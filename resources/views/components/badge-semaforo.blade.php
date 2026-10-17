@props(['estado' => 'habilitado', 'texto' => null])

@php
    $estilos = match($estado) {
        'habilitado', 'verde' => 'bg-emerald-100 text-emerald-800 border-emerald-300',
        'alerta', 'amarillo' => 'bg-amber-100 text-amber-800 border-amber-300',
        'bloqueado', 'rojo' => 'bg-red-100 text-red-800 border-red-300',
        default => 'bg-slate-100 text-slate-800 border-slate-300',
    };

    $etiqueta = $texto ?? match($estado) {
        'habilitado', 'verde' => 'Habilitado',
        'alerta', 'amarillo' => 'Por Vencer',
        'bloqueado', 'rojo' => 'Candado S/ 1.00',
        default => ucfirst($estado),
    };
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold border {$estilos}"]) }}>
    <span class="w-1.5 h-1.5 rounded-full bg-current"></span>
    {{ $etiqueta }}
</span>
