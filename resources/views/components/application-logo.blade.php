@php
    $customLogo = \Illuminate\Support\Facades\DB::table('app_settings')->where('key', 'site_logo')->value('value');
@endphp

@if($customLogo && file_exists(public_path($customLogo)))
    <img src="{{ asset($customLogo) }}?v={{ filemtime(public_path($customLogo)) }}" alt="Logo Swizh" {{ $attributes->merge(['class' => 'h-10 w-10 rounded-full object-cover border border-gray-200 shadow-sm']) }}>
@else
    <span {{ $attributes->merge(['class' => 'inline-flex items-center justify-center h-10 w-10 rounded-full bg-indigo-600 text-white font-black text-xs tracking-wider shadow-sm']) }}>SWZ</span>
@endif
