<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard Member') }}
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <p class="text-sm text-gray-500">Status Keanggotaan</p>
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-800">
                            ● Member Aktif
                        </span>
                    </div>
                    <div class="text-right">
                        <p class="text-sm text-gray-500">Kode Referral Anda</p>
                        <p class="text-lg font-bold text-indigo-600">{{ Auth::user()->referral_code ?? 'Belum ada' }}</p>
                    </div>
                </div>

                @if(Auth::user()->referral_code)
                <div class="mt-4 pt-4 border-t border-gray-100">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Link Undangan Referral (1 Level)</label>
                    <div class="flex gap-2">
                        <input type="text" id="refLink" readonly value="{{ url('/register?ref=' . Auth::user()->referral_code) }}" class="w-full text-sm border-gray-300 rounded-md bg-gray-50 text-gray-600">
                        <button onclick="navigator.clipboard.writeText(document.getElementById('refLink').value); alert('Link referral berhasil disalin!');" class="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700 shrink-0">
                            Salin Link
                        </button>
                    </div>
                </div>
                @endif
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">
                    Member yang Anda Ajak ({{ Auth::user()->referrals()->count() }} Orang)
                </h3>

                @if(Auth::user()->referrals()->count() > 0)
                    <div class="divide-y divide-gray-200">
                        @foreach(Auth::user()->referrals()->latest()->get() as $member)
                            <div class="py-3 flex justify-between items-center">
                                <div>
                                    <p class="font-medium text-gray-800">{{ $member->name }}</p>
                                    <p class="text-xs text-gray-500">Bergabung: {{ $member->created_at->format('d M Y, H:i') }}</p>
                                </div>
                                <span class="text-xs px-2 py-1 bg-green-50 text-green-700 rounded">Aktif</span>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-sm text-gray-500">Belum ada member yang mendaftar menggunakan link referral Anda.</p>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
