<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Panel Persetujuan Admin Swizh</h2>
            <a href="{{ route('dashboard') }}" class="text-sm text-indigo-600 underline">← Ke Dashboard</a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if(session('success'))
                <div class="p-4 bg-green-100 border border-green-400 text-green-800 rounded-md text-sm font-medium">{{ session('success') }}</div>
            @endif

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="bg-white p-5 rounded-lg shadow-sm border-l-4 border-indigo-600">
                    <p class="text-xs text-gray-500 uppercase">Total SP Global ({{ $period }})</p>
                    <p class="text-2xl font-bold text-gray-900 mt-1">{{ number_format($globalSpMonth, 0, ',', '.') }} SP</p>
                </div>
                <div class="bg-white p-5 rounded-lg shadow-sm border-l-4 border-green-600">
                    <p class="text-xs text-gray-500 uppercase">Pool Bonus Leader 5% ({{ $period }})</p>
                    <p class="text-2xl font-bold text-green-700 mt-1">Rp {{ number_format($leaderPoolRp, 0, ',', '.') }}</p>
                </div>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg p-6 border-l-4 border-purple-600 flex flex-col md:flex-row justify-between items-center gap-4"><div><h3 class="font-semibold text-gray-900">Tutup Buku Bulanan Otomatis (Tgl 1 Pukul 00:05 WIB)</h3><p class="text-xs text-gray-500">Menghanguskan bonus pending, membagi Pool Leader 5%, dan menghitung pemenang Reward 10%.</p></div><form method="POST" action="{{ route('admin.closing.run') }}" onsubmit="return confirm('Yakin jalankan tutup buku untuk periode bulan berjalan?')">@csrf<button type="submit" style="background-color:#7c3aed;color:#fff;" class="px-4 py-2 text-xs font-bold rounded-md">🔒 Jalankan Tutup Buku Sekarang</button></form></div>

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Daftar Seller & Persetujuan Admin</h3>
                <div class="space-y-4">
                    @forelse($sellers as $seller)
                        <div class="border border-gray-200 rounded-lg p-4 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                            <div>
                                <div class="flex items-center gap-2">
                                    <p class="font-bold text-gray-900">{{ $seller->store_name }}</p>
                                    @if($seller->status === 'pending')
                                        <span class="px-2 py-0.5 text-[10px] font-bold bg-yellow-100 text-yellow-800 rounded">MENUNGGU APPROVAL</span>
                                    @else
                                        <span class="px-2 py-0.5 text-[10px] font-bold bg-green-100 text-green-800 rounded">AKTIF</span>
                                    @endif
                                </div>
                                <p class="text-xs text-gray-500 mt-1">Pemilik: {{ $seller->owner_name }} | Pengajak: {{ $seller->referrer_name ?? '-' }} | SP Saat Ini: <strong>{{ $seller->sp_percentage }}%</strong></p>
                                @if($seller->requested_sp_percentage)
                                    <p class="text-xs font-semibold text-indigo-600 mt-1">🔔 Mengajukan perubahan persentase SP menjadi: {{ $seller->requested_sp_percentage }}%</p>
                                @endif
                            </div>

                            <div class="flex flex-wrap items-center gap-2">
                                @if($seller->status === 'pending')
                                    <form method="POST" action="{{ route('admin.sellers.approve', $seller->id) }}">
                                        @csrf
                                        <button type="submit" style="background-color:#16a34a; color:#ffffff;" class="px-3 py-2 text-xs font-bold rounded-md">
                                            ✓ Setujui Jadi Seller
                                        </button>
                                    </form>
                                @endif

                                <form method="POST" action="{{ route('admin.sellers.approve_sp', $seller->id) }}" class="flex items-center gap-2">
                                    @csrf
                                    @method('PATCH')
                                    <input type="number" step="0.01" name="sp_percentage" value="{{ $seller->requested_sp_percentage ?? $seller->sp_percentage }}" class="w-24 text-sm border-gray-300 rounded-md">
                                    <button type="submit" style="background-color:#d97706; color:#ffffff;" class="px-3 py-2 text-xs font-bold rounded-md">
                                        {{ $seller->requested_sp_percentage ? 'Setujui % Baru' : 'Ubah %' }}
                                    </button>
                                </form>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-gray-500">Belum ada pengajuan Seller.</p>
                    @endforelse
                </div>
            </div>

            @if($approvedSellers->count() > 0)
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-3">Simulasi Transaksi Belanja Member</h3>
                <form method="POST" action="{{ route('admin.sellers.simulate') }}" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    @csrf
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Pembeli</label>
                        <select name="buyer_id" class="mt-1 block w-full border-gray-300 rounded-md text-sm">
                            @foreach($users as $u)
                                <option value="{{ $u->id }}">{{ $u->name }} (Rp {{ number_format($u->wallet_balance, 0, ',', '.') }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Belanja di Seller Aktif</label>
                        <select name="seller_id" class="mt-1 block w-full border-gray-300 rounded-md text-sm">
                            @foreach($approvedSellers as $s)
                                <option value="{{ $s->id }}">{{ $s->store_name }} ({{ $s->sp_percentage }}%)</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Nominal Belanja (Rp)</label>
                        <input type="number" name="amount_rp" value="100000" required class="mt-1 block w-full border-gray-300 rounded-md text-sm">
                    </div>
                    <div class="md:col-span-3">
                        <button type="submit" style="background-color:#16a34a; color:#ffffff;" class="px-4 py-2 text-sm font-bold rounded-md">Proses Transaksi</button>
                    </div>
                </form>
            </div>
            @endif
        </div>
    </div>
</x-app-layout>
