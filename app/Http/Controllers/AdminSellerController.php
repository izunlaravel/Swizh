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
        abort_unless(Auth::user()->is_admin, 403, 'Hanya Admin Swizh yang dapat mengakses halaman ini.');

        $period = now()->format('Y-m');

        $sellers = DB::table('sellers')
            ->join('users as owners', 'sellers.user_id', '=', 'owners.id')
            ->leftJoin('users as referrers', 'sellers.referred_by', '=', 'referrers.id')
            ->select('sellers.*', 'owners.name as owner_name', 'owners.email as owner_email', 'referrers.name as referrer_name')
            ->orderByDesc('sellers.id')
            ->get();

        $users = User::select('id', 'name', 'email', 'referral_code', 'wallet_balance')->get();

        $globalSpMonth = (int) DB::table('sp_transactions')
            ->where('period_month', $period)
            ->where('status', 'paid')
            ->sum('sp_earned');

        $leaderPoolRp = (int) round($globalSpMonth * 0.05);

        return view('admin.sellers', compact('sellers', 'users', 'period', 'globalSpMonth', 'leaderPoolRp'));
    }

    public function store(Request $request)
    {
        abort_unless(Auth::user()->is_admin, 403);

        $request->validate([
            'user_id' => 'required|exists:users,id',
            'store_name' => 'required|string|max:255',
            'sp_percentage' => 'required|numeric|min:0.1|max:100',
            'referrer_code' => 'nullable|string|exists:users,referral_code',
        ]);

        $referrerId = null;
        if ($request->filled('referrer_code')) {
            $referrerId = User::where('referral_code', strtoupper($request->referrer_code))->value('id');
        }

        DB::table('sellers')->insert([
            'user_id' => $request->user_id,
            'referred_by' => $referrerId,
            'store_name' => $request->store_name,
            'sp_percentage' => $request->sp_percentage,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        User::where('id', $request->user_id)->update(['is_seller' => true]);

        return back()->with('success', 'Seller baru berhasil didaftarkan!');
    }

    public function updatePercentage(Request $request, int $id)
    {
        abort_unless(Auth::user()->is_admin, 403);

        $request->validate([
            'sp_percentage' => 'required|numeric|min:0.1|max:100',
        ]);

        DB::table('sellers')->where('id', $id)->update([
            'sp_percentage' => $request->sp_percentage,
            'updated_at' => now(),
        ]);

        return back()->with('success', 'Persentase SP Seller berhasil diubah oleh Admin!');
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
