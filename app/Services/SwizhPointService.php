<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SwizhPointService
{
    public function recordTransaction(int $buyerId, int $sellerId, int $amountRp, string $paymentMethod = 'qris_gateway', int $nonSpAmountRp = 0): array
    {
        return DB::transaction(function () use ($buyerId, $sellerId, $amountRp, $paymentMethod, $nonSpAmountRp) {
            $period = now()->format('Y-m');
            $buyer = User::findOrFail($buyerId);
            $seller = DB::table('sellers')->where('id', $sellerId)->lockForUpdate()->firstOrFail();

            if ($seller->user_id === $buyer->id) {
                throw new \Exception('Anda tidak dapat bertransaksi di toko milik sendiri.');
            }

            $spEligibleAmount = max(0, $amountRp - $nonSpAmountRp);
            $spEarned = (int) round($spEligibleAmount * ($seller->sp_percentage / 100));
            $totalSpObligationRp = (int) round($spEarned * 0.655);

            if ($paymentMethod === 'wallet_balance') {
                $buyerLocked = DB::table('users')->where('id', $buyerId)->lockForUpdate()->first();
                if ($buyerLocked->wallet_balance < $amountRp) {
                    throw new \Exception('Saldo Wallet Anda tidak mencukupi.');
                }
                DB::table('users')->where('id', $buyerId)->decrement('wallet_balance', $amountRp);
                $netToSeller = max(0, $amountRp - $totalSpObligationRp);
                DB::table('users')->where('id', $seller->user_id)->increment('wallet_balance', $netToSeller);
            } elseif ($paymentMethod === 'cash_deposit') {
                if ($seller->cash_deposit_balance < 100000 || $seller->cash_deposit_balance < $totalSpObligationRp) {
                    throw new \Exception('Merchant belum mengaktifkan pembayaran Tunai/Cash (Deposit minimal Rp 100.000).');
                }
                if ($totalSpObligationRp > 0) {
                    DB::table('sellers')->where('id', $seller->id)->decrement('cash_deposit_balance', $totalSpObligationRp);
                }
            }

            $txId = DB::table('sp_transactions')->insertGetId([
                'invoice_code' => 'INV-' . now()->format('Ymd') . '-' . strtoupper(Str::random(5)),
                'buyer_id' => $buyerId,
                'seller_id' => $sellerId,
                'transaction_amount' => $amountRp,
                'sp_earned' => $spEarned,
                'payment_method' => $paymentMethod,
                'period_month' => $period,
                'status' => 'paid',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $directBonus = (int) round($spEarned * 0.40);
            if ($directBonus > 0) {
                $this->creditBonus($buyer->id, $txId, 'direct_40', $directBonus, $period, 'approved', "Bonus langsung 40% ({$seller->store_name})");
                $this->sendNotification($buyer->id, 'Bonus Langsung Masuk!', "Anda mendapat +Rp " . number_format($directBonus, 0, ',', '.') . " dari belanja di {$seller->store_name}.");
            }

            $this->unlockPendingBonusesIfQualified($buyer->id, $period);

            if ($buyer->referred_by) {
                $refMemberBonus = (int) round($spEarned * 0.10);
                if ($refMemberBonus > 0) {
                    $isQualified = $this->hasPersonalSp($buyer->referred_by, $period);
                    $status = $isQualified ? 'approved' : 'pending';
                    $this->creditBonus($buyer->referred_by, $txId, 'ref_member_10', $refMemberBonus, $period, $status, "Bonus referral member 10% ({$buyer->name})");
                    $this->sendNotification($buyer->referred_by, $isQualified ? 'Bonus Referral Cair!' : 'Bonus Referral Pending', "Member {$buyer->name} belanja di {$seller->store_name}. Bonus +Rp " . number_format($refMemberBonus, 0, ',', '.'));
                }
            }

            if ($seller->referred_by) {
                $refSellerBonus = (int) round($spEarned * 0.005);
                if ($refSellerBonus > 0) {
                    $isQualified = $this->hasPersonalSp($seller->referred_by, $period);
                    $status = $isQualified ? 'approved' : 'pending';
                    $this->creditBonus($seller->referred_by, $txId, 'ref_seller_05', $refSellerBonus, $period, $status, "Bonus referral omset seller 0.5% ({$seller->store_name})");
                    $this->sendNotification($seller->referred_by, $isQualified ? 'Bonus Omset Seller Cair!' : 'Bonus Seller Pending', "Toko binaan {$seller->store_name} mencatat transaksi. Bonus +Rp " . number_format($refSellerBonus, 0, ',', '.'));
                }
            }

            return [
                'transaction_id' => $txId,
                'amount_rp' => $amountRp,
                'sp_percentage' => $seller->sp_percentage,
                'sp_earned' => $spEarned,
                'direct_bonus' => $directBonus,
                'payment_method' => $paymentMethod,
            ];
        });
    }

    public function sendNotification(int $userId, string $title, string $message): void
    {
        DB::table('user_notifications')->insert([
            'user_id' => $userId,
            'title' => $title,
            'message' => $message,
            'is_read' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function getRequiredLeaderGroupSp(string $period): array
    {
        $settings = DB::table('app_settings')->pluck('value', 'key');
        $isPromoActive = ($settings['leader_promo_active'] ?? '0') === '1';
        $promoUntil = $settings['leader_promo_until_month'] ?? '';
        $promoTarget = (int) ($settings['leader_promo_group_sp'] ?? 1000000);
        $normalTarget = (int) ($settings['leader_normal_group_sp'] ?? 10000000);

        if ($isPromoActive && $period <= $promoUntil) {
            return ['target_sp' => $promoTarget, 'is_promo' => true, 'promo_until' => $promoUntil];
        }
        return ['target_sp' => $normalTarget, 'is_promo' => false, 'promo_until' => $promoUntil];
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
                if ($row->total_sp >= 1) $activeReferralsCount++;
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
        if (!$this->hasPersonalSp($userId, $period)) return;

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
            $this->sendNotification($userId, 'Bonus Pending Cair!', "Syarat 1 SP terpenuhi! Rp " . number_format($pendingAmount, 0, ',', '.') . " masuk ke Wallet.");
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
