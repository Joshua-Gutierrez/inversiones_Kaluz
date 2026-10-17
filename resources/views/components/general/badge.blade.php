@props([
    'variant' => 'navy', // navy (#08183D), red (#E31B23), neutral (#64748B), outline
    'icon' => null,
])

@php
    $style = match($variant) {
        'red', 'accent', 'kaluz' => 'bg-[#E31B23] text-white',
        'neutral', 'slate' => 'bg-[#F1F5F9] text-[#475569] border border-slate-200',
        'outline' => 'bg-transparent text-[#08183D] border border-[#08183D]',
        default => 'bg-[#08183D] text-white',
    };
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-label font-semibold {$style}"]) }}>
    @if($icon)
        <span>{!! $icon !!}</span>
    @endif
    {{ $slot }}
</span>
