<?php

namespace App\Console\Commands;

use App\Services\SwizhPointService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SwizhMonthlyClosing extends Command
{
    protected $signature = 'swizh:monthly-closing {period? : Format YYYY-MM, default bulan lalu}';
    protected $description = 'Tutup buku bulanan Swizh: hanguskan pending bonus, bagi Bonus Leader 5%, dan hitung kualifikasi Reward 10%';

    public function handle(SwizhPointService $spService): int
    {
        // Jika tidak diisi manual, otomatis menutup periode bulan sebelumnya
        $period = $this->argument('period') ?? now()->subMonth()->format('Y-m');

        // Cegah tutup buku ganda untuk periode yang sama
        $alreadyClosed = DB::table('bonus_ledgers')
            ->where('period_month', $period)
            ->whereIn('bonus_type', ['leader_share_5', 'reward_10'])
            ->exists();

        if ($alreadyClosed) {
            $this->warn("Periode {$period} sudah pernah ditutup bukunya.");
            return self::FAILURE;
        }

        DB::transaction(function () use ($period, $spService) {
            // 1. Hanguskan semua bonus pending yang tidak memenuhi syarat 1 SP pribadi
            $expiredCount = DB::table('bonus_ledgers')
                ->where('period_month', $period)
                ->where('status', 'pending')
                ->update(['status' => 'expired', 'updated_at' => now()]);

            $this->info("Bonus pending hangus (expired): {$expiredCount} transaksi.");

            // 2. Hitung Total SP Global pada periode tersebut
            $globalSp = (int) DB::table('sp_transactions')
                ->where('period_month', $period)
                ->where('status', 'paid')
                ->sum('sp_earned');

            if ($globalSp <= 0) {
                $this->info("Total SP Global periode {$period} adalah 0. Tutup buku selesai.");
                return;
            }

            // 3. Cari seluruh member yang lolos kualifikasi Leader
            $allUserIds = DB::table('users')->pluck('id');
            $qualifiedLeaderIds = [];

            foreach ($allUserIds as $uid) {
                $stats = $spService->getMemberMonthlyStats($uid, $period);
                if ($stats['is_leader_qualified']) {
                    $qualifiedLeaderIds[] = $uid;
                }
            }

            $leaderCount = count($qualifiedLeaderIds);
            $this->info("Total SP Global: {$globalSp} | Jumlah Leader Lolos: {$leaderCount}");

            // 4. Distribusi Bonus Leader (5% x Total SP Global / Jumlah Leader)
            if ($leaderCount > 0) {
                $leaderPoolRp = (int) round($globalSp * 0.05);
                $sharePerLeader = (int) floor($leaderPoolRp / $leaderCount);

                if ($sharePerLeader > 0) {
                    foreach ($qualifiedLeaderIds as $leaderId) {
                        DB::table('bonus_ledgers')->insert([
                            'user_id' => $leaderId,
                            'sp_transaction_id' => null,
                            'bonus_type' => 'leader_share_5',
                            'amount_rp' => $sharePerLeader,
                            'period_month' => $period,
                            'status' => 'approved',
                            'notes' => "Bonus Leader 5% Global ({$period}) - {$leaderCount} Leader",
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);

                        DB::table('users')->where('id', $leaderId)->increment('wallet_balance', $sharePerLeader);
                    }
                }

                // 5. Cek Program Reward (Memiliki minimal 5 Referral langsung yang menjadi Leader)
                $rewardWinners = [];
                foreach ($allUserIds as $uid) {
                    $directLeaderRefs = DB::table('users')
                        ->where('referred_by', $uid)
                        ->whereIn('id', $qualifiedLeaderIds)
                        ->count();

                    if ($directLeaderRefs >= 5) {
                        $rewardWinners[] = $uid;
                    }
                }

                $winnerCount = count($rewardWinners);
                if ($winnerCount > 0) {
                    $rewardPoolRp = (int) round($globalSp * 0.10);
                    $rewardPerWinner = (int) floor($rewardPoolRp / $winnerCount);

                    foreach ($rewardWinners as $winnerId) {
                        DB::table('bonus_ledgers')->insert([
                            'user_id' => $winnerId,
                            'sp_transaction_id' => null,
                            'bonus_type' => 'reward_10',
                            'amount_rp' => $rewardPerWinner,
                            'period_month' => $period,
                            'status' => 'approved',
                            'notes' => "Program Reward 10% (5+ Referral Leader periode {$period})",
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }
            }
        });

        $this->info("Tutup buku otomatis periode {$period} BERHASIL diselesaikan!");
        return self::SUCCESS;
    }
}
