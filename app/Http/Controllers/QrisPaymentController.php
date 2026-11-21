<?php

namespace App\Http\Controllers;

use App\Services\SwizhPointService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class QrisPaymentController extends Controller
{
    // Halaman Scan QR & Bayar untuk Member
    public function scanPage(Request $request)
    {
        $sellers = DB::table('sellers')->where('status', 'approved')->where('is_active', true)->get();
        $selectedSeller = null;

        if ($request->filled('store_code')) {
            $storeId = (int) str_replace('SWZ-STORE-', '', strtoupper($request->store_code));
            $selectedSeller = DB::table('sellers')->where('id', $storeId)->where('status', 'approved')->first();
        }

        return view('qris.scan', compact('sellers', 'selectedSeller'));
    }

    // Proses Pembayaran QRIS & Hitung SP Otomatis
    public function processPayment(Request $request, SwizhPointService $spService)
    {
        $request->validate([
            'store_code' => 'required|string',
            'amount_rp' => 'required|integer|min:1000',
            'payment_method' => 'required|in:qris_gateway,wallet_balance',
        ]);

        $user = Auth::user();
        $storeId = (int) str_replace('SWZ-STORE-', '', strtoupper(trim($request->store_code)));
        $seller = DB::table('sellers')->where('id', $storeId)->where('status', 'approved')->first();

        if (!$seller) {
            return back()->with('error', 'Kode QR Seller tidak valid atau toko belum aktif.');
        }

        if ($seller->user_id === $user->id) {
            return back()->with('error', 'Anda tidak dapat melakukan transaksi belanja di toko milik sendiri.');
        }

        // Jika bayar potong Saldo Wallet Swizh
        if ($request->payment_method === 'wallet_balance') {
            if ($user->wallet_balance < $request->amount_rp) {
                return back()->with('error', 'Saldo Wallet tidak mencukupi untuk pembayaran ini.');
            }
            DB::table('users')->where('id', $user->id)->decrement('wallet_balance', $request->amount_rp);
            DB::table('users')->where('id', $seller->user_id)->increment('wallet_balance', $request->amount_rp);
        }

        // Eksekusi distribusi SP (40% Langsung, 10% Ref Member, 0.5% Ref Seller)
        $result = $spService->recordTransaction($user->id, $seller->id, $request->amount_rp);

        return redirect()->route('dashboard')->with(
            'success',
            "Pembayaran QRIS ke {$seller->store_name} sebesar Rp " . number_format($request->amount_rp, 0, ',', '.') .
            " BERHASIL! Anda mendapat {$result['sp_earned']} SP & Bonus Langsung Rp " . number_format($result['direct_bonus'], 0, ',', '.')
        );
    }
}
