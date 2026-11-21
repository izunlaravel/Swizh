<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Tenant / Seller Terdekat</h2>
            <a href="{{ route('dashboard') }}" class="text-sm text-indigo-600 underline">← Kembali</a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <!-- Tombol Deteksi GPS Member -->
            <div class="bg-white shadow-sm sm:rounded-lg p-5 flex flex-col sm:flex-row justify-between items-center gap-3">
                <div>
                    <p class="font-semibold text-gray-900 text-sm">Urutkan Berdasarkan Lokasi Anda</p>
                    <p id="gpsStatus" class="text-xs text-gray-500">
                        {{ ($lat && $lng) ? "Menampilkan tenant terdekat dari posisi GPS Anda." : "Aktifkan GPS untuk melihat jarak toko dalam kilometer." }}
                    </p>
                </div>
                <button type="button" onclick="detectMyLocation()" style="background-color:#0d9488;color:#ffffff;" class="px-4 py-2 text-xs font-bold rounded-md shadow shrink-0">
                    📍 Deteksi Lokasi Saya
                </button>
            </div>

            <!-- Daftar Tenant / Seller -->
            <div class="space-y-3">
                @forelse($sellers as $seller)
                    <div class="bg-white shadow-sm sm:rounded-lg p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-l-4 border-indigo-500">
                        <div>
                            <div class="flex items-center gap-2">
                                <h3 class="font-bold text-gray-900 text-base">{{ $seller->store_name }}</h3>
                                <span class="px-2 py-0.5 text-[11px] font-bold bg-green-100 text-green-800 rounded">
                                    SP {{ $seller->sp_percentage }}%
                                </span>
                            </div>

                            @if(isset($seller->distance_km))
                                <p class="text-xs font-semibold text-teal-700 mt-1">
                                    📏 Jarak: {{ $seller->distance_km < 1 ? round($seller->distance_km * 1000) . ' meter' : number_format($seller->distance_km, 2, ',', '.') . ' km' }} dari lokasi Anda
                                </p>
                            @elseif($seller->latitude && $seller->longitude)
                                <p class="text-xs text-gray-500 mt-1">📍 Koordinat GPS toko tersedia</p>
                            @else
                                <p class="text-xs text-gray-400 mt-1">Lokasi GPS toko belum diatur Seller</p>
                            @endif
                        </div>

                        <div class="flex items-center gap-2">
                            @if($seller->latitude && $seller->longitude)
                                <a href="https://google.com{{ $seller->latitude }},{{ $seller->longitude }}" style="background-color:#4b5563;color:#ffffff;" class="px-3 py-2 text-xs font-semibold rounded-md">
                                    🗺 Rute Maps
                                </a>
                            @endif
                            <a href="{{ route('qris.scan', ['store_code' => 'SWZ-STORE-' . $seller->id]) }}" style="background-color:#2563eb;color:#ffffff;" class="px-3 py-2 text-xs font-bold rounded-md">
                                📷 Bayar QRIS
                            </a>
                        </div>
                    </div>
                @empty
                    <div class="bg-white p-6 rounded-lg shadow-sm text-center text-sm text-gray-500">
                        Belum ada tenant/seller yang mengatur lokasi GPS-nya.
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    <script>
        function detectMyLocation() {
            const statusEl = document.getElementById('gpsStatus');
            if (!navigator.geolocation) {
                alert('Browser Anda tidak mendukung GPS.');
                return;
            }
            statusEl.innerText = 'Mengambil koordinat GPS Anda...';
            navigator.geolocation.getCurrentPosition(
                function(pos) {
                    window.location.href = "{{ route('sellers.nearby') }}?lat=" + pos.coords.latitude + "&lng=" + pos.coords.longitude;
                },
                function(err) {
                    statusEl.innerText = 'Gagal membaca GPS: ' + err.message;
                },
                { enableHighAccuracy: true }
            );
        }

        // Otomatis deteksi GPS saat pertama kali halaman dibuka jika belum ada parameter lat/lng
        @if(!$lat || !$lng)
            window.addEventListener('load', function() {
                if (navigator.geolocation) {
                    navigator.geolocation.getCurrentPosition(function(pos) {
                        window.location.href = "{{ route('sellers.nearby') }}?lat=" + pos.coords.latitude + "&lng=" + pos.coords.longitude;
                    });
                }
            });
        @endif
    </script>
</x-app-layout>
