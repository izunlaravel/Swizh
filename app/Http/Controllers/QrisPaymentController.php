<?php

namespace App\Http\Controllers;

use App\Services\SwizhPointService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class QrisPaymentController extends Controller
{
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

    public function processPayment(Request $request, SwizhPointService $spService)
    {
        $request->validate([
            'store_code' => 'required|string',
            'amount_rp' => 'required|integer|min:1000',
            'non_sp_amount_rp' => 'nullable|integer|min:0',
            'payment_method' => 'required|in:qris_gateway,wallet_balance,cash_deposit',
        ]);

        $user = Auth::user();
        $storeId = (int) str_replace('SWZ-STORE-', '', strtoupper(trim($request->store_code)));
        $seller = DB::table('sellers')->where('id', $storeId)->where('status', 'approved')->first();

        if (!$seller) {
            return back()->with('popup_error', 'Kode QR Seller tidak valid atau toko belum aktif.');
        }

        try {
            $result = $spService->recordTransaction(
                $user->id,
                $seller->id,
                (int) $request->amount_rp,
                $request->payment_method,
                (int) ($request->non_sp_amount_rp ?? 0)
            );

            return redirect()->route('dashboard')->with(
                'popup_success',
                "Transaksi di {$seller->store_name} sebesar Rp " . number_format($request->amount_rp, 0, ',', '.') .
                " SUKSES! Anda memperoleh {$result['sp_earned']} SP & Bonus Langsung Rp " . number_format($result['direct_bonus'], 0, ',', '.') . "."
            );
        } catch (\Exception $e) {
            return back()->with('popup_error', $e->getMessage());
        }
    }
}
