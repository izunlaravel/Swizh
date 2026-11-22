<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Scan QRIS & Pembayaran Merchant</h2>
            <a href="{{ route('dashboard') }}" class="text-sm text-indigo-600 underline">← Kembali</a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if(session('popup_error'))
                <script>alert("{{ session('popup_error') }}");</script>
                <div class="p-4 bg-red-100 border border-red-400 text-red-800 rounded-md text-sm font-bold">{{ session('popup_error') }}</div>
            @endif

            <div class="bg-white shadow-sm sm:rounded-lg p-6 text-center">
                <h3 class="font-semibold text-gray-900 mb-2">Scan QR Seller Swizh</h3>
                <div class="flex justify-center gap-2 mb-3">
                    <button type="button" onclick="openBackCamera()" style="background-color:#2563eb;color:#fff;" class="px-4 py-2 text-xs font-bold rounded-md">📷 Kamera Belakang</button>
                    <label style="background-color:#4b5563;color:#fff;" class="px-4 py-2 text-xs font-bold rounded-md cursor-pointer">
                        📸 Foto QR
                        <input type="file" accept="image/*" capture="environment" onchange="scanFromPhoto(this)" class="hidden">
                    </label>
                </div>
                <div id="reader" class="mx-auto rounded-lg bg-gray-900" style="width:100%;max-width:280px;min-height:200px;"></div>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <form method="POST" action="{{ route('qris.pay') }}" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-medium text-gray-700">Kode QR Toko</label>
                        <input type="text" id="store_code" name="store_code" value="{{ $selectedSeller ? 'SWZ-STORE-' . $selectedSeller->id : '' }}" required placeholder="SWZ-STORE-1" class="mt-1 block w-full border-gray-300 rounded-md text-sm uppercase font-bold text-indigo-700">
                    </div>

                    @if($sellers->count() > 0)
                    <div>
                        <label class="block text-xs text-gray-500">Pilih Cepat Merchant:</label>
                        <select onchange="document.getElementById('store_code').value = this.value" class="mt-1 block w-full border-gray-300 rounded-md text-xs">
                            <option value="">-- Pilih Seller --</option>
                            @foreach($sellers as $s)
                                <option value="SWZ-STORE-{{ $s->id }}">{{ $s->store_name }} (SP {{ $s->sp_percentage }}% {{ $s->cash_deposit_balance >= 100000 ? '| Terima Cash' : '' }})</option>
                            @endforeach
                        </select>
                    </div>
                    @endif

                    <div>
                        <label class="block text-xs font-medium text-gray-700">Total Belanja (Rp)</label>
                        <input type="number" name="amount_rp" min="1000" value="50000" required class="mt-1 block w-full border-gray-300 rounded-md text-lg font-bold">
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-700">Nominal Barang Non-SP (Opsional)</label>
                        <input type="number" name="non_sp_amount_rp" min="0" value="0" class="mt-1 block w-full border-gray-300 rounded-md text-sm">
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-700">Metode Pembayaran</label>
                        <select name="payment_method" class="mt-1 block w-full border-gray-300 rounded-md text-sm">
                            <option value="qris_gateway">QRIS Payment Gateway (Midtrans / All E-Wallet & Bank)</option>
                            <option value="wallet_balance">Potong Saldo Wallet Swizh (Rp {{ number_format(Auth::user()->wallet_balance, 0, ',', '.') }})</option>
                            <option value="cash_deposit">Bayar Tunai / Cash ke Kasir (Khusus Merchant Berdeposit)</option>
                        </select>
                    </div>

                    <button type="submit" style="background-color:#16a34a;color:#fff;" class="w-full py-3 text-sm font-bold rounded-md shadow">Konfirmasi & Proses Pembayaran</button>
                </form>
            </div>
        </div>
    </div>

    <script src="https://unpkg.com"></script>
    <script>
        function extractStoreCode(t) {
            let c = t.includes('store_code=') ? t.split('store_code=').split('&') : t;
            document.getElementById('store_code').value = c.toUpperCase();
            alert('QR Toko Terdeteksi: ' + c.toUpperCase());
        }
        function openBackCamera() {
            Html5Qrcode.getCameras().then(d => {
                let cam = d.find(x => x.label.toLowerCase().includes('back')) || d[d.length - 1];
                const qr = new Html5Qrcode("reader");
                qr.start(cam.id, { fps: 10, qrbox: 180 }, txt => { extractStoreCode(txt); qr.stop(); }, () => {});
            });
        }
        function scanFromPhoto(input) {
            if (!input.files.length) return;
            (new Html5Qrcode("reader")).scanFile(input.files, true).then(txt => extractStoreCode(txt));
        }
    </script>
</x-app-layout>
