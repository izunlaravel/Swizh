<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SwizhPointService
{
    public function recordTransaction(int $buyerId, int $sellerId, int $amountRp): array
    {
        return DB::transaction(function () use ($buyerId, $sellerId, $amountRp) {
            $period = now()->format('Y-m');
            $buyer = User::findOrFail($buyerId);
            $seller = DB::table('sellers')->where('id', $sellerId)->firstOrFail();

            $spEarned = (int) round($amountRp * ($seller->sp_percentage / 100));

            $txId = DB::table('sp_transactions')->insertGetId([
                'invoice_code' => 'INV-' . now()->format('Ymd') . '-' . strtoupper(Str::random(5)),
                'buyer_id' => $buyerId,
                'seller_id' => $sellerId,
                'transaction_amount' => $amountRp,
                'sp_earned' => $spEarned,
                'period_month' => $period,
                'status' => 'paid',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $directBonus = (int) round($spEarned * 0.40);
            if ($directBonus > 0) {
                $this->creditBonus($buyer->id, $txId, 'direct_40', $directBonus, $period, 'approved', "Bonus langsung 40% ({$seller->store_name})");
            }

            $this->unlockPendingBonusesIfQualified($buyer->id, $period);

            if ($buyer->referred_by) {
                $refMemberBonus = (int) round($spEarned * 0.10);
                if ($refMemberBonus > 0) {
                    $status = $this->hasPersonalSp($buyer->referred_by, $period) ? 'approved' : 'pending';
                    $this->creditBonus($buyer->referred_by, $txId, 'ref_member_10', $refMemberBonus, $period, $status, "Bonus referral member 10% ({$buyer->name})");
                }
            }

            if ($seller->referred_by) {
                $refSellerBonus = (int) round($spEarned * 0.005);
                if ($refSellerBonus > 0) {
                    $status = $this->hasPersonalSp($seller->referred_by, $period) ? 'approved' : 'pending';
                    $this->creditBonus($seller->referred_by, $txId, 'ref_seller_05', $refSellerBonus, $period, $status, "Bonus referral omset seller 0.5% ({$seller->store_name})");
                }
            }

            return [
                'transaction_id' => $txId,
                'amount_rp' => $amountRp,
                'sp_percentage' => $seller->sp_percentage,
                'sp_earned' => $spEarned,
                'direct_bonus' => $directBonus,
            ];
        });
    }

    /**
     * Tentukan target SP Grup Leader: 1 Juta saat Promo Launching, 10 Juta saat Normal
     */
    public function getRequiredLeaderGroupSp(string $period): array
    {
        $settings = DB::table('app_settings')->pluck('value', 'key');
        $isPromoActive = ($settings['leader_promo_active'] ?? '0') === '1';
        $promoUntil = $settings['leader_promo_until_month'] ?? '';
        $promoTarget = (int) ($settings['leader_promo_group_sp'] ?? 1000000);
        $normalTarget = (int) ($settings['leader_normal_group_sp'] ?? 10000000);

        if ($isPromoActive && $period <= $promoUntil) {
            return [
                'target_sp' => $promoTarget,
                'is_promo' => true,
                'promo_until' => $promoUntil,
            ];
        }

        return [
            'target_sp' => $normalTarget,
            'is_promo' => false,
            'promo_until' => $promoUntil,
        ];
    }

    public function hasPersonalSp(int $userId, string $period): bool
    {
        return $this->getPersonalSp($userId, $period) >= 1;
    }

    public function getPersonalSp(int $userId, string $period): int
    {
        return (int) DB::table('sp_transactions')
            ->where('buyer_id', $userId)
            ->where('period_month', $period)
            ->where('status', 'paid')
            ->sum('sp_earned');
    }

    public function getMemberMonthlyStats(int $userId, string $period): array
    {
        $personalSp = $this->getPersonalSp($userId, $period);
        $referralIds = DB::table('users')->where('referred_by', $userId)->pluck('id');

        $activeReferralsCount = 0;
        $groupSp = 0;

        if ($referralIds->isNotEmpty()) {
            $referralSpSums = DB::table('sp_transactions')
                ->select('buyer_id', DB::raw('SUM(sp_earned) as total_sp'))
                ->whereIn('buyer_id', $referralIds)
                ->where('period_month', $period)
                ->where('status', 'paid')
                ->groupBy('buyer_id')
                ->get();

            foreach ($referralSpSums as $row) {
                if ($row->total_sp >= 1) {
                    $activeReferralsCount++;
                }
                $groupSp += (int) $row->total_sp;
            }
        }

        $leaderRule = $this->getRequiredLeaderGroupSp($period);
        $requiredGroupSp = $leaderRule['target_sp'];

        $isLeaderQualified = ($personalSp >= 1) && ($activeReferralsCount >= 10) && ($groupSp >= $requiredGroupSp);

        return [
            'period' => $period,
            'personal_sp' => $personalSp,
            'active_referrals' => $activeReferralsCount,
            'group_sp' => $groupSp,
            'required_group_sp' => $requiredGroupSp,
            'is_promo_leader' => $leaderRule['is_promo'],
            'promo_until' => $leaderRule['promo_until'],
            'is_leader_qualified' => $isLeaderQualified,
        ];
    }

    public function unlockPendingBonusesIfQualified(int $userId, string $period): void
    {
        if (!$this->hasPersonalSp($userId, $period)) {
            return;
        }

        $pendingAmount = DB::table('bonus_ledgers')
            ->where('user_id', $userId)
            ->where('period_month', $period)
            ->where('status', 'pending')
            ->sum('amount_rp');

        if ($pendingAmount > 0) {
            DB::table('bonus_ledgers')
                ->where('user_id', $userId)
                ->where('period_month', $period)
                ->where('status', 'pending')
                ->update(['status' => 'approved', 'updated_at' => now()]);

            DB::table('users')->where('id', $userId)->increment('wallet_balance', $pendingAmount);
        }
    }

    private function creditBonus(int $userId, int $txId, string $type, int $amountRp, string $period, string $status, string $notes): void
    {
        DB::table('bonus_ledgers')->insert([
            'user_id' => $userId,
            'sp_transaction_id' => $txId,
            'bonus_type' => $type,
            'amount_rp' => $amountRp,
            'period_month' => $period,
            'status' => $status,
            'notes' => $notes,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if ($status === 'approved') {
            DB::table('users')->where('id', $userId)->increment('wallet_balance', $amountRp);
        }
    }
}
