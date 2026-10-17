@props([
    'variant' => 'primary', // primary, secondary, inverted, outlined, accent
    'size' => 'md',        // sm, md, lg
    'type' => 'button',
    'href' => null,
])

@php
    $baseStyles = "inline-flex items-center justify-center font-label font-semibold rounded-xl transition-all duration-200 gap-2 select-none focus:outline-none focus:ring-2 focus:ring-offset-2";
    
    $sizeStyles = match($size) {
        'sm' => 'text-xs px-3 py-1.5',
        'lg' => 'text-base px-6 py-3',
        default => 'text-sm px-4 py-2.5',
    };

    $variantStyles = match($variant) {
        'secondary' => 'bg-[#E2E8F0] text-[#08183D] hover:bg-[#CBD5E1] focus:ring-slate-400',
        'inverted' => 'bg-[#081328] text-white hover:bg-[#040914] focus:ring-[#081328]',
        'outlined' => 'bg-transparent text-[#08183D] border-2 border-[#08183D] hover:bg-slate-100 focus:ring-[#08183D]',
        'accent', 'kaluz' => 'bg-[#E31B23] text-white hover:bg-[#C6161E] shadow-sm hover:shadow-md focus:ring-[#E31B23]',
        default => 'bg-[#08183D] text-white hover:bg-[#0D255B] shadow-sm hover:shadow-md focus:ring-[#08183D]',
    };

    $classes = "{$baseStyles} {$sizeStyles} {$variantStyles}";
@endphp

@if($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>
        {{ $slot }}
    </button>
@endif
