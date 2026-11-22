@php
    $user = Auth::user();
    $period = now()->format('Y-m');
    $spService = app(\App\Services\SwizhPointService::class);
    $stats = $spService->getMemberMonthlyStats($user->id, $period);
    $mySeller = \Illuminate\Support\Facades\DB::table('sellers')->where('user_id', $user->id)->first();
    $myProducts = $mySeller ? \Illuminate\Support\Facades\DB::table('seller_products')->where('seller_id', $mySeller->id)->get() : collect();
    $notifications = \Illuminate\Support\Facades\DB::table('user_notifications')->where('user_id', $user->id)->orderByDesc('id')->limit(5)->get();
    $promos = \Illuminate\Support\Facades\DB::table('promo_broadcasts')->where('is_active', true)->orderByDesc('id')->get();
    $pendingBonus = (int) \Illuminate\Support\Facades\DB::table('bonus_ledgers')->where('user_id', $user->id)->where('period_month', $period)->where('status', 'pending')->sum('amount_rp');
    $ledgers = \Illuminate\Support\Facades\DB::table('bonus_ledgers')->where('user_id', $user->id)->orderByDesc('id')->limit(10)->get();
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap justify-between items-center gap-2">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Dashboard Member</h2>
            <div class="flex items-center gap-2">
                <a href="{{ route('sellers.nearby') }}" style="background-color:#0d9488;color:#fff;" class="px-3 py-1.5 text-xs font-bold rounded-md shadow">📍 Tenant Terdekat</a>
                <a href="{{ route('qris.scan') }}" style="background-color:#2563eb;color:#fff;" class="px-3 py-1.5 text-xs font-bold rounded-md shadow">📷 Scan QRIS</a>
                @if($user->is_admin)
                    <a href="{{ route('admin.sellers.index') }}" style="background-color:#d97706;color:#fff;" class="px-3 py-1.5 text-xs font-bold rounded-md shadow">⚙ Admin</a>
                @endif
            </div>
        </div>
    </x-slot>

    @if(session('popup_success'))<script>window.onload=()=>alert("✅ BERHASIL!\n\n{{ session('popup_success') }}");</script>@endif
    @if(session('popup_error'))<script>window.onload=()=>alert("❌ PERHATIAN!\n\n{{ session('popup_error') }}");</script>@endif

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if(session('popup_success') || session('success'))<div class="p-4 bg-green-100 border border-green-400 text-green-800 rounded-md text-sm font-medium">{{ session('popup_success') ?? session('success') }}</div>@endif
            @if(session('popup_error') || session('error') || $errors->any())<div class="p-4 bg-red-100 border border-red-400 text-red-800 rounded-md text-sm">{{ session('popup_error') ?? session('error') ?? $errors->first() }}</div>@endif

            @foreach($promos as $promo)
                <div class="p-4 bg-indigo-50 border-l-4 border-indigo-600 rounded-md">
                    <p class="font-bold text-indigo-900 text-sm">📢 {{ $promo->title }}</p>
                    <p class="text-xs text-indigo-800 mt-1 whitespace-pre-line">{{ $promo->content }}</p>
                </div>
            @endforeach

            <div class="bg-white shadow-sm sm:rounded-lg p-6 flex flex-col sm:flex-row items-center justify-between gap-4">
                <div class="flex items-center gap-4">
                    @if($user->profile_photo && file_exists(public_path($user->profile_photo)))
                        <img src="{{ asset($user->profile_photo) }}" style="width:64px;height:64px;border-radius:9999px;object-fit:cover;" class="border-2 border-indigo-500 shrink-0">
                    @else
                        <div style="width:64px;height:64px;border-radius:9999px;" class="bg-indigo-100 text-indigo-700 font-bold text-xl flex items-center justify-center shrink-0">{{ strtoupper(substr($user->name, 0, 1)) }}</div>
                    @endif
                    <div>
                        <h3 class="font-bold text-gray-900">{{ $user->name }}</h3>
                        <p class="text-xs text-gray-500">{{ $user->email }} | HP: {{ $user->phone ?? '-' }}</p>
                        <span class="inline-block mt-1 px-2 py-0.5 rounded-full text-[11px] font-semibold bg-green-100 text-green-800">● Member Aktif ({{ $user->referral_code }})</span>
                    </div>
                </div>
                <form method="POST" action="{{ route('member.profile.photo') }}" enctype="multipart/form-data" class="flex flex-col sm:flex-row gap-2 w-full sm:w-auto">
                    @csrf
                    <input type="file" name="photo" accept="image/*" required class="text-xs border rounded p-1.5">
                    <button type="submit" style="background-color:#4f46e5;color:#fff;" class="px-3 py-2 text-xs font-bold rounded-md">Upload Foto (Maks 2MB)</button>
                </form>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="bg-white shadow-sm sm:rounded-lg p-6 border-l-4 border-green-500 space-y-3">
                    <div>
                        <p class="text-xs text-gray-500 uppercase">Saldo Wallet Aktif (1 SP = Rp 1)</p>
                        <p class="text-2xl font-bold text-gray-900 mt-1">Rp {{ number_format($user->wallet_balance, 0, ',', '.') }}</p>
                        <p class="text-[11px] text-gray-500 mt-1">Saldo &lt; Rp 50.000 khusus belanja Merchant. Withdraw &gt; Rp 50.000 pukul 08.00–10.00 WIB (Biaya gateway Rp 2.500).</p>
                    </div>
                    @if($user->wallet_balance > 50000)
                        <form method="POST" action="{{ route('withdraw.store') }}" class="pt-3 border-t grid grid-cols-1 sm:grid-cols-2 gap-2">
                            @csrf
                            <input type="number" name="amount_rp" min="50001" max="{{ $user->wallet_balance }}" placeholder="Nominal (> 50.000)" required class="text-xs border-gray-300 rounded">
                            <input type="text" name="bank_name" value="{{ $user->bank_name }}" placeholder="Bank / E-Wallet" required class="text-xs border-gray-300 rounded">
                            <input type="text" name="bank_account_number" value="{{ $user->bank_account_number }}" placeholder="No. Rekening / HP" required class="text-xs border-gray-300 rounded">
                            <input type="text" name="bank_account_name" value="{{ $user->bank_account_name }}" placeholder="Atas Nama" required class="text-xs border-gray-300 rounded">
                            <button type="submit" style="background-color:#16a34a;color:#fff;" class="sm:col-span-2 py-2 text-xs font-bold rounded">Ajukan Withdraw (08.00-10.00 WIB)</button>
                        </form>
                    @endif
                </div>

                <div class="bg-white shadow-sm sm:rounded-lg p-6 border-l-4 border-yellow-500">
                    <p class="text-xs text-gray-500 uppercase">Bonus Pending ({{ $period }})</p>
                    <p class="text-2xl font-bold text-yellow-600 mt-1">Rp {{ number_format($pendingBonus, 0, ',', '.') }}</p>
                    <p class="text-xs text-gray-500 mt-1">{{ $stats['personal_sp'] >= 1 ? '✓ Syarat 1 SP terpenuhi' : 'Belanja min 1 SP sebelum tgl 1 untuk mencairkan.' }}</p>
                </div>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg p-6 space-y-3">
                <div class="flex justify-between items-center">
                    <h3 class="font-semibold text-gray-900">Proposal Promosi & Link Referral</h3>
                    <span class="font-bold text-indigo-600 text-sm">{{ $user->referral_code }}</span>
                </div>
                <textarea id="proposalText" rows="3" class="w-full text-xs border-gray-300 rounded-md">Halo! Yuk gabung di SWIZH — Belanja hemat di merchant sekitar dapat cashback langsung 40% Swizh Poin (1 SP = Rp 1), plus peluang penghasilan pasif dari referral member & omset merchant! Daftar gratis sekarang lewat link resmi saya di bawah ini:</textarea>
                <div class="flex flex-wrap gap-2">
                    <button type="button" onclick="copyProposal()" style="background-color:#4f46e5;color:#fff;" class="px-4 py-2 text-xs font-bold rounded-md">📋 Copy Proposal + Link</button>
                    <button type="button" onclick="shareProposal()" style="background-color:#16a34a;color:#fff;" class="px-4 py-2 text-xs font-bold rounded-md">📤 Share ke WhatsApp / Sosmed</button>
                </div>
            </div>
            <div class="bg-white shadow-sm sm:rounded-lg p-6 space-y-4">
                <h3 class="font-semibold text-gray-900">Status Toko & Merchant Swizh</h3>
                @if(!$mySeller)
                    <form method="POST" action="{{ route('member.seller.apply') }}" class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        @csrf
                        <input type="text" name="store_name" required placeholder="Nama Toko / Usaha" class="text-sm border-gray-300 rounded-md">
                        <select name="category" onchange="document.getElementById('customCatBox').style.display = this.value === 'Lainnya' ? 'block' : 'none'" class="text-sm border-gray-300 rounded-md">
                            @foreach(['Makanan/Minuman','Toko Kelontong','Service HP','Service Laptop','Bengkel','Showroom Mobil','Showroom Motor','Toko Sepeda','Toko Mainan','Toko Pakaian','Tukang Pangkas','Lainnya'] as $cat)
                                <option value="{{ $cat }}">{{ $cat }}</option>
                            @endforeach
                        </select>
                        <div id="customCatBox" style="display:none;" class="md:col-span-2">
                            <input type="text" name="custom_category" placeholder="Tuliskan jenis usaha/toko Anda..." class="w-full text-sm border-gray-300 rounded-md">
                        </div>
                        <input type="number" step="0.01" min="5" name="sp_percentage" required placeholder="Pemberian Poin SP % (Minimal 5%)" class="text-sm border-gray-300 rounded-md">
                        <input type="text" name="referrer_code" placeholder="Kode Ref Pengajak Toko (Opsional)" class="text-sm border-gray-300 rounded-md uppercase">
                        <input type="text" name="address" required placeholder="Alamat Lengkap Toko" class="md:col-span-2 text-sm border-gray-300 rounded-md">
                        <input type="text" name="bank_info" placeholder="Rekening Payout Merchant (Bank - No. Rek - Atas Nama)" class="md:col-span-2 text-sm border-gray-300 rounded-md">
                        <div class="md:col-span-2">
                            <button type="submit" style="background-color:#1f2937;color:#fff;" class="px-4 py-2 text-xs font-bold rounded-md">Ajukan Buka Toko Seller</button>
                        </div>
                    </form>
                @elseif($mySeller->status === 'pending')
                    <div class="p-3 bg-yellow-50 border border-yellow-200 rounded text-xs text-yellow-800">
                        Toko <strong>{{ $mySeller->store_name }}</strong> ({{ $mySeller->category }} | SP {{ $mySeller->sp_percentage }}%) sedang <strong>Menunggu Persetujuan Admin Swizh</strong>.
                    </div>
                @else
                    <div class="p-4 bg-gray-50 rounded-lg space-y-4">
                        <div class="flex flex-col md:flex-row justify-between gap-4">
                            <div class="space-y-2">
                                <p class="font-bold text-lg text-gray-900">{{ $mySeller->store_name }} <span class="text-xs text-green-600">(● Aktif - {{ $mySeller->category }})</span></p>
                                <p class="text-xs text-gray-600">SP Aktif: <strong>{{ $mySeller->sp_percentage }}%</strong> | Deposit Cash: <strong>Rp {{ number_format($mySeller->cash_deposit_balance, 0, ',', '.') }}</strong></p>

                                <form method="POST" action="{{ route('member.seller.request_sp') }}" class="flex items-center gap-2">
                                    @csrf
                                    <input type="number" step="0.01" min="5" name="requested_sp_percentage" placeholder="SP Baru (Min 5%)" required class="w-36 text-xs border-gray-300 rounded-md">
                                    <button type="submit" style="background-color:#4f46e5;color:#fff;" class="px-3 py-1.5 text-xs font-bold rounded-md">Ajukan Ubah % SP</button>
                                </form>

                                <form method="POST" action="{{ route('member.seller.deposit') }}" class="flex items-center gap-2">
                                    @csrf
                                    <input type="number" min="100000" name="amount_rp" placeholder="Deposit Cash (Min 100rb)" required class="w-40 text-xs border-gray-300 rounded-md">
                                    <button type="submit" style="background-color:#d97706;color:#fff;" class="px-3 py-1.5 text-xs font-bold rounded-md">+ Isi Deposit Tunai</button>
                                </form>
                            </div>

                            <div class="text-center bg-white p-3 rounded border">
                                <img src="{{ (new \chillerlan\QRCode\QRCode)->render('SWZ-STORE-' . $mySeller->id) }}" style="width:130px;height:130px;margin:auto;">
                                <p class="text-[11px] font-bold mt-1">SWZ-STORE-{{ $mySeller->id }}</p>
                            </div>
                        </div>

                        <form method="POST" action="{{ route('member.seller.update_profile') }}" class="grid grid-cols-1 md:grid-cols-2 gap-2 pt-3 border-t">
                            @csrf
                            <input type="text" name="address" value="{{ $mySeller->address }}" placeholder="Alamat Toko" required class="md:col-span-2 text-xs border-gray-300 rounded">
                            <input type="text" name="latitude" id="latInput" value="{{ $mySeller->latitude }}" placeholder="Latitude GPS" class="text-xs border-gray-300 rounded">
                            <input type="text" name="longitude" id="lngInput" value="{{ $mySeller->longitude }}" placeholder="Longitude GPS" class="text-xs border-gray-300 rounded">
                            <input type="text" name="bank_info" value="{{ $mySeller->bank_info }}" placeholder="Rekening Payout Payment Gateway" class="md:col-span-2 text-xs border-gray-300 rounded">
                            <textarea name="requested_non_sp_notes" rows="2" placeholder="Catatan Khusus Produk yang TIDAK termasuk SP (Menunggu approval Admin)" class="md:col-span-2 text-xs border-gray-300 rounded">{{ $mySeller->requested_non_sp_notes ?? $mySeller->non_sp_notes }}</textarea>
                            <div class="md:col-span-2 flex gap-2">
                                <button type="button" onclick="fillGps()" style="background-color:#0d9488;color:#fff;" class="px-3 py-1.5 text-xs font-bold rounded">📍 Ambil GPS Otomatis</button>
                                <button type="submit" style="background-color:#1f2937;color:#fff;" class="px-3 py-1.5 text-xs font-bold rounded">Simpan Profil Toko</button>
                            </div>
                        </form>

                        <div class="pt-3 border-t space-y-3">
                            <p class="text-xs font-bold text-gray-800">Produk Unggulan Toko ({{ $myProducts->count() }} / 5 Foto)</p>
                            @if($myProducts->count() < 5)
                                <form method="POST" action="{{ route('member.seller.product_upload') }}" enctype="multipart/form-data" class="grid grid-cols-1 sm:grid-cols-4 gap-2">
                                    @csrf
                                    <input type="text" name="product_name" placeholder="Nama Produk" required class="text-xs border-gray-300 rounded">
                                    <input type="text" name="product_type" placeholder="Jenis Produk" required class="text-xs border-gray-300 rounded">
                                    <input type="number" name="price_rp" placeholder="Harga Rp" required class="text-xs border-gray-300 rounded">
                                    <input type="file" name="photo" accept="image/*" required class="text-xs border rounded p-1">
                                    <button type="submit" style="background-color:#2563eb;color:#fff;" class="sm:col-span-4 py-1.5 text-xs font-bold rounded">Upload Foto Produk (Maks 5MB)</button>
                                </form>
                            @endif
                            <div class="grid grid-cols-2 sm:grid-cols-5 gap-2">
                                @foreach($myProducts as $p)
                                    <div class="border rounded p-1.5 bg-white text-center">
                                        <img src="{{ asset($p->photo_path) }}" class="w-full h-20 object-cover rounded">
                                        <p class="text-[11px] font-bold truncate mt-1">{{ $p->product_name }}</p>
                                        <form method="POST" action="{{ route('member.seller.product_delete', $p->id) }}">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="text-[10px] text-red-600 underline mt-1">Hapus</button>
                                        </form>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endif
            </div>
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="font-semibold mb-3">🔔 Notifikasi Terbaru</h3>
                @forelse($notifications as $notif)
                    <div class="py-2 border-b text-xs">
                        <p class="font-bold text-gray-800">{{ $notif->title }}</p>
                        <p class="text-gray-600">{{ $notif->message }}</p>
                    </div>
                @empty
                    <p class="text-xs text-gray-500">Belum ada notifikasi.</p>
                @endforelse
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <div class="flex flex-wrap justify-between items-center gap-2 mb-3">
                    <h3 class="font-semibold">Progres Leader ({{ $period }})</h3>
                    @if($stats['is_promo_leader'])
                        <span class="px-2.5 py-0.5 text-[11px] font-bold bg-red-100 text-red-700 rounded-full">🔥 PROMO LAUNCHING (Target 1 Jt s/d {{ $stats['promo_until'] }})</span>
                    @endif
                </div>
                <div class="grid grid-cols-3 gap-3 text-center">
                    <div class="p-3 bg-gray-50 rounded"><p class="text-xs text-gray-500">SP Pribadi</p><p class="font-bold">{{ number_format($stats['personal_sp'], 0, ',', '.') }} / 1</p></div>
                    <div class="p-3 bg-gray-50 rounded"><p class="text-xs text-gray-500">Ref Aktif</p><p class="font-bold">{{ $stats['active_referrals'] }} / 10</p></div>
                    <div class="p-3 bg-gray-50 rounded"><p class="text-xs text-gray-500">SP Grup</p><p class="font-bold">{{ number_format($stats['group_sp'], 0, ',', '.') }} / {{ $stats['required_group_sp'] >= 1000000 ? ($stats['required_group_sp']/1000000) . ' Jt' : number_format($stats['required_group_sp'], 0, ',', '.') }}</p></div>
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

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <h3 class="font-semibold text-sm mb-2">✉ Kirim Saran / Masukan ke Admin</h3>
                    <form method="POST" action="{{ route('member.inbox.send') }}" class="space-y-2">
                        @csrf
                        <input type="text" name="subject" placeholder="Judul Saran" required class="w-full text-xs border-gray-300 rounded">
                        <textarea name="message" rows="2" placeholder="Tuliskan saran Anda..." required class="w-full text-xs border-gray-300 rounded"></textarea>
                        <button type="submit" style="background-color:#4f46e5;color:#fff;" class="px-3 py-1.5 text-xs font-bold rounded">Kirim ke Admin</button>
                    </form>
                </div>

                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <h3 class="font-semibold text-sm mb-2">🤖 Asisten Chatbot Swizh</h3>
                    <div id="chatLog" class="h-24 overflow-y-auto bg-gray-50 p-2 rounded text-xs space-y-1 mb-2">
                        <p class="text-indigo-700"><strong>Swizh Bot:</strong> Halo! Tanyakan seputar SP, Withdraw, Leader, atau Deposit Cash.</p>
                    </div>
                    <div class="flex gap-2">
                        <input type="text" id="chatInput" placeholder="Ketik pertanyaan..." class="w-full text-xs border-gray-300 rounded">
                        <button type="button" onclick="askBot()" style="background-color:#0d9488;color:#fff;" class="px-3 py-1.5 text-xs font-bold rounded">Tanya</button>
                    </div>
                </div>
            </div>

            <div class="text-center text-xs text-gray-500 py-4">
                &copy; {{ date('Y') }} Swizh Indonesia • <a href="{{ route('terms') }}" class="underline text-indigo-600 font-semibold">Syarat & Ketentuan (Terms & Conditions)</a>
            </div>
        </div>
    </div>

    <script>
        const refUrl = "{{ url('/register?ref=' . $user->referral_code) }}";
        function copyProposal() {
            navigator.clipboard.writeText(document.getElementById('proposalText').value + "\n\nLink Daftar: " + refUrl);
            alert('Proposal & Link Referral berhasil disalin!');
        }
        function shareProposal() {
            const fullText = document.getElementById('proposalText').value + "\n\nLink Daftar: " + refUrl;
            if (navigator.share) navigator.share({ title: 'Gabung Swizh', text: fullText });
            else copyProposal();
        }
        function fillGps() {
            navigator.geolocation.getCurrentPosition(pos => {
                document.getElementById('latInput').value = pos.coords.latitude;
                document.getElementById('lngInput').value = pos.coords.longitude;
                alert('Koordinat GPS terisi! Klik "Simpan Profil Toko".');
            });
        }
        function askBot() {
            const inp = document.getElementById('chatInput');
            const log = document.getElementById('chatLog');
            const q = inp.value.toLowerCase();
            if (!q) return;
            let ans = "Silakan cek menu Syarat & Ketentuan atau kirim pesan ke Inbox Admin.";
            if (q.includes('withdraw') || q.includes('tarik')) ans = "Withdraw bisa diajukan jika saldo > Rp 50.000 pukul 08.00–10.00 WIB (biaya gateway Rp 2.500).";
            else if (q.includes('leader')) ans = "Syarat Leader: Min 1 SP pribadi, 10 referral aktif, dan 1 Juta SP grup (promo 3 bulan pertama).";
            else if (q.includes('cash') || q.includes('tunai')) ans = "Bayar Cash berlaku di Merchant dengan saldo deposit minimal Rp 100.000.";
            else if (q.includes('sp') || q.includes('poin')) ans = "1 SP = Rp 1. Belanja pribadi dapat 40% SP langsung cair!";
            log.innerHTML += `<p><strong>Anda:</strong> ${inp.value}</p><p class="text-indigo-700"><strong>Swizh Bot:</strong> ${ans}</p>`;
            inp.value = '';
            log.scrollTop = log.scrollHeight;
        }
    </script>
</x-app-layout>
