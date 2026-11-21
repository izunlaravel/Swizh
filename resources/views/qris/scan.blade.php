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
                <p id="camStatus" class="text-xs text-gray-500 mb-3">Tekan tombol biru di bawah untuk membuka kamera belakang</p>
                
                <div class="flex justify-center gap-2 mb-3">
                    <button type="button" id="btnStartCam" onclick="openBackCamera()" style="background-color:#2563eb; color:#ffffff;" class="px-4 py-2 text-xs font-bold rounded-md shadow">
                        📷 Buka Kamera Belakang
                    </button>
                    <label style="background-color:#4b5563; color:#ffffff;" class="px-4 py-2 text-xs font-bold rounded-md shadow cursor-pointer">
                        📸 Foto QR (Alternatif)
                        <input type="file" accept="image/*" capture="environment" onchange="scanFromPhoto(this)" class="hidden">
                    </label>
                </div>

                <div id="reader" class="mx-auto overflow-hidden rounded-lg bg-gray-900" style="width: 100%; max-width: 300px; min-height: 220px;"></div>
            </div>

            <!-- Form Konfirmasi Pembayaran -->
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="font-semibold text-gray-900 mb-4">Detail Pembayaran</h3>
                <form method="POST" action="{{ route('qris.pay') }}" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-medium text-gray-700">Kode QR Toko</label>
                        <input type="text" id="store_code" name="store_code" value="{{ $selectedSeller ? 'SWZ-STORE-' . $selectedSeller->id : '' }}" required placeholder="Contoh: SWZ-STORE-1" class="mt-1 block w-full border-gray-300 rounded-md text-sm uppercase font-bold text-indigo-700">
                    </div>

                    @if($sellers->count() > 0)
                    <div>
                        <label class="block text-xs text-gray-500">Atau pilih cepat dari daftar Seller aktif:</label>
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

    <script src="https://unpkg.com"></script>
    <script>
        function extractStoreCode(decodedText) {
            let code = decodedText;
            if (decodedText.includes('store_code=')) {
                code = decodedText.split('store_code=')[1].split('&')[0];
            }
            document.getElementById('store_code').value = code.toUpperCase();
            document.getElementById('camStatus').innerText = "✓ QR Terdeteksi: " + code.toUpperCase();
            alert('QR Toko Terdeteksi: ' + code.toUpperCase());
        }

        function openBackCamera() {
            const statusEl = document.getElementById('camStatus');
            statusEl.innerText = "Mencari kamera belakang HP...";

            Html5Qrcode.getCameras().then(devices => {
                if (devices && devices.length) {
                    // Pilih kamera terakhir (biasanya kamera belakang utama di Android)
                    let backCam = devices.find(d => d.label.toLowerCase().includes('back') || d.label.toLowerCase().includes('belakang')) || devices[devices.length - 1];
                    const html5QrCode = new Html5Qrcode("reader");
                    html5QrCode.start(
                        backCam.id,
                        { fps: 10, qrbox: 200 },
                        (decodedText) => {
                            extractStoreCode(decodedText);
                            html5QrCode.stop();
                        },
                        () => {}
                    ).then(() => {
                        statusEl.innerText = "Kamera aktif! Arahkan ke QR Seller.";
                    }).catch(err => {
                        statusEl.innerText = "Gagal start kamera: " + err + ". Gunakan tombol 'Foto QR' di sebelah kanan.";
                    });
                } else {
                    statusEl.innerText = "Kamera tidak ditemukan. Gunakan tombol 'Foto QR' di samping.";
                }
            }).catch(err => {
                statusEl.innerText = "Izin kamera ditolak browser. Gunakan tombol 'Foto QR' di samping.";
            });
        }

        function scanFromPhoto(input) {
            if (input.files.length === 0) return;
            const html5QrCode = new Html5Qrcode("reader");
            html5QrCode.scanFile(input.files[0], true)
                .then(decodedText => extractStoreCode(decodedText))
                .catch(err => alert("QR Code tidak terbaca pada foto. Pastikan gambar jelas."));
        }
    </script>
</x-app-layout>
