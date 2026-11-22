<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    public function create(Request $request): View
    {
        $ref = $request->query('ref');
        return view('auth.register', compact('ref'));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'phone' => ['required', 'string', 'min:9', 'max:20', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Password::min(8)->mixedCase()],
            'referral_code' => ['nullable', 'string', 'exists:users,referral_code'],
            'terms' => ['accepted'],
        ], [
            'phone.unique' => 'Nomor HP ini sudah terdaftar di akun lain.',
            'password.min' => 'Password minimal 8 karakter.',
            'password.mixed' => 'Password wajib mengandung kombinasi huruf besar dan huruf kecil.',
            'terms.accepted' => 'Anda wajib menyetujui Syarat & Ketentuan (Terms & Conditions) Swizh.',
        ]);

        // Jika tidak mengisi kode referral, otomatis menginduk ke Admin Swizh (ID 1)
        $referrerId = User::where('is_admin', true)->value('id') ?? 1;

        if ($request->filled('referral_code')) {
            $referrer = User::where('referral_code', strtoupper(trim($request->referral_code)))->first();
            if ($referrer) {
                $referrerId = $referrer->id;
            }
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => preg_replace('/[^0-9+]/', '', $request->phone),
            'password' => Hash::make($request->password),
            'referred_by' => $referrerId,
            'terms_accepted_at' => now(),
        ]);

        event(new Registered($user));
        Auth::login($user);

        return redirect(route('dashboard', absolute: false));
    }
}
