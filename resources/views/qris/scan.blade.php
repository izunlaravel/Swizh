<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Scan QRIS & Bayar</h2>
            <a href="{{ route('dashboard') }}" class="text-sm text-indigo-600 underline">← Kembali</a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if(session('error'))
                <div class="p-4 bg-red-100 border border-red-400 text-red-800 rounded-md text-sm">{{ session('error') }}</div>
            @endif

            <!-- Kamera Scanner QR -->
            <div class="bg-white shadow-sm sm:rounded-lg p-6 text-center">
                <h3 class="font-semibold text-gray-900 mb-2">Scan QR Seller Swizh</h3>
                <p id="camStatus" class="text-xs text-gray-500 mb-3">Klik tombol di bawah untuk mengaktifkan kamera belakang HP</p>
                
                <button type="button" id="btnStartCam" onclick="startCamera()" style="background-color:#2563eb; color:#ffffff;" class="px-4 py-2 text-xs font-bold rounded-md shadow mb-3">
                    📷 Buka Kamera HP
                </button>

                <div id="reader" class="mx-auto overflow-hidden rounded-lg bg-black" style="width: 100%; max-width: 300px; min-height: 220px;"></div>
            </div>

            <!-- Form Konfirmasi Pembayaran -->
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="font-semibold text-gray-900 mb-4">Detail Pembayaran</h3>
                <form method="POST" action="{{ route('qris.pay') }}" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-medium text-gray-700">Kode QR Toko (Hasil Scan / Pilih Manual)</label>
                        <input type="text" id="store_code" name="store_code" value="{{ $selectedSeller ? 'SWZ-STORE-' . $selectedSeller->id : '' }}" required placeholder="Contoh: SWZ-STORE-1" class="mt-1 block w-full border-gray-300 rounded-md text-sm uppercase font-bold text-indigo-700">
                    </div>

                    @if($sellers->count() > 0)
                    <div>
                        <label class="block text-xs text-gray-500">Atau pilih dari daftar Seller aktif (jika uji coba 1 HP):</label>
                        <select onchange="document.getElementById('store_code').value = this.value" class="mt-1 block w-full border-gray-300 rounded-md text-xs">
                            <option value="">-- Pilih Seller --</option>
                            @foreach($sellers as $s)
                                <option value="SWZ-STORE-{{ $s->id }}">{{ $s->store_name }} (Kode: SWZ-STORE-{{ $s->id }} | SP {{ $s->sp_percentage }}%)</option>
                            @endforeach
                        </select>
                    </div>
                    @endif

                    <div>
                        <label class="block text-xs font-medium text-gray-700">Nominal Pembayaran (Rp)</label>
                        <input type="number" name="amount_rp" min="1000" value="50000" required class="mt-1 block w-full border-gray-300 rounded-md text-lg font-bold">
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-700">Metode Pembayaran</label>
                        <select name="payment_method" class="mt-1 block w-full border-gray-300 rounded-md text-sm">
                            <option value="qris_gateway">QRIS All Payment (Dana / OVO / GoPay / M-Banking)</option>
                            <option value="wallet_balance">Potong Saldo Wallet Swizh (Rp {{ number_format(Auth::user()->wallet_balance, 0, ',', '.') }})</option>
                        </select>
                    </div>

                    <button type="submit" style="background-color:#16a34a; color:#ffffff;" class="w-full py-3 text-sm font-bold rounded-md shadow">
                        Konfirmasi & Bayar Sekarang
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script src="https://unpkg.com" type="text/javascript"></script>
    <script>
        let html5QrCode;

        function startCamera() {
            const statusEl = document.getElementById('camStatus');
            statusEl.innerText = "Meminta izin kamera...";
            
            html5QrCode = new Html5Qrcode("reader");
            html5QrCode.start(
                { facingMode: "environment" },
                { fps: 10, qrbox: { width: 200, height: 200 } },
                (decodedText) => {
                    let code = decodedText;
                    if (decodedText.includes('store_code=')) {
                        code = decodedText.split('store_code=')[1].split('&')[0];
                    }
                    document.getElementById('store_code').value = code.toUpperCase();
                    statusEl.innerText = "✓ QR Terdeteksi: " + code.toUpperCase();
                    html5QrCode.stop();
                    alert('QR Toko Terdeteksi: ' + code.toUpperCase());
                },
                (errorMessage) => { /* Abaikan frame kosong saat mencari QR */ }
            ).then(() => {
                statusEl.innerText = "Kamera aktif. Arahkan ke QR Code Seller.";
                document.getElementById('btnStartCam').style.display = 'none';
            }).catch((err) => {
                statusEl.innerText = "Kamera gagal dibuka (" + err + "). Pastikan izin kamera Chrome diizinkan.";
            });
        }
    </script>
</x-app-layout>
