<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class WithdrawController extends Controller
{
    public function store(Request $request)
    {
        $user = Auth::user();
        $currentHour = (int) now()->format('H');
        $bypassSchedule = DB::table('app_settings')->where('key', 'withdraw_anytime_test')->value('value') === '1';

        if (!$bypassSchedule && ($currentHour < 8 || $currentHour >= 10)) {
            return back()->with('popup_error', 'Penarikan saldo (Withdraw) hanya dapat dilakukan setiap hari pukul 08.00 – 10.00 WIB.');
        }

        if ($user->wallet_balance <= 50000) {
            return back()->with('popup_error', 'Saldo di bawah Rp 50.000 hanya dapat digunakan untuk berbelanja di Merchant Swizh.');
        }

        $request->validate([
            'amount_rp' => 'required|integer|min:50001|max:' . $user->wallet_balance,
            'bank_name' => 'required|string|max:50',
            'bank_account_number' => 'required|string|max:50',
            'bank_account_name' => 'required|string|max:100',
        ]);

        $adminFee = 2500;
        $netAmount = $request->amount_rp - $adminFee;

        DB::transaction(function () use ($user, $request, $adminFee, $netAmount) {
            DB::table('users')->where('id', $user->id)->update([
                'bank_name' => $request->bank_name,
                'bank_account_number' => $request->bank_account_number,
                'bank_account_name' => $request->bank_account_name,
                'wallet_balance' => DB::raw('wallet_balance - ' . (int) $request->amount_rp),
                'updated_at' => now(),
            ]);

            DB::table('withdrawals')->insert([
                'user_id' => $user->id,
                'amount_rp' => $request->amount_rp,
                'admin_fee_rp' => $adminFee,
                'net_amount_rp' => $netAmount,
                'bank_name' => $request->bank_name,
                'bank_account_number' => $request->bank_account_number,
                'bank_account_name' => $request->bank_account_name,
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        return back()->with('popup_success', "Permohonan Withdraw Rp " . number_format($request->amount_rp, 0, ',', '.') . " (Bersih: Rp " . number_format($netAmount, 0, ',', '.') . ") berhasil diajukan!");
    }
}
