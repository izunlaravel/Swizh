<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Admin Swizh - Seller & Simulasi SP') }}
            </h2>
            <a href="{{ route('dashboard') }}" class="text-sm text-indigo-600 underline">← Ke Dashboard</a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if(session('success'))
                <div class="p-4 bg-green-100 border border-green-400 text-green-800 rounded-md text-sm font-medium">
                    {{ session('success') }}
                </div>
            @endif

            @if($errors->any())
                <div class="p-4 bg-red-100 border border-red-400 text-red-800 rounded-md text-sm">
                    {{ $errors->first() }}
                </div>
            @endif

            <!-- Ringkasan SP Global Bulan Ini -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="bg-white p-5 rounded-lg shadow-sm border-l-4 border-indigo-600">
                    <p class="text-xs text-gray-500 uppercase">Total SP Global ({{ $period }})</p>
                    <p class="text-2xl font-bold text-gray-900 mt-1">{{ number_format($globalSpMonth, 0, ',', '.') }} SP</p>
                </div>
                <div class="bg-white p-5 rounded-lg shadow-sm border-l-4 border-emerald-600">
                    <p class="text-xs text-gray-500 uppercase">Pool Bonus Leader 5% ({{ $period }})</p>
                    <p class="text-2xl font-bold text-emerald-700 mt-1">Rp {{ number_format($leaderPoolRp, 0, ',', '.') }}</p>
                </div>
            </div>

            <!-- Form Tambah Seller Baru -->
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">1. Daftarkan Seller Baru</h3>
                <form method="POST" action="{{ route('admin.sellers.store') }}" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @csrf
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Akun Member Pemilik Toko</label>
                        <select name="user_id" required class="mt-1 block w-full border-gray-300 rounded-md text-sm">
                            @foreach($users as $u)
                                <option value="{{ $u->id }}">{{ $u->name }} ({{ $u->email }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Nama Toko / Seller</label>
                        <input type="text" name="store_name" required placeholder="Contoh: Toko Berkah Swizh" class="mt-1 block w-full border-gray-300 rounded-md text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Persentase SP (% dari Harga Jual)</label>
                        <input type="number" step="0.01" name="sp_percentage" value="10" required class="mt-1 block w-full border-gray-300 rounded-md text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Kode Referral Pengajak Seller (Dapat 0.5%)</label>
                        <input type="text" name="referrer_code" placeholder="Opsional: SWZ-XXXXXX" class="mt-1 block w-full border-gray-300 rounded-md text-sm uppercase">
                    </div>
                    <div class="md:col-span-2">
                        <button type="submit" class="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700">
                            Simpan Seller Baru
                        </button>
                    </div>
                </form>
            </div>

            <!-- Daftar Seller & Ubah Persentase SP -->
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">2. Daftar Seller & Ubah Persentase SP (Khusus Admin)</h3>
                <div class="space-y-4">
                    @forelse($sellers as $seller)
                        <div class="border border-gray-200 rounded-lg p-4 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                            <div>
                                <p class="font-bold text-gray-900">{{ $seller->store_name }}</p>
                                <p class="text-xs text-gray-500">Pemilik: {{ $seller->owner_name }} | Pengajak Seller: {{ $seller->referrer_name ?? 'Tidak ada' }}</p>
                            </div>
                            <form method="POST" action="{{ route('admin.sellers.update', $seller->id) }}" class="flex items-center gap-2">
                                @csrf
                                @method('PATCH')
                                <label class="text-xs font-medium text-gray-600">SP (%):</label>
                                <input type="number" step="0.01" name="sp_percentage" value="{{ $seller->sp_percentage }}" class="w-24 text-sm border-gray-300 rounded-md">
                                <button type="submit" class="px-3 py-2 bg-amber-600 text-white text-xs font-medium rounded-md hover:bg-amber-700">
                                    Update %
                                </button>
                            </form>
                        </div>
                    @empty
                        <p class="text-sm text-gray-500">Belum ada seller terdaftar. Silakan daftarkan pada form di atas.</p>
                    @endforelse
                </div>
            </div>

            <!-- Simulasi Transaksi Belanja -->
            @if($sellers->count() > 0)
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-2">3. Simulasi Transaksi Belanja Member</h3>

```bash
cat << 'EOF' > resources/views/dashboard.blade.php
@php
    $user = Auth::user();
    $period = now()->format('Y-m');
    $spService = app(\App\Services\SwizhPointService::class);
    $stats = $spService->getMemberMonthlyStats($user->id, $period);
    $pendingBonus = (int) \Illuminate\Support\Facades\DB::table('bonus_ledgers')
        ->where('user_id', $user->id)
        ->where('period_month', $period)
        ->where('status', 'pending')
        ->sum('amount_rp');
    $ledgers = \Illuminate\Support\Facades\DB::table('bonus_ledgers')
        ->where('user_id', $user->id)
        ->orderByDesc('id')
        ->limit(10)
        ->get();
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Dashboard Swizh') }}
            </h2>
            @if($user->is_admin)
                <a href="{{ route('admin.sellers.index') }}" class="px-3 py-1.5 bg-amber-600 text-white text-xs font-semibold rounded-md hover:bg-amber-700">
                    ⚙ Panel Admin Seller
                </a>
            @endif
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <!-- Kartu Saldo & Bonus Pending -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 border-l-4 border-green-500">
                    <p class="text-xs text-gray-500 uppercase">Saldo Wallet Aktif (1 SP = Rp 1)</p>
                    <p class="text-2xl font-bold text-gray-900 mt-1">Rp {{ number_format($user->wallet_balance, 0, ',', '.') }}</p>
                    <p class="text-xs text-green-600 mt-1">● Siap dicairkan / digunakan</p>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 border-l-4 border-amber-500">
                    <p class="text-xs text-gray-500 uppercase">Bonus Pending Bulan Ini ({{ $period }})</p>
                    <p class="text-2xl font-bold text-amber-600 mt-1">Rp {{ number_format($pendingBonus, 0, ',', '.') }}</p>
                    <p class="text-xs text-gray-500 mt-1">
                        @if($stats['personal_sp'] >= 1)
                            <span class="text-green-600 font-medium">✓ Syarat minimal 1 SP pribadi bulan ini terpenuhi!</span>
                        @else
                            Belanja minimal 1 SP sebelum tgl 1 bulan depan untuk mencairkan.
                        @endif
                    </p>
                </div>
            </div>

            <!-- Kartu Link Referral -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <div class="flex items-center justify-between mb-3">
                    <div>
                        <p class="text-xs text-gray-500">Status Keanggotaan</p>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-green-100 text-green-800">
                            ● Member Aktif
                        </span>
                    </div>
                    <div class="text-right">
                        <p class="text-xs text-gray-500">Kode Referral</p>
                        <p class="text-lg font-bold text-indigo-600">{{ $user->referral_code }}</p>
                    </div>
                </div>

                <div class="mt-3 pt-3 border-t border-gray-100">
                    <label class="block text-xs font-medium text-gray-700 mb-1">Link Undangan Referral (1 Level)</label>
                    <div class="flex gap-2">
                        <input type="text" id="refLink" readonly value="{{ url('/register?ref=' . $user->referral_code) }}" class="w-full text-xs border-gray-300 rounded-md bg-gray-50 text-gray-600">
                        <button onclick="navigator.clipboard.writeText(document.getElementById('refLink').value); alert('Link referral berhasil disalin!');" class="px-4 py-2 bg-indigo-600 text-white text-xs font-medium rounded-md hover:bg-indigo-700 shrink-0">
                            Salin
                        </button>
                    </div>
                </div>
            </div>

            <!-- Kartu Progres Kualifikasi Leader Bulanan -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-base font-semibold text-gray-900">Progres Leader ({{ $period }})</h3>
                    @if($stats['is_leader_qualified'])
                        <span class="px-2.5 py-1 bg-indigo-100 text-indigo-800 text-xs font-bold rounded-full">★ Kualifikasi Leader</span>
                    @else
                        <span class="px-2.5 py-1 bg-gray-100 text-gray-600 text-xs rounded-full">Belum Leader</span>
                    @endif
                </div>

                <div class="grid grid-cols-3 gap-3 text-center">
                    <div class="p-3 bg-gray-50 rounded-lg">
                        <p class="text-xs text-gray-500">SP Pribadi</p>
                        <p class="text-sm font-bold {{ $stats['personal_sp'] >= 1 ? 'text-green-600' : 'text-gray-800' }}">
                            {{ number_format($stats['personal_sp'], 0, ',', '.') }} / 1
                        </p>
                    </div>
                    <div class="p-3 bg-gray-50 rounded-lg">
                        <p class="text-xs text-gray-500">Ref Aktif</p>
                        <p class="text-sm font-bold {{ $stats['active_referrals'] >= 10 ? 'text-green-600' : 'text-gray-800' }}">
                            {{ $stats['active_referrals'] }} / 10
                        </p>
                    </div>
                    <div class="p-3 bg-gray-50 rounded-lg">
                        <p class="text-xs text-gray-500">SP Grup</p>
                        <p class="text-sm font-bold {{ $stats['group_sp'] >= 10000000 ? 'text-green-600' : 'text-gray-800' }}">
                            {{ number_format($stats['group_sp'], 0, ',', '.') }} / 10 Jt
                        </p>
                    </div>
                </div>
            </div>

            <!-- Riwayat Bonus -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-base font-semibold text-gray-900 mb-3">Mutasi Bonus Terbaru</h3>
                @forelse($ledgers as $item)
                    <div class="py-2.5 border-b border-gray-100 flex justify-between items-center text-sm">
                        <div>
                            <p class="font-medium text-gray-800">{{ $item->notes }}</p>
                            <p class="text-xs text-gray-400">{{ \Carbon\Carbon::parse($item->created_at)->format('d M Y, H:i') }} WIB</p>
                        </div>
                        <div class="text-right">
                            <p class="font-bold {{ $item->status === 'approved' ? 'text-green-600' : 'text-amber-600' }}">
                                +Rp {{ number_format($item->amount_rp, 0, ',', '.') }}
                            </p>
                            <span class="text-[10px] uppercase px-1.5 py-0.5 rounded {{ $item->status === 'approved' ? 'bg-green-50 text-green-700' : 'bg-amber-50 text-amber-700' }}">
                                {{ $item->status }}
                            </span>
                        </div>
                    </div>
                @empty
                    <p class="text-xs text-gray-500">Belum ada riwayat bonus masuk.</p>
                @endforelse
            </div>
        </div>
    </div>
</x-app-layout>
