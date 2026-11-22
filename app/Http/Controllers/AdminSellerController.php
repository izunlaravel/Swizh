<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\SwizhPointService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AdminSellerController extends Controller
{
    public function index(Request $request, SwizhPointService $spService)
    {
        abort_unless(Auth::user()->is_admin, 403);

        $period = now()->format('Y-m');
        $search = trim((string) $request->query('search', ''));
        $filterTab = $request->query('tab', 'all');

        $newMembersToday = User::whereDate('created_at', today())->count();
        $totalMembers = User::count();
        $totalActiveSellers = DB::table('sellers')->where('status', 'approved')->count();

        $memberListQuery = User::query()->orderByDesc('id');
        if ($filterTab === 'today') {
            $memberListQuery->whereDate('created_at', today());
        } elseif ($filterTab === 'sellers') {
            $memberListQuery->where('is_seller', true);
        }
        if ($search !== '') {
            $memberListQuery->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('email', 'like', '%' . $search . '%')
                  ->orWhere('phone', 'like', '%' . $search . '%')
                  ->orWhere('referral_code', 'like', '%' . $search . '%');
            });
        }
        $filteredMembers = $memberListQuery->limit(25)->get();

        $chartLabels = [];
        $chartMembers = [];
        $chartSp = [];
        for ($i = 6; $i >= 0; $i--) {
            $d = now()->subDays($i)->format('Y-m-d');
            $chartLabels[] = now()->subDays($i)->format('d M');
            $chartMembers[] = User::whereDate('created_at', $d)->count();
            $chartSp[] = (int) DB::table('sp_transactions')->whereDate('created_at', $d)->where('status', 'paid')->sum('sp_earned');
        }

        $grossSalesMonth = (int) DB::table('sp_transactions')->where('period_month', $period)->where('status', 'paid')->sum('transaction_amount');
        $globalSpMonth = (int) DB::table('sp_transactions')->where('period_month', $period)->where('status', 'paid')->sum('sp_earned');
        $totalPayoutObligation = (int) round($globalSpMonth * 0.655);
        $companyNetRetention = max(0, $globalSpMonth - $totalPayoutObligation);
        $leaderPoolRp = (int) round($globalSpMonth * 0.05);
        $rewardPoolRp = (int) round($globalSpMonth * 0.10);

        $sellers = DB::table('sellers')
            ->join('users as owners', 'sellers.user_id', '=', 'owners.id')
            ->leftJoin('users as referrers', 'sellers.referred_by', '=', 'referrers.id')
            ->select('sellers.*', 'owners.name as owner_name', 'owners.phone as owner_phone', 'referrers.name as referrer_name')
            ->orderByDesc('sellers.id')
            ->get();

        $approvedSellers = $sellers->where('status', 'approved');
        $users = User::select('id', 'name', 'email', 'referral_code', 'wallet_balance')->get();

        $withdrawals = DB::table('withdrawals')
            ->join('users', 'withdrawals.user_id', '=', 'users.id')
            ->select('withdrawals.*', 'users.name as user_name', 'users.phone as user_phone')
            ->orderByDesc('withdrawals.id')
            ->limit(30)
            ->get();
        $qualifiedLeaders = [];
        $qualifiedLeaderIds = [];
        foreach ($users as $u) {
            $st = $spService->getMemberMonthlyStats($u->id, $period);
            if ($st['is_leader_qualified']) {
                $qualifiedLeaders[] = ['user' => $u, 'stats' => $st];
                $qualifiedLeaderIds[] = $u->id;
            }
        }

        $qualifiedRewards = [];
        if (count($qualifiedLeaderIds) > 0) {
            foreach ($users as $u) {
                $directLeaders = User::where('referred_by', $u->id)->whereIn('id', $qualifiedLeaderIds)->count();
                if ($directLeaders >= 5) {
                    $qualifiedRewards[] = ['user' => $u, 'leader_refs' => $directLeaders];
                }
            }
        }

        $promos = DB::table('promo_broadcasts')->orderByDesc('id')->get();
        $activePromo = $promos->where('is_active', true)->first();
        $qualifiedPromoMembers = [];
        if ($activePromo) {
            foreach ($users as $u) {
                $st = $spService->getMemberMonthlyStats($u->id, $period);
                if ($st['personal_sp'] >= $activePromo->min_personal_sp &&
                    $st['active_referrals'] >= $activePromo->min_active_referrals &&
                    $st['group_sp'] >= $activePromo->min_group_sp) {
                    $qualifiedPromoMembers[] = ['user' => $u, 'stats' => $st];
                }
            }
        }

        $inboxes = DB::table('feedback_inboxes')
            ->join('users', 'feedback_inboxes.user_id', '=', 'users.id')
            ->select('feedback_inboxes.*', 'users.name as sender_name', 'users.phone as sender_phone')
            ->orderByDesc('feedback_inboxes.id')
            ->limit(25)
            ->get();

        return view('admin.sellers', compact(
            'period', 'search', 'filterTab', 'newMembersToday', 'totalMembers', 'totalActiveSellers',
            'filteredMembers', 'chartLabels', 'chartMembers', 'chartSp',
            'grossSalesMonth', 'globalSpMonth', 'totalPayoutObligation', 'companyNetRetention', 'leaderPoolRp', 'rewardPoolRp',
            'sellers', 'approvedSellers', 'users', 'withdrawals',
            'qualifiedLeaders', 'qualifiedRewards', 'promos', 'activePromo', 'qualifiedPromoMembers', 'inboxes'
        ));
    }

    public function approveSeller(int $id, SwizhPointService $spService)
    {
        abort_unless(Auth::user()->is_admin, 403);
        $seller = DB::table('sellers')->where('id', $id)->firstOrFail();
        DB::table('sellers')->where('id', $id)->update(['status' => 'approved', 'is_active' => true, 'updated_at' => now()]);
        User::where('id', $seller->user_id)->update(['is_seller' => true]);
        $spService->sendNotification($seller->user_id, 'Toko Disetujui!', "Toko {$seller->store_name} resmi aktif!");
        if ($seller->referred_by) {
            $spService->sendNotification($seller->referred_by, 'Merchant Referral Aktif!', "Toko {$seller->store_name} telah aktif. Anda mendapat 0.5% SP dari omsetnya!");
        }
        return back()->with('success', "Toko {$seller->store_name} disetujui!");
    }

    public function approveSpChange(Request $request, int $id)
    {
        abort_unless(Auth::user()->is_admin, 403);
        $request->validate(['sp_percentage' => 'required|numeric|min:5']);
        DB::table('sellers')->where('id', $id)->update(['sp_percentage' => $request->sp_percentage, 'requested_sp_percentage' => null, 'updated_at' => now()]);
        return back()->with('success', 'Persentase SP Seller disetujui!');
    }

    public function approveNonSpNotes(int $id)
    {
        abort_unless(Auth::user()->is_admin, 403);
        $seller = DB::table('sellers')->where('id', $id)->firstOrFail();
        DB::table('sellers')->where('id', $id)->update(['non_sp_notes' => $seller->requested_non_sp_notes, 'requested_non_sp_notes' => null, 'updated_at' => now()]);
        return back()->with('success', 'Catatan Non-SP Seller disetujui!');
    }

    public function processWithdraw(Request $request, int $id, SwizhPointService $spService)
    {
        abort_unless(Auth::user()->is_admin, 403);
        $wd = DB::table('withdrawals')->where('id', $id)->where('status', 'pending')->firstOrFail();
        if ($request->action === 'approve') {
            DB::table('withdrawals')->where('id', $id)->update(['status' => 'approved', 'admin_notes' => 'Transfer sukses', 'updated_at' => now()]);
            $spService->sendNotification($wd->user_id, 'Withdraw Berhasil!', "Dana Rp " . number_format($wd->net_amount_rp, 0, ',', '.') . " telah ditransfer.");
            return back()->with('success', 'Withdraw disetujui!');
        }
        DB::table('users')->where('id', $wd->user_id)->increment('wallet_balance', $wd->amount_rp);
        DB::table('withdrawals')->where('id', $id)->update(['status' => 'rejected', 'admin_notes' => 'Ditolak Admin', 'updated_at' => now()]);
        return back()->with('success', 'Withdraw ditolak & saldo dikembalikan.');
    }

    public function storePromoBroadcast(Request $request)
    {
        abort_unless(Auth::user()->is_admin, 403);
        $request->validate(['title' => 'required|string|max:200', 'content' => 'required|string']);
        DB::table('promo_broadcasts')->insert([
            'title' => $request->title,
            'content' => $request->content,
            'min_personal_sp' => (int) ($request->min_personal_sp ?? 0),
            'min_active_referrals' => (int) ($request->min_active_referrals ?? 0),
            'min_group_sp' => (int) ($request->min_group_sp ?? 0),
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        return back()->with('success', 'Promo berhasil di-broadcast ke seluruh Member!');
    }

    public function simulateTransaction(Request $request, SwizhPointService $spService)
    {
        abort_unless(Auth::user()->is_admin, 403);
        $request->validate(['buyer_id' => 'required|exists:users,id', 'seller_id' => 'required|exists:sellers,id', 'amount_rp' => 'required|integer|min:1000']);
        try {
            $res = $spService->recordTransaction($request->buyer_id, $request->seller_id, $request->amount_rp);
            return back()->with('success', "Simulasi sukses! SP: {$res['sp_earned']}, Bonus Langsung: Rp " . number_format($res['direct_bonus'], 0, ',', '.'));
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function runClosing()
    {
        abort_unless(Auth::user()->is_admin, 403);
        $period = now()->format('Y-m');
        $exit = \Illuminate\Support\Facades\Artisan::call('swizh:monthly-closing', ['period' => $period]);
        return $exit === 0 ? back()->with('success', "Tutup buku {$period} berhasil!") : back()->with('error', "Periode {$period} sudah ditutup.");
    }

    public function updateLeaderPromo(Request $request)
    {
        abort_unless(Auth::user()->is_admin, 403);
        DB::table('app_settings')->updateOrInsert(['key' => 'leader_promo_active'], ['value' => $request->has('leader_promo_active') ? '1' : '0', 'updated_at' => now()]);
        DB::table('app_settings')->updateOrInsert(['key' => 'leader_promo_until_month'], ['value' => $request->leader_promo_until_month, 'updated_at' => now()]);
        DB::table('app_settings')->updateOrInsert(['key' => 'leader_promo_group_sp'], ['value' => (string) $request->leader_promo_group_sp, 'updated_at' => now()]);
        DB::table('app_settings')->updateOrInsert(['key' => 'withdraw_anytime_test'], ['value' => $request->has('withdraw_anytime_test') ? '1' : '0', 'updated_at' => now()]);
        return back()->with('success', 'Pengaturan Operasional disimpan!');
    }

    public function updateLogo(Request $request)
    {
        abort_unless(Auth::user()->is_admin, 403);
        $request->validate(['logo' => 'required|image|mimes:png,jpg,jpeg,svg,webp|max:2048']);
        $file = $request->file('logo');
        $filename = 'logo-swizh.' . $file->getClientOriginalExtension();
        $file->move(public_path('images'), $filename);
        DB::table('app_settings')->updateOrInsert(['key' => 'site_logo'], ['value' => 'images/' . $filename, 'updated_at' => now()]);
        return back()->with('success', 'Logo diperbarui!');
    }
}
