@php
    $user = Auth::user();
    $period = now()->format('Y-m');
    $spService = app(\App\Services\SwizhPointService::class);
    $stats = $spService->getMemberMonthlyStats($user->id, $period);
    $mySeller = \Illuminate\Support\Facades\DB::table('sellers')->where('user_id', $user->id)->first();
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
        <div class="flex justify-between items-center gap-2">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Dashboard Swizh</h2>
            <div class="flex items-center gap-2">
                <a href="{{ route('sellers.nearby') }}" style="background-color:#0d9488; color:#ffffff;" class="px-3 py-1.5 text-xs font-bold rounded-md shadow">📍 Tenant Terdekat</a>
                <a href="{{ route('qris.scan') }}" style="background-color:#2563eb; color:#ffffff;" class="px-3 py-1.5 text-xs font-bold rounded-md shadow">
                    📷 Scan QRIS
                </a>
                @if($user->is_admin)
                    <a href="{{ route('admin.sellers.index') }}" style="background-color:#d97706; color:#ffffff;" class="px-3 py-1.5 text-xs font-bold rounded-md shadow">
                        ⚙ Admin
                    </a>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if(session('success'))
                <div class="p-4 bg-green-100 border border-green-400 text-green-800 rounded-md text-sm font-medium">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="p-4 bg-red-100 border border-red-400 text-red-800 rounded-md text-sm">{{ session('error') }}</div>
            @endif

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="bg-white shadow-sm sm:rounded-lg p-6 border-l-4 border-green-500">
                    <p class="text-xs text-gray-500 uppercase">Saldo Wallet Aktif (1 SP = Rp 1)</p>
                    <p class="text-2xl font-bold text-gray-900 mt-1">Rp {{ number_format($user->wallet_balance, 0, ',', '.') }}</p>
                </div>
                <div class="bg-white shadow-sm sm:rounded-lg p-6 border-l-4 border-yellow-500">
                    <p class="text-xs text-gray-500 uppercase">Bonus Pending ({{ $period }})</p>
                    <p class="text-2xl font-bold text-yellow-600 mt-1">Rp {{ number_format($pendingBonus, 0, ',', '.') }}</p>
                    <p class="text-xs text-gray-500 mt-1">{{ $stats['personal_sp'] >= 1 ? '✓ Syarat 1 SP terpenuhi' : 'Belanja min 1 SP untuk cair' }}</p>
                </div>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <div class="flex justify-between mb-3">
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-green-100 text-green-800">Member Aktif</span>
                    <span class="font-bold text-indigo-600">{{ $user->referral_code }}</span>
                </div>
                <div class="flex gap-2">
                    <input type="text" id="refLink" readonly value="{{ url('/register?ref=' . $user->referral_code) }}" class="w-full text-xs border-gray-300 rounded-md bg-gray-50">
                    <button onclick="navigator.clipboard.writeText(document.getElementById('refLink').value); alert('Link disalin!');" class="px-4 py-2 bg-indigo-600 text-white text-xs rounded-md">Salin</button>
                </div>
            </div>

            <!-- Modul Seller + QR Code SVG Lokal -->
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="font-semibold text-gray-900 mb-3">Status Toko & QRIS Seller</h3>
                @if(!$mySeller)
                    <form method="POST" action="{{ route('member.seller.apply') }}" class="grid grid-cols-1 md:grid-cols-3 gap-3">
                        @csrf
                        <input type="text" name="store_name" required placeholder="Nama Toko Anda" class="text-sm border-gray-300 rounded-md">
                        <input type="number" step="0.01" name="sp_percentage" required placeholder="Persentase SP (misal: 10)" class="text-sm border-gray-300 rounded-md">
                        <input type="text" name="referrer_code" placeholder="Kode Ref Pengajak (Opsional)" class="text-sm border-gray-300 rounded-md uppercase">
                        <div class="md:col-span-3">
                            <button type="submit" class="px-4 py-2 bg-gray-800 text-white text-xs font-semibold rounded-md">Ajukan Daftar Seller</button>
                        </div>
                    </form>
                @elseif($mySeller->status === 'pending')
                    <div class="p-3 bg-yellow-50 border border-yellow-200 rounded text-xs text-yellow-800">
                        Toko <strong>{{ $mySeller->store_name }}</strong> (SP {{ $mySeller->sp_percentage }}%) sedang <strong>Menunggu Persetujuan Admin Swizh</strong>.
                    </div>
                @else
                    <div class="flex flex-col md:flex-row items-center justify-between gap-6 p-4 bg-gray-50 rounded-lg">
                        <div class="text-center md:text-left">
                            <p class="font-bold text-lg text-gray-900">{{ $mySeller->store_name }} <span class="text-xs font-normal text-green-600">(● Aktif)</span></p>
                            <p class="text-xs text-gray-600 mt-1">Kode QR Toko: <strong>SWZ-STORE-{{ $mySeller->id }}</strong> | SP: <strong>{{ $mySeller->sp_percentage }}%</strong></p>
                            @if($mySeller->requested_sp_percentage)
                                <p class="text-xs text-yellow-700 mt-1">⏳ Pengajuan SP {{ $mySeller->requested_sp_percentage }}% menunggu Admin.</p>
                            @endif
                            <form method="POST" action="{{ route('member.seller.request_sp') }}" class="flex items-center gap-2 mt-3">
                                @csrf
                                <input type="number" step="0.01" name="requested_sp_percentage" placeholder="SP Baru %" required class="w-28 text-xs border-gray-300 rounded-md">
                                <button type="submit" class="px-3 py-2 bg-indigo-600 text-white text-xs rounded-md">Ajukan Ubah %</button>
                            </form>
                            <form id="gpsForm" method="POST" action="{{ route('member.seller.location') }}" class="mt-2">@csrf<input type="hidden" name="latitude" id="latInput"><input type="hidden" name="longitude" id="lngInput"><button type="button" onclick="saveGpsLocation()" style="background-color:#0d9488;color:#fff;" class="px-3 py-1.5 text-xs font-semibold rounded-md">📍 Simpan Lokasi GPS Toko ({{ $mySeller->latitude ? 'Tersimpan' : 'Belum Set' }})</button></form><script>function saveGpsLocation(){if(!navigator.geolocation){alert('GPS tidak didukung browser');return;}navigator.geolocation.getCurrentPosition(function(pos){document.getElementById('latInput').value=pos.coords.latitude;document.getElementById('lngInput').value=pos.coords.longitude;document.getElementById('gpsForm').submit();},function(err){alert('Gagal ambil GPS: '+err.message);});}</script>
                        </div>
                        <div class="text-center bg-white p-3 rounded-lg border shadow-sm">
                            <div class="inline-block">
                                <img src="{{ (new \chillerlan\QRCode\QRCode)->render('SWZ-STORE-' . $mySeller->id) }}" alt="QR Toko" style="width:140px;height:140px;margin:auto;"><script>var qr=qrcode(0,"M");qr.addData("SWZ-STORE-{{ $mySeller->id }}");qr.make();document.getElementById("sellerQr").innerHTML=qr.createImgTag(4);</script>
                            </div>
                            <p class="text-[11px] font-bold text-gray-700 mt-1">SCAN QRIS SWIZH</p>
                        </div>
                    </div>
                @endif
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="font-semibold mb-3">Progres Leader ({{ $period }})</h3>
                <div class="grid grid-cols-3 gap-3 text-center">
                    <div class="p-3 bg-gray-50 rounded"><p class="text-xs text-gray-500">SP Pribadi</p><p class="font-bold">{{ number_format($stats['personal_sp'], 0, ',', '.') }} / 1</p></div>
                    <div class="p-3 bg-gray-50 rounded"><p class="text-xs text-gray-500">Ref Aktif</p><p class="font-bold">{{ $stats['active_referrals'] }} / 10</p></div>
                    <div class="p-3 bg-gray-50 rounded"><p class="text-xs text-gray-500">SP Grup</p><p class="font-bold">{{ number_format($stats['group_sp'], 0, ',', '.') }} / 10 Jt</p></div>
                </div>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="font-semibold mb-3">Mutasi Bonus Terbaru</h3>
                @forelse($ledgers as $item)
                    <div class="py-2 border-b flex justify-between text-sm">
                        <div>
                            <p class="font-medium">{{ $item->notes }}</p>
                            <p class="text-xs text-gray-400">{{ \Carbon\Carbon::parse($item->created_at)->format('d M Y, H:i') }} WIB</p>
                        </div>
                        <div class="text-right">
                            <p class="font-bold text-green-600">+Rp {{ number_format($item->amount_rp, 0, ',', '.') }}</p>
                            <span class="text-[10px] uppercase">{{ $item->status }}</span>
                        </div>
                    </div>
                @empty
                    <p class="text-xs text-gray-500">Belum ada riwayat bonus.</p>
                @endforelse
            </div>
        </div>
    </div>
</x-app-layout>
