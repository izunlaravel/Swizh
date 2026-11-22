<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Tenant / Seller Terdekat</h2>
            <a href="{{ route('dashboard') }}" class="text-sm text-indigo-600 underline">← Kembali</a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-5">
            @if(session('popup_success'))
                <div class="p-4 bg-green-100 border border-green-400 text-green-800 rounded-md text-sm font-semibold">{{ session('popup_success') }}</div>
            @endif

            <div class="bg-white shadow-sm sm:rounded-lg p-5">
                <form method="GET" action="{{ route('sellers.nearby') }}" class="grid grid-cols-1 sm:grid-cols-3 gap-2">
                    <input type="hidden" name="lat" value="{{ $lat }}">
                    <input type="hidden" name="lng" value="{{ $lng }}">
                    <select name="category" onchange="this.form.submit()" class="sm:col-span-2 text-sm border-gray-300 rounded-md">
                        <option value="Semua">-- Semua Kategori Usaha --</option>
                        @foreach(['Makanan/Minuman','Toko Kelontong','Service HP','Service Laptop','Bengkel','Showroom Mobil','Showroom Motor','Toko Sepeda','Toko Mainan','Toko Pakaian','Tukang Pangkas'] as $cat)
                            <option value="{{ $cat }}" {{ $category === $cat ? 'selected' : '' }}>{{ $cat }}</option>
                        @endforeach
                    </select>
                    <button type="button" onclick="detectMyLocation()" style="background-color:#0d9488;color:#fff;" class="px-3 py-2 text-xs font-bold rounded-md">📍 Deteksi GPS Saya</button>
                </form>
            </div>

            @forelse($sellers as $seller)
                <div class="bg-white shadow-sm sm:rounded-lg p-5 space-y-4 border-l-4 border-indigo-600">
                    <div class="flex flex-col sm:flex-row justify-between gap-3">
                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <h3 class="font-bold text-gray-900 text-lg">{{ $seller->store_name }}</h3>
                                <span class="px-2 py-0.5 text-[11px] font-bold bg-indigo-100 text-indigo-800 rounded">{{ $seller->category }}</span>
                                <span class="px-2 py-0.5 text-[11px] font-bold bg-green-100 text-green-800 rounded">SP {{ $seller->sp_percentage }}%</span>
                                @if($seller->cash_deposit_balance >= 100000)
                                    <span class="px-2 py-0.5 text-[11px] font-bold bg-amber-100 text-amber-800 rounded">💵 Terima Tunai/Cash</span>
                                @endif
                            </div>
                            <p class="text-xs text-gray-600 mt-1">{{ $seller->address }}</p>
                            @if(isset($seller->distance_km))
                                <p class="text-xs font-bold text-teal-700 mt-1">📏 Jarak: {{ $seller->distance_km < 1 ? round($seller->distance_km * 1000) . ' m' : number_format($seller->distance_km, 2, ',', '.') . ' km' }}</p>
                            @endif
                            @if($seller->non_sp_notes)
                                <p class="text-[11px] text-red-600 mt-1 bg-red-50 p-2 rounded"><strong>Catatan Non-SP:</strong> {{ $seller->non_sp_notes }}</p>
                            @endif
                        </div>

                        <div class="flex sm:flex-col gap-2 shrink-0">
                            @if($seller->latitude && $seller->longitude)
                                <a href="geo:{{ $seller->latitude }},{{ $seller->longitude }}?q={{ $seller->latitude }},{{ $seller->longitude }}({{ urlencode($seller->store_name) }})" rel="noopener noreferrer" style="background-color:#4b5563;color:#fff;" class="px-3 py-2 text-xs font-semibold rounded-md text-center">🗺 Maps</a>
                            @endif
                            <a href="{{ route('qris.scan', ['store_code' => 'SWZ-STORE-' . $seller->id]) }}" style="background-color:#2563eb;color:#fff;" class="px-3 py-2 text-xs font-bold rounded-md text-center">📷 Bayar</a>
                        </div>
                    </div>

                    @if($seller->products->count() > 0)
                        <div class="grid grid-cols-2 sm:grid-cols-5 gap-2 pt-2 border-t">
                            @foreach($seller->products as $prod)
                                <div class="border rounded p-1.5 text-center bg-gray-50">
                                    <img src="{{ asset($prod->photo_path) }}" class="w-full h-20 object-cover rounded">
                                    <p class="text-[11px] font-bold truncate mt-1">{{ $prod->product_name }}</p>
                                    <p class="text-[10px] text-gray-500">Rp {{ number_format($prod->price_rp, 0, ',', '.') }}</p>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    <div class="pt-3 border-t space-y-2">
                        <form method="POST" action="{{ route('seller.review', $seller->id) }}" class="flex gap-2">
                            @csrf
                            <input type="text" name="comment" placeholder="Tulis komentar & beri Like..." class="w-full text-xs border-gray-300 rounded-md">
                            <button type="submit" style="background-color:#ec4899;color:#fff;" class="px-3 py-1.5 text-xs font-bold rounded-md shrink-0">❤ Like ({{ $seller->likes_count }})</button>
                        </form>
                        @foreach($seller->comments as $c)
                            <p class="text-xs text-gray-600 bg-gray-50 px-2.5 py-1 rounded"><strong>{{ $c->name }}:</strong> {{ $c->comment }}</p>
                        @endforeach
                    </div>
                </div>
            @empty
                <div class="bg-white p-6 rounded-lg text-center text-sm text-gray-500">Tidak ada tenant ditemukan pada kategori ini.</div>
            @endforelse
        </div>
    </div>

    <script>
        function detectMyLocation() {
            if (!navigator.geolocation) return alert('GPS tidak didukung.');
            navigator.geolocation.getCurrentPosition(pos => {
                window.location.href = "{{ route('sellers.nearby') }}?category={{ urlencode($category ?? 'Semua') }}&lat=" + pos.coords.latitude + "&lng=" + pos.coords.longitude;
            });
        }
    </script>
</x-app-layout>
