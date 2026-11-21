<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class MemberSellerController extends Controller
{
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
            'status' => 'pending',
            'is_active' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return back()->with('success', 'Pengajuan Seller terkirim! Menunggu persetujuan Admin Swizh.');
    }

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

    public function updateLocation(Request $request)
    {
        $request->validate([
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
        ]);

        DB::table('sellers')->where('user_id', Auth::id())->update([
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'updated_at' => now(),
        ]);

        return back()->with('success', 'Lokasi GPS toko berhasil diperbarui!');
    }

    public function updateProfilePhoto(Request $request)
    {
        $request->validate([
            "photo" => "required|image|mimes:jpg,jpeg,png,webp|max:2048",
        ], [
            "photo.max" => "Ukuran foto profil maksimal 2 MB.",
            "photo.image" => "File harus berupa gambar (JPG, PNG, atau WEBP).",
        ]);

        $user = Auth::user();
        $file = $request->file("photo");
        $filename = "user-" . $user->id . "-" . time() . "." . $file->getClientOriginalExtension();
        $file->move(public_path("images/avatars"), $filename);

        if ($user->profile_photo && file_exists(public_path($user->profile_photo))) {
            @unlink(public_path($user->profile_photo));
        }

        DB::table("users")->where("id", $user->id)->update([
            "profile_photo" => "images/avatars/" . $filename,
            "updated_at" => now(),
        ]);

        return back()->with("success", "Foto profil berhasil diperbarui!");
    }
}
