@php
    $user = Auth::user();
    $period = now()->format('Y-m');
    $spService = app(\App\Services\SwizhPointService::class);
    $stats = $spService->getMemberMonthlyStats($user->id, $period);
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
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Dashboard Swizh</h2>
            @if($user->is_admin)
                <a href="{{ route('admin.sellers.index') }}" class="px-3 py-1.5 bg-amber-600 text-white text-xs font-semibold rounded-md">Panel Admin Seller</a>
            @endif
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="bg-white shadow-sm sm:rounded-lg p-6 border-l-4 border-green-500">
                    <p class="text-xs text-gray-500 uppercase">Saldo Wallet Aktif (1 SP = Rp 1)</p>
                    <p class="text-2xl font-bold text-gray-900 mt-1">Rp {{ number_format($user->wallet_balance, 0, ',', '.') }}</p>
                </div>
                <div class="bg-white shadow-sm sm:rounded-lg p-6 border-l-4 border-amber-500">
                    <p class="text-xs text-gray-500 uppercase">Bonus Pending ({{ $period }})</p>
                    <p class="text-2xl font-bold text-amber-600 mt-1">Rp {{ number_format($pendingBonus, 0, ',', '.') }}</p>
                    <p class="text-xs text-gray-500 mt-1">{{ $stats['personal_sp'] >= 1 ? 'Syarat 1 SP terpenuhi' : 'Belanja min 1 SP untuk cair' }}</p>
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
