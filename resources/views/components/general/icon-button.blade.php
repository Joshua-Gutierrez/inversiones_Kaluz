@props([
    'variant' => 'primary', // primary (#08183D), accent (#E31B23), inverted (#081328), secondary (#E2E8F0)
    'type' => 'button',
    'title' => null,
])

@php
    $variantClass = match($variant) {
        'accent', 'kaluz' => 'bg-[#E31B23] text-white hover:bg-[#C6161E]',
        'inverted' => 'bg-[#081328] text-white hover:bg-[#040914]',
        'secondary' => 'bg-[#E2E8F0] text-[#08183D] hover:bg-[#CBD5E1]',
        default => 'bg-[#08183D] text-white hover:bg-[#0D255B]',
    };
@endphp

<button type="{{ $type }}" title="{{ $title }}" {{ $attributes->merge(['class' => "w-10 h-10 rounded-xl flex items-center justify-center transition-all duration-200 shadow-sm {$variantClass}"]) }}>
    {{ $slot }}
</button>
