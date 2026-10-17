@props(['titulo' => '', 'subtitulo' => null])

<div {{ $attributes->merge(['class' => 'bg-white rounded-2xl shadow-sm border border-slate-200/80 p-6 transition-all duration-200 hover:shadow-md']) }}>
    @if($titulo)
        <div class="mb-4">
            <h3 class="font-headline text-base font-bold text-[#08183D] tracking-tight">{{ $titulo }}</h3>
            @if($subtitulo)
                <p class="font-body text-xs text-[#64748B] mt-0.5">{{ $subtitulo }}</p>
            @endif
        </div>
    @endif
    <div class="font-body">
        {{ $slot }}
    </div>
</div>
