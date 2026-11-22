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
            return back()->with('popup_error', 'Anda sudah memiliki pengajuan atau toko terdaftar.');
        }

        $request->validate([
            'store_name' => 'required|string|max:255',
            'category' => 'required|string|max:100',
            'custom_category' => 'nullable|string|max:100',
            'address' => 'required|string|max:500',
            'sp_percentage' => 'required|numeric|min:5', // Minimal 5%, maksimal unlimited
            'referrer_code' => 'nullable|string|exists:users,referral_code',
            'bank_info' => 'nullable|string|max:255',
        ], [
            'sp_percentage.min' => 'Ketentuan pemberian poin (SP) minimal adalah 5%.',
        ]);

        $finalCategory = ($request->category === 'Lainnya' && $request->filled('custom_category'))
            ? trim($request->custom_category)
            : $request->category;

        $referrerId = null;
        if ($request->filled('referrer_code')) {
            $referrer = User::where('referral_code', strtoupper(trim($request->referrer_code)))->first();
            if ($referrer && $referrer->id !== $user->id) {
                $referrerId = $referrer->id;
            }
        }

        DB::table('sellers')->insert([
            'user_id' => $user->id,
            'referred_by' => $referrerId,
            'store_name' => $request->store_name,
            'category' => $finalCategory,
            'address' => $request->address,
            'sp_percentage' => $request->sp_percentage,
            'bank_info' => $request->bank_info,
            'status' => 'pending',
            'is_active' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return back()->with('popup_success', 'Pengajuan Seller terkirim! Menunggu persetujuan Admin Swizh.');
    }

    public function requestSpChange(Request $request)
    {
        $seller = DB::table('sellers')->where('user_id', Auth::id())->where('status', 'approved')->first();
        if (!$seller) return back()->with('popup_error', 'Hanya Seller aktif yang dapat mengajukan perubahan SP.');

        $request->validate(['requested_sp_percentage' => 'required|numeric|min:5'], [
            'requested_sp_percentage.min' => 'Minimal persentase SP adalah 5%.',
        ]);

        DB::table('sellers')->where('id', $seller->id)->update([
            'requested_sp_percentage' => $request->requested_sp_percentage,
            'updated_at' => now(),
        ]);

        return back()->with('popup_success', 'Permintaan perubahan SP ke ' . $request->requested_sp_percentage . '% telah dikirim ke Admin.');
    }

    public function updateSellerProfile(Request $request)
    {
        $seller = DB::table('sellers')->where('user_id', Auth::id())->firstOrFail();
        $request->validate([
            'address' => 'required|string|max:500',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'bank_info' => 'nullable|string|max:255',
            'requested_non_sp_notes' => 'nullable|string|max:1000',
        ]);

        $updateData = [
            'address' => $request->address,
            'bank_info' => $request->bank_info,
            'updated_at' => now(),
        ];

        if ($request->filled('latitude') && $request->filled('longitude')) {
            $updateData['latitude'] = $request->latitude;
            $updateData['longitude'] = $request->longitude;
        }

        // Jika ada perubahan catatan produk Non-SP, simpan ke kolom pengajuan approval Admin
        if ($request->filled('requested_non_sp_notes') && $request->requested_non_sp_notes !== $seller->non_sp_notes) {
            $updateData['requested_non_sp_notes'] = $request->requested_non_sp_notes;
        }

        DB::table('sellers')->where('id', $seller->id)->update($updateData);

        return back()->with('popup_success', 'Profil & alamat toko berhasil diperbarui! (Perubahan produk Non-SP menunggu persetujuan Admin).');
    }

    public function uploadProduct(Request $request)
    {
        $seller = DB::table('sellers')->where('user_id', Auth::id())->where('status', 'approved')->firstOrFail();
        $count = DB::table('seller_products')->where('seller_id', $seller->id)->count();

        if ($count >= 5) {
            return back()->with('popup_error', 'Maksimal 5 foto produk unggulan per Seller.');
        }

        $request->validate([
            'product_name' => 'required|string|max:150',
            'product_type' => 'required|string|max:100',
            'price_rp' => 'required|integer|min:0',
            'photo' => 'required|image|mimes:jpg,jpeg,png,webp|max:5120', // Maks 5MB
        ], [
            'photo.max' => 'Ukuran foto produk maksimal 5 MB.',
        ]);

        $file = $request->file('photo');
        $filename = 'prod-' . $seller->id . '-' . time() . '.' . $file->getClientOriginalExtension();
        $file->move(public_path('images/products'), $filename);

        DB::table('seller_products')->insert([
            'seller_id' => $seller->id,
            'product_name' => $request->product_name,
            'product_type' => $request->product_type,
            'price_rp' => $request->price_rp,
            'photo_path' => 'images/products/' . $filename,
            'is_sp_eligible' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return back()->with('popup_success', 'Foto produk unggulan berhasil ditambahkan!');
    }

    public function deleteProduct(int $id)
    {
        $seller = DB::table('sellers')->where('user_id', Auth::id())->firstOrFail();
        $prod = DB::table('seller_products')->where('id', $id)->where('seller_id', $seller->id)->firstOrFail();
        if (file_exists(public_path($prod->photo_path))) @unlink(public_path($prod->photo_path));
        DB::table('seller_products')->where('id', $id)->delete();
        return back()->with('popup_success', 'Produk berhasil dihapus.');
    }

    public function topupCashDeposit(Request $request)
    {
        $seller = DB::table('sellers')->where('user_id', Auth::id())->where('status', 'approved')->firstOrFail();
        $request->validate(['amount_rp' => 'required|integer|min:100000'], [
            'amount_rp.min' => 'Minimal pengisian deposit transaksi tunai adalah Rp 100.000.',
        ]);

        DB::table('sellers')->where('id', $seller->id)->increment('cash_deposit_balance', $request->amount_rp);
        return back()->with('popup_success', 'Deposit Tunai (Cash) bertambah Rp ' . number_format($request->amount_rp, 0, ',', '.') . '!');
    }

    public function postReview(Request $request, int $sellerId)
    {
        $request->validate(['comment' => 'nullable|string|max:500']);
        DB::table('seller_reviews')->insert([
            'seller_id' => $sellerId,
            'user_id' => Auth::id(),
            'is_like' => true,
            'comment' => $request->comment,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        return back()->with('popup_success', 'Terima kasih atas Like & Komentar Anda!');
    }

    public function sendInbox(Request $request)
    {
        $request->validate(['subject' => 'required|string|max:150', 'message' => 'required|string|max:1000']);
        DB::table('feedback_inboxes')->insert([
            'user_id' => Auth::id(),
            'sender_role' => Auth::user()->is_seller ? 'seller' : 'member',
            'subject' => $request->subject,
            'message' => $request->message,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        return back()->with('popup_success', 'Saran/pesan Anda berhasil dikirim ke Inbox Admin Swizh!');
    }

    public function updateProfilePhoto(Request $request)
    {
        $request->validate(['photo' => 'required|image|mimes:jpg,jpeg,png,webp|max:2048']);
        $user = Auth::user();
        $file = $request->file('photo');
        $filename = 'user-' . $user->id . '-' . time() . '.' . $file->getClientOriginalExtension();
        $file->move(public_path('images/avatars'), $filename);
        if ($user->profile_photo && file_exists(public_path($user->profile_photo))) @unlink(public_path($user->profile_photo));
        DB::table('users')->where('id', $user->id)->update(['profile_photo' => 'images/avatars/' . $filename, 'updated_at' => now()]);
        return back()->with('popup_success', 'Foto profil berhasil diperbarui!');
    }
}
