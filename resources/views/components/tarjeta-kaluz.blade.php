@props(['titulo' => '', 'subtitulo' => null])

<div {{ $attributes->merge(['class' => 'bg-white rounded-xl shadow-sm border border-slate-200 p-5']) }}>
    @if($titulo)
        <div class="mb-3">
            <h3 class="text-base font-bold text-slate-800">{{ $titulo }}</h3>
            @if($subtitulo)
                <p class="text-xs text-slate-500">{{ $subtitulo }}</p>
            @endif
        </div>
    @endif
    <div>
        {{ $slot }}
    </div>
</div>
