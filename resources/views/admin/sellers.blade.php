<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Admin Swizh - Seller & Simulasi SP</h2>
            <a href="{{ route('dashboard') }}" class="text-sm text-indigo-600 underline">← Ke Dashboard</a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if(session('success'))
                <div class="p-4 bg-green-100 border border-green-400 text-green-800 rounded-md text-sm font-medium">{{ session('success') }}</div>
            @endif
            @if($errors->any())
                <div class="p-4 bg-red-100 border border-red-400 text-red-800 rounded-md text-sm">{{ $errors->first() }}</div>
            @endif

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
                        <label class="block text-sm font-medium text-gray-700">Persentase SP (%)</label>
                        <input type="number" step="0.01" name="sp_percentage" value="10" required class="mt-1 block w-full border-gray-300 rounded-md text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Kode Referral Pengajak Seller (Opsional - 0.5%)</label>
                        <input type="text" name="referrer_code" placeholder="SWZ-XXXXXX" class="mt-1 block w-full border-gray-300 rounded-md text-sm uppercase">
                    </div>
                    <div class="md:col-span-2">
                        <button type="submit" class="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md">Simpan Seller Baru</button>
                    </div>
                </form>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">2. Daftar Seller & Ubah Persentase SP</h3>
                <div class="space-y-4">
                    @forelse($sellers as $seller)
                        <div class="border border-gray-200 rounded-lg p-4 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                            <div>
                                <p class="font-bold text-gray-900">{{ $seller->store_name }}</p>
                                <p class="text-xs text-gray-500">Pemilik: {{ $seller->owner_name }} | Pengajak: {{ $seller->referrer_name ?? '-' }}</p>
                            </div>
                            <form method="POST" action="{{ route('admin.sellers.update', $seller->id) }}" class="flex items-center gap-2">
                                @csrf
                                @method('PATCH')
                                <label class="text-xs text-gray-600">SP (%):</label>
                                <input type="number" step="0.01" name="sp_percentage" value="{{ $seller->sp_percentage }}" class="w-24 text-sm border-gray-300 rounded-md">
                                <button type="submit" class="px-3 py-2 bg-amber-600 text-white text-xs font-medium rounded-md">Update %</button>
                            </form>
                        </div>
                    @empty
                        <p class="text-sm text-gray-500">Belum ada seller terdaftar.</p>
                    @endforelse
                </div>
            </div>

            @if($sellers->count() > 0)
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-3">3. Simulasi Transaksi Belanja Member</h3>
                <form method="POST" action="{{ route('admin.sellers.simulate') }}" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    @csrf
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Pembeli (Member)</label>
                        <select name="buyer_id" class="mt-1 block w-full border-gray-300 rounded-md text-sm">
                            @foreach($users as $u)
                                <option value="{{ $u->id }}">{{ $u->name }} (Rp {{ number_format($u->wallet_balance, 0, ',', '.') }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Belanja di Seller</label>
                        <select name="seller_id" class="mt-1 block w-full border-gray-300 rounded-md text-sm">
                            @foreach($sellers as $s)
                                <option value="{{ $s->id }}">{{ $s->store_name }} ({{ $s->sp_percentage }}%)</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Nominal Belanja (Rp)</label>
                        <input type="number" name="amount_rp" value="100000" required class="mt-1 block w-full border-gray-300 rounded-md text-sm">
                    </div>
                    <div class="md:col-span-3">
                        <button type="submit" class="px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-md">Proses Transaksi & Bagikan Bonus</button>
                    </div>
                </form>
            </div>
            @endif
        </div>
    </div>
</x-app-layout>
