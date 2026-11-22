<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Command Center Admin Swizh</h2>
            <a href="{{ route('dashboard') }}" class="text-sm text-indigo-600 underline">← Dashboard Member</a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if(session('success'))<div class="p-4 bg-green-100 border border-green-400 text-green-800 rounded-md text-sm font-medium">{{ session('success') }}</div>@endif
            @if(session('error'))<div class="p-4 bg-red-100 border border-red-400 text-red-800 rounded-md text-sm">{{ session('error') }}</div>@endif

            <!-- 1. Info Box Klik-Detail & Pencarian -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <a href="{{ route('admin.sellers.index', ['tab' => 'today']) }}" class="bg-white p-5 rounded-lg shadow-sm border-l-4 border-blue-600 hover:bg-blue-50">
                    <p class="text-xs text-gray-500 uppercase">Member Baru Hari Ini (Klik Detail)</p>
                    <p class="text-2xl font-bold text-blue-700 mt-1">{{ number_format($newMembersToday, 0, ',', '.') }}</p>
                </a>
                <a href="{{ route('admin.sellers.index', ['tab' => 'all']) }}" class="bg-white p-5 rounded-lg shadow-sm border-l-4 border-indigo-600 hover:bg-indigo-50">
                    <p class="text-xs text-gray-500 uppercase">Total Member (Klik Detail)</p>
                    <p class="text-2xl font-bold text-indigo-700 mt-1">{{ number_format($totalMembers, 0, ',', '.') }}</p>
                </a>
                <a href="{{ route('admin.sellers.index', ['tab' => 'sellers']) }}" class="bg-white p-5 rounded-lg shadow-sm border-l-4 border-green-600 hover:bg-green-50">
                    <p class="text-xs text-gray-500 uppercase">Member Seller Aktif (Klik Detail)</p>
                    <p class="text-2xl font-bold text-green-700 mt-1">{{ number_format($totalActiveSellers, 0, ',', '.') }}</p>
                </a>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg p-6 space-y-4">
                <form method="GET" action="{{ route('admin.sellers.index') }}" class="flex gap-2">
                    <input type="hidden" name="tab" value="{{ $filterTab }}">
                    <input type="text" name="search" value="{{ $search }}" placeholder="Cari nama, email, no. HP, atau kode referral..." class="w-full text-xs border-gray-300 rounded-md">
                    <button type="submit" style="background-color:#4f46e5;color:#fff;" class="px-4 py-2 text-xs font-bold rounded-md">🔍 Cari</button>
                </form>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs border-collapse">
                        <thead><tr class="bg-gray-100 border-b"><th class="p-2">Nama</th><th class="p-2">HP / Email</th><th class="p-2">Ref</th><th class="p-2">Saldo</th><th class="p-2">Status</th></tr></thead>
                        <tbody>
                            @foreach($filteredMembers as $m)
                                <tr class="border-b">
                                    <td class="p-2 font-bold">{{ $m->name }}</td>
                                    <td class="p-2">{{ $m->phone ?? '-' }}<br><span class="text-gray-400">{{ $m->email }}</span></td>
                                    <td class="p-2 text-indigo-600 font-semibold">{{ $m->referral_code }}</td>
                                    <td class="p-2">Rp {{ number_format($m->wallet_balance, 0, ',', '.') }}</td>
                                    <td class="p-2">{{ $m->is_seller ? '🏪 Seller' : '👤 Member' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- 2. Grafik Pertumbuhan Bisnis 7 Hari -->
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="font-semibold text-gray-900 mb-3">📈 Grafik Pertumbuhan Bisnis (7 Hari Terakhir)</h3>
                <div class="grid grid-cols-7 gap-2 items-end h-32 pt-4 border-b">
                    @php $maxSp = max(1, max($chartSp)); @endphp
                    @foreach($chartLabels as $idx => $lbl)
                        <div class="text-center flex flex-col items-center justify-end h-full">
                            <span class="text-[10px] font-bold text-indigo-600">{{ $chartSp[$idx] > 0 ? number_format($chartSp[$idx]/1000, 0) . 'k' : '0' }}</span>
                            <div style="height: {{ max(8, round(($chartSp[$idx] / $maxSp) * 80)) }}px; background-color:#4f46e5;" class="w-6 rounded-t"></div>
                            <span class="text-[10px] text-gray-500 mt-1">{{ $lbl }}</span>
                            <span class="text-[9px] text-green-700">+{{ $chartMembers[$idx] }} usr</span>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- 3. Pembukuan Lengkap -->
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="font-semibold text-gray-900 mb-3">📒 Pembukuan Keuangan Swizh ({{ $period }})</h3>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3 text-xs">
                    <div class="p-3 bg-gray-50 rounded border"><p class="text-gray-500">Omset Merchant</p><p class="text-base font-bold mt-1">Rp {{ number_format($grossSalesMonth, 0, ',', '.') }}</p></div>
                    <div class="p-3 bg-gray-50 rounded border"><p class="text-gray-500">Total SP Terbentuk</p><p class="text-base font-bold text-indigo-700 mt-1">{{ number_format($globalSpMonth, 0, ',', '.') }} SP</p></div>
                    <div class="p-3 bg-gray-50 rounded border"><p class="text-gray-500">Pay Out Member (65.5%)</p><p class="text-base font-bold text-amber-700 mt-1">Rp {{ number_format($totalPayoutObligation, 0, ',', '.') }}</p></div>
                    <div class="p-3 bg-gray-50 rounded border"><p class="text-gray-500">Retensi Net (34.5%)</p><p class="text-base font-bold text-green-700 mt-1">Rp {{ number_format($companyNetRetention, 0, ',', '.') }}</p></div>
                </div>
            </div>
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="font-semibold text-gray-900 mb-3">💸 Permohonan Withdraw Member</h3>
                @forelse($withdrawals as $wd)
                    <div class="py-3 border-b flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 text-xs">
                        <div>
                            <p class="font-bold text-gray-900">{{ $wd->user_name }} ({{ $wd->user_phone }}) — Tarik Rp {{ number_format($wd->amount_rp, 0, ',', '.') }}</p>
                            <p class="text-gray-600">Transfer Bersih: <strong>Rp {{ number_format($wd->net_amount_rp, 0, ',', '.') }}</strong> ke {{ $wd->bank_name }} - {{ $wd->bank_account_number }} (a.n {{ $wd->bank_account_name }})</p>
                        </div>
                        @if($wd->status === 'pending')
                            <div class="flex gap-2">
                                <form method="POST" action="{{ route('admin.withdrawals.process', $wd->id) }}">@csrf<input type="hidden" name="action" value="approve"><button type="submit" style="background-color:#16a34a;color:#fff;" class="px-3 py-1.5 font-bold rounded">✓ Transfer Sukses</button></form>
                                <form method="POST" action="{{ route('admin.withdrawals.process', $wd->id) }}">@csrf<input type="hidden" name="action" value="reject"><button type="submit" style="background-color:#dc2626;color:#fff;" class="px-3 py-1.5 font-bold rounded">✕ Tolak</button></form>
                            </div>
                        @else
                            <span class="px-2 py-1 rounded uppercase font-bold {{ $wd->status === 'approved' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">{{ $wd->status }}</span>
                        @endif
                    </div>
                @empty
                    <p class="text-xs text-gray-500">Belum ada permohonan withdraw.</p>
                @endforelse
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <h3 class="font-semibold text-gray-900 mb-2">🏆 Qualified Bonus Leader ({{ count($qualifiedLeaders) }} Orang)</h3>
                    <p class="text-[11px] text-gray-500 mb-3">Pool 5% Global: Rp {{ number_format($leaderPoolRp, 0, ',', '.') }}</p>
                    @forelse($qualifiedLeaders as $ql)
                        <div class="py-1.5 border-b text-xs flex justify-between">
                            <span><strong>{{ $ql['user']->name }}</strong> ({{ $ql['user']->referral_code }})</span>
                            <span class="text-green-700 font-bold">{{ number_format($ql['stats']['group_sp'], 0, ',', '.') }} SP</span>
                        </div>
                    @empty
                        <p class="text-xs text-gray-500">Belum ada member qualified Leader bulan ini.</p>
                    @endforelse
                </div>

                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <h3 class="font-semibold text-gray-900 mb-2">🎁 Qualified Program Reward ({{ count($qualifiedRewards) }} Orang)</h3>
                    <p class="text-[11px] text-gray-500 mb-3">Syarat: Min. 5 Referral Leader (Pool 10%: Rp {{ number_format($rewardPoolRp, 0, ',', '.') }})</p>
                    @forelse($qualifiedRewards as $qr)
                        <div class="py-1.5 border-b text-xs flex justify-between">
                            <span><strong>{{ $qr['user']->name }}</strong></span>
                            <span class="text-indigo-700 font-bold">{{ $qr['leader_refs'] }} Leader</span>
                        </div>
                    @empty
                        <p class="text-xs text-gray-500">Belum ada member qualified Reward bulan ini.</p>
                    @endforelse
                </div>
            </div>
            <div class="bg-white shadow-sm sm:rounded-lg p-6 space-y-4">
                <h3 class="font-semibold text-gray-900">📢 Broadcast Informasi Promo / Hadiah</h3>
                <form method="POST" action="{{ route('admin.promo.broadcast') }}" class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    @csrf
                    <input type="text" name="title" placeholder="Judul Promo / Hadiah" required class="md:col-span-3 text-xs border-gray-300 rounded">
                    <textarea name="content" rows="3" placeholder="Detail informasi promo / hadiah..." required class="md:col-span-3 text-xs border-gray-300 rounded"></textarea>
                    <input type="number" name="min_personal_sp" placeholder="Min SP Pribadi (opsional)" class="text-xs border-gray-300 rounded">
                    <input type="number" name="min_active_referrals" placeholder="Min Ref Aktif (opsional)" class="text-xs border-gray-300 rounded">
                    <input type="number" name="min_group_sp" placeholder="Min SP Grup (opsional)" class="text-xs border-gray-300 rounded">
                    <button type="submit" style="background-color:#4f46e5;color:#fff;" class="md:col-span-3 py-2 text-xs font-bold rounded">Kirim Broadcast ke Semua Member</button>
                </form>

                @if($activePromo)
                    <div class="pt-3 border-t">
                        <p class="text-xs font-bold text-indigo-800 mb-2">Member Qualified Promo "{{ $activePromo->title }}" ({{ count($qualifiedPromoMembers) }} Orang):</p>
                        @forelse($qualifiedPromoMembers as $qp)
                            <span class="inline-block mr-2 mb-1 px-2 py-1 bg-indigo-50 text-indigo-800 rounded text-xs">{{ $qp['user']->name }} ({{ $qp['user']->phone }})</span>
                        @empty
                            <p class="text-xs text-gray-500">Belum ada member memenuhi kriteria promo.</p>
                        @endforelse
                    </div>
                @endif
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg p-6 space-y-4">
                <h3 class="font-semibold text-gray-900">🏪 Persetujuan Seller, % SP & Catatan Non-SP</h3>
                @foreach($sellers as $seller)
                    <div class="border rounded-lg p-4 flex flex-col md:flex-row justify-between gap-4 text-xs">
                        <div>
                            <p class="font-bold text-sm">{{ $seller->store_name }} ({{ $seller->category }}) — <span class="uppercase">{{ $seller->status }}</span></p>
                            <p class="text-gray-600">Pemilik: {{ $seller->owner_name }} | SP Aktif: <strong>{{ $seller->sp_percentage }}%</strong></p>
                            @if($seller->requested_sp_percentage)<p class="text-indigo-600 font-bold">🔔 Minta ubah SP ke: {{ $seller->requested_sp_percentage }}%</p>@endif
                            @if($seller->requested_non_sp_notes)
                                <div class="mt-2 p-2 bg-yellow-50 border border-yellow-200 rounded">
                                    <p class="font-bold text-yellow-800">🔔 Pengajuan Catatan Produk Non-SP:</p>
                                    <p>{{ $seller->requested_non_sp_notes }}</p>
                                    <form method="POST" action="{{ route('admin.sellers.approve_non_sp', $seller->id) }}" class="mt-1">@csrf<button type="submit" style="background-color:#0d9488;color:#fff;" class="px-2.5 py-1 font-bold rounded">Setujui Catatan Non-SP</button></form>
                                </div>
                            @endif
                        </div>
                        <div class="flex flex-wrap items-center gap-2">
                            @if($seller->status === 'pending')
                                <form method="POST" action="{{ route('admin.sellers.approve', $seller->id) }}">@csrf<button type="submit" style="background-color:#16a34a;color:#fff;" class="px-3 py-2 font-bold rounded">✓ Setujui Seller</button></form>
                            @endif
                            <form method="POST" action="{{ route('admin.sellers.approve_sp', $seller->id) }}" class="flex items-center gap-1">
                                @csrf @method('PATCH')
                                <input type="number" step="0.01" min="5" name="sp_percentage" value="{{ $seller->requested_sp_percentage ?? $seller->sp_percentage }}" class="w-20 text-xs border-gray-300 rounded">
                                <button type="submit" style="background-color:#d97706;color:#fff;" class="px-3 py-2 font-bold rounded">Setujui %</button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="font-semibold text-gray-900 mb-3">📥 Inbox Saran & Masukan ({{ $inboxes->count() }})</h3>
                @forelse($inboxes as $inb)
                    <div class="py-2.5 border-b text-xs">
                        <p class="font-bold">{{ $inb->subject }} <span class="text-gray-400 font-normal">oleh {{ $inb->sender_name }} ({{ $inb->sender_role }})</span></p>
                        <p class="text-gray-700 mt-1">{{ $inb->message }}</p>
                    </div>
                @empty
                    <p class="text-xs text-gray-500">Inbox masih kosong.</p>
                @endforelse
            </div>

            @php $sPromo = \Illuminate\Support\Facades\DB::table('app_settings')->pluck('value', 'key'); @endphp
            <div class="bg-white shadow-sm sm:rounded-lg p-6 space-y-4 border-l-4 border-gray-800">
                <h3 class="font-semibold text-gray-900">🛡 Pengaturan Sistem, Logo, Promo & Keamanan</h3>
                <div class="text-xs text-green-700 bg-green-50 p-3 rounded border border-green-200">
                    <strong>Security Aktif:</strong> CSRF Protection • Rate Limiting Transaksi & Withdraw • Admin Guard (403) • Enkripsi Password Bcrypt • Validasi File Upload.
                </div>

                <form method="POST" action="{{ route('admin.logo.update') }}" enctype="multipart/form-data" class="flex flex-wrap items-center gap-2 pt-2 border-t">
                    @csrf
                    <span class="text-xs font-bold">Ganti Logo:</span>
                    <input type="file" name="logo" accept="image/*" required class="text-xs border rounded p-1">
                    <button type="submit" style="background-color:#2563eb;color:#fff;" class="px-3 py-1.5 text-xs font-bold rounded">Upload Logo</button>
                </form>

                <form method="POST" action="{{ route('admin.promo.update') }}" class="grid grid-cols-1 sm:grid-cols-4 gap-2 pt-2 border-t items-end text-xs">
                    @csrf
                    <label class="flex items-center gap-1 font-semibold"><input type="checkbox" name="leader_promo_active" {{ ($sPromo['leader_promo_active'] ?? '0') === '1' ? 'checked' : '' }}> Promo Leader Aktif</label>
                    <label class="flex items-center gap-1 font-semibold"><input type="checkbox" name="withdraw_anytime_test" {{ ($sPromo['withdraw_anytime_test'] ?? '0') === '1' ? 'checked' : '' }}> Bypass Jam WD (Tes)</label>
                    <div><span>Target SP Promo:</span><input type="number" name="leader_promo_group_sp" value="{{ $sPromo['leader_promo_group_sp'] ?? 1000000 }}" class="w-full text-xs border-gray-300 rounded"></div>
                    <div><span>Batas Bulan:</span><input type="text" name="leader_promo_until_month" value="{{ $sPromo['leader_promo_until_month'] ?? now()->addMonths(2)->format('Y-m') }}" class="w-full text-xs border-gray-300 rounded"></div>
                    <button type="submit" style="background-color:#dc2626;color:#fff;" class="sm:col-span-4 py-2 font-bold rounded">Simpan Pengaturan Operasional</button>
                </form>

                <div class="bg-white shadow-sm sm:rounded-lg p-6 space-y-3 border-l-4 border-teal-600"><h3 class="font-semibold text-gray-900">🔌 Integrasi Payment Gateway (Midtrans & Xendit)</h3><p class="text-xs text-gray-500">URL Webhook Otomatis: <code class="bg-gray-100 px-1.5 py-0.5 rounded">{{ url('/api/v1/payment/webhook') }}</code></p><form method="POST" action="{{ route('admin.pg.update') }}" class="grid grid-cols-1 md:grid-cols-2 gap-3 text-xs">@csrf<div><label class="font-semibold">Gateway Aktif:</label><select name="pg_active_gateway" class="w-full text-xs border-gray-300 rounded mt-1"><option value="midtrans" {{ ($sPromo["pg_active_gateway"] ?? "midtrans") === "midtrans" ? "selected" : "" }}>Midtrans (Tahap Awal)</option><option value="xendit" {{ ($sPromo["pg_active_gateway"] ?? "") === "xendit" ? "selected" : "" }}>Xendit (Tahap Lanjutan)</option></select></div><div class="flex items-center pt-5"><label class="flex items-center gap-2 font-semibold"><input type="checkbox" name="pg_is_production" {{ ($sPromo["pg_is_production"] ?? "0") === "1" ? "checked" : "" }}> Mode Production (Live Uang Asli)</label></div><div><label>Midtrans Server Key:</label><input type="password" name="midtrans_server_key" value="{{ $sPromo["midtrans_server_key"] ?? "" }}" placeholder="SB-Mid-server-..." class="w-full text-xs border-gray-300 rounded mt-1"></div><div><label>Midtrans Client Key:</label><input type="text" name="midtrans_client_key" value="{{ $sPromo["midtrans_client_key"] ?? "" }}" placeholder="SB-Mid-client-..." class="w-full text-xs border-gray-300 rounded mt-1"></div><div><label>Xendit Secret API Key:</label><input type="password" name="xendit_secret_key" value="{{ $sPromo["xendit_secret_key"] ?? "" }}" placeholder="xnd_development_..." class="w-full text-xs border-gray-300 rounded mt-1"></div><div><label>Xendit Callback Token:</label><input type="password" name="xendit_callback_token" value="{{ $sPromo["xendit_callback_token"] ?? "" }}" placeholder="Token Verifikasi Webhook" class="w-full text-xs border-gray-300 rounded mt-1"></div><button type="submit" style="background-color:#0d9488;color:#fff;" class="md:col-span-2 py-2 font-bold rounded">Simpan Kredensial Payment Gateway</button></form></div>

                <form method="POST" action="{{ route('admin.closing.run') }}" onsubmit="return confirm('Jalankan tutup buku bulan ini?')" class="pt-2 border-t">
                    @csrf
                    <button type="submit" style="background-color:#7c3aed;color:#fff;" class="px-4 py-2 text-xs font-bold rounded">🔒 Jalankan Tutup Buku Bulanan Sekarang</button>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
