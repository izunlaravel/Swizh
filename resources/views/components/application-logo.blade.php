@php
    $customLogo = \Illuminate\Support\Facades\DB::table('app_settings')->where('key', 'site_logo')->value('value');
@endphp

@if($customLogo && file_exists(public_path($customLogo)))
    <img src="{{ asset($customLogo) }}?v={{ filemtime(public_path($customLogo)) }}" alt="Logo Swizh" {{ $attributes->merge(['class' => 'h-9 w-auto object-contain']) }}>
@else
    <span {{ $attributes->merge(['class' => 'font-black text-xl tracking-wider text-indigo-600']) }}>SWIZH</span>
@endif
