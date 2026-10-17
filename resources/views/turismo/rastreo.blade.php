@extends('layouts.torre-control')

@section('titulo', 'Rastreo GPS en Vivo - Taxi Kaluz')
@section('encabezado', '👨‍💻 Zona Dev 3: Rastreo Satelital en Tiempo Real (#' . $viajeId . ')')

@section('content')
    <div class="space-y-6">
        <x-tarjeta-kaluz titulo="Monitoreo Satelital del Viaje #{{ $viajeId }}" subtitulo="Enlace oficial compartible por WhatsApp para seguridad del cliente y central">
            <div class="bg-[#08183D] border border-white/10 text-white rounded-2xl h-80 flex flex-col items-center justify-center p-6 text-center shadow-xl relative overflow-hidden">
                <div class="absolute top-4 left-4 flex items-center gap-2">
                    <span class="w-3 h-3 rounded-full bg-emerald-400 animate-ping"></span>
                    <span class="text-xs font-label text-emerald-300 font-bold">SEÑAL GPS ACTIVA</span>
                </div>

                <div class="w-16 h-16 rounded-2xl bg-white/10 flex items-center justify-center text-3xl mb-3 shadow-inner">
                    🛰️
                </div>
                <h4 class="font-headline text-xl font-bold text-white">Unidad en Ruta — Viaje {{ $viajeId }}</h4>
                <p class="font-body text-xs text-slate-300 max-w-md mt-1">
                    Calle Real ➔ Jr. Ayacucho (El Tambo). Velocidad actual: 38 km/h.
                </p>
                
                <div class="mt-4 flex gap-2">
                    <x-general.button variant="accent" size="sm">
                        <span>📲 Compartir por WhatsApp</span>
                    </x-general.button>
                    <x-general.button variant="secondary" size="sm">
                        <span>Centrar Mapa</span>
                    </x-general.button>
                </div>
            </div>
        </x-tarjeta-kaluz>
    </div>
@endsection
