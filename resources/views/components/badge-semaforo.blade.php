@props(['estado' => 'habilitado', 'texto' => null])

@php
    $estilos = match($estado) {
        'habilitado', 'verde' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
        'alerta', 'amarillo' => 'bg-amber-50 text-amber-700 border-amber-200',
        'bloqueado', 'rojo' => 'bg-[#E31B23]/10 text-[#E31B23] border-[#E31B23]/30',
        default => 'bg-[#F1F5F9] text-[#64748B] border-slate-200',
    };

    $etiqueta = $texto ?? match($estado) {
        'habilitado', 'verde' => 'Habilitado',
        'alerta', 'amarillo' => 'Por Vencer',
        'bloqueado', 'rojo' => 'Candado S/ 1.00',
        default => ucfirst($estado),
    };
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-label font-semibold border {$estilos}"]) }}>
    <span class="w-1.5 h-1.5 rounded-full bg-current"></span>
    {{ $etiqueta }}
</span>
