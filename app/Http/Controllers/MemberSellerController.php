<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class MemberSellerController extends Controller
{
    // Member biasa mengajukan pendaftaran Seller baru
    public function apply(Request $request)
    {
        $user = Auth::user();

        if (DB::table('sellers')->where('user_id', $user->id)->exists()) {
            return back()->with('error', 'Anda sudah memiliki pengajuan atau toko terdaftar.');
        }

        $request->validate([
            'store_name' => 'required|string|max:255',
            'sp_percentage' => 'required|numeric|min:1|max:100',
            'referrer_code' => 'nullable|string|exists:users,referral_code',
        ]);

        $referrerId = null;
        if ($request->filled('referrer_code')) {
            $referrer = User::where('referral_code', strtoupper($request->referrer_code))->first();
            if ($referrer && $referrer->id !== $user->id) {
                $referrerId = $referrer->id;
            }
        }

        DB::table('sellers')->insert([
            'user_id' => $user->id,
            'referred_by' => $referrerId,
            'store_name' => $request->store_name,
            'sp_percentage' => $request->sp_percentage,
            'status' => 'pending', // Wajib disetujui Admin dulu
            'is_active' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return back()->with('success', 'Pengajuan Seller terkirim! Menunggu persetujuan Admin Swizh.');
    }

    // Seller mengajukan perubahan persentase SP ke Admin
    public function requestSpChange(Request $request)
    {
        $user = Auth::user();
        $seller = DB::table('sellers')->where('user_id', $user->id)->where('status', 'approved')->first();

        if (!$seller) {
            return back()->with('error', 'Toko Anda belum disetujui Admin.');
        }

        $request->validate([
            'requested_sp_percentage' => 'required|numeric|min:1|max:100',
        ]);

        DB::table('sellers')->where('id', $seller->id)->update([
            'requested_sp_percentage' => $request->requested_sp_percentage,
            'updated_at' => now(),
        ]);

        return back()->with('success', 'Permintaan perubahan persentase SP ke ' . $request->requested_sp_percentage . '% telah dikirim ke Admin.');
    }
}
