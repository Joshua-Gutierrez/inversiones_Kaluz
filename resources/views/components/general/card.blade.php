@props([
    'title' => null,
    'subtitle' => null,
])

<div {{ $attributes->merge(['class' => 'bg-white rounded-2xl border border-slate-200/80 shadow-sm p-6 transition-all duration-200']) }}>
    @if($title)
        <div class="mb-4">
            <h3 class="font-headline text-lg font-bold text-[#08183D] tracking-tight">{{ $title }}</h3>
            @if($subtitle)
                <p class="font-body text-xs text-[#64748B] mt-0.5">{{ $subtitle }}</p>
            @endif
        </div>
    @endif
    <div>
        {{ $slot }}
    </div>
</div>
