@php
    $customLogo = \Illuminate\Support\Facades\DB::table('app_settings')->where('key', 'site_logo')->value('value');
@endphp

@if($customLogo && file_exists(public_path($customLogo)))
    <img src="{{ asset($customLogo) }}?v={{ filemtime(public_path($customLogo)) }}" alt="Logo Swizh" style="width:40px;height:40px;min-width:40px;max-width:40px;border-radius:9999px;object-fit:cover;" class="border border-gray-200 shadow-sm">
@else
    <span style="width:40px;height:40px;border-radius:9999px;" class="inline-flex items-center justify-center bg-indigo-600 text-white font-black text-xs tracking-wider shadow-sm">SWZ</span>
@endif
