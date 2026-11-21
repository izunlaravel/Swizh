<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\SwizhPointService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AdminSellerController extends Controller
{
    public function index()
    {
        abort_unless(Auth::user()->is_admin, 403);

        $period = now()->format('Y-m');
        $sellers = DB::table('sellers')
            ->join('users as owners', 'sellers.user_id', '=', 'owners.id')
            ->leftJoin('users as referrers', 'sellers.referred_by', '=', 'referrers.id')
            ->select('sellers.*', 'owners.name as owner_name', 'owners.email as owner_email', 'referrers.name as referrer_name')
            ->orderByRaw("CASE WHEN sellers.status = 'pending' OR sellers.requested_sp_percentage IS NOT NULL THEN 0 ELSE 1 END")
            ->orderByDesc('sellers.id')
            ->get();

        $users = User::select('id', 'name', 'email', 'referral_code', 'wallet_balance')->get();
        $approvedSellers = $sellers->where('status', 'approved');
        $globalSpMonth = (int) DB::table('sp_transactions')->where('period_month', $period)->where('status', 'paid')->sum('sp_earned');
        $leaderPoolRp = (int) round($globalSpMonth * 0.05);

        return view('admin.sellers', compact('sellers', 'approvedSellers', 'users', 'period', 'globalSpMonth', 'leaderPoolRp'));
    }

    // Admin menyetujui pendaftaran Seller baru
    public function approveSeller(int $id)
    {
        abort_unless(Auth::user()->is_admin, 403);

        $seller = DB::table('sellers')->where('id', $id)->firstOrFail();

        DB::table('sellers')->where('id', $id)->update([
            'status' => 'approved',
            'is_active' => true,
            'updated_at' => now(),
        ]);

        User::where('id', $seller->user_id)->update(['is_seller' => true]);

        return back()->with('success', "Toko {$seller->store_name} resmi disetujui menjadi Seller aktif!");
    }

    // Admin menyetujui perubahan persentase SP yang diajukan Seller
    public function approveSpChange(Request $request, int $id)
    {
        abort_unless(Auth::user()->is_admin, 403);

        $request->validate(['sp_percentage' => 'required|numeric|min:0.1|max:100']);

        DB::table('sellers')->where('id', $id)->update([
            'sp_percentage' => $request->sp_percentage,
            'requested_sp_percentage' => null,
            'updated_at' => now(),
        ]);

        return back()->with('success', 'Persentase SP Seller resmi disetujui & diperbarui!');
    }

    public function simulateTransaction(Request $request, SwizhPointService $spService)
    {
        abort_unless(Auth::user()->is_admin, 403);

        $request->validate([
            'buyer_id' => 'required|exists:users,id',
            'seller_id' => 'required|exists:sellers,id',
            'amount_rp' => 'required|integer|min:1000',
        ]);

        $result = $spService->recordTransaction($request->buyer_id, $request->seller_id, $request->amount_rp);

        return back()->with('success', "Transaksi Rp " . number_format($result['amount_rp'], 0, ',', '.') . " sukses! Terbentuk {$result['sp_earned']} SP ({$result['sp_percentage']}%). Bonus Langsung 40% = Rp " . number_format($result['direct_bonus'], 0, ',', '.'));
    }
}
