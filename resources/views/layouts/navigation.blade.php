<nav class="bg-white border-b border-gray-100">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex items-center">
                <a href="{{ route('dashboard') }}" class="flex items-center gap-2">
                    <x-application-logo class="block h-10 w-10 fill-current text-gray-800" />
                    <span class="font-extrabold text-indigo-700 tracking-wide">SWIZH</span>
                </a>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('profile.edit') }}" class="text-xs font-semibold text-gray-700 px-2.5 py-1.5 rounded bg-gray-100">👤 Profil</a>
                <a href="{{ route('terms') }}" class="text-xs font-semibold text-gray-600">📜 T&C</a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="text-xs font-semibold text-red-600">Keluar</button>
                </form>
            </div>
        </div>
    </div>
</nav>
