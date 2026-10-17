@extends('layouts.torre-control')

@section('titulo', 'Rastreo GPS en Vivo - Taxi Kaluz')
@section('encabezado', '👨‍💻 Zona Dev 3: Rastreo GPS de Unidad en Tiempo Real (#' . $viajeId . ')')

@section('content')
    <x-tarjeta-kaluz titulo="Monitoreo satelital del viaje #{{ $viajeId }}" subtitulo="Enlace compartible por WhatsApp para seguridad del pasajero">
        <div class="bg-slate-900 text-white rounded-xl h-80 flex flex-col items-center justify-center p-6 text-center">
            <span class="text-4xl mb-2">🛰️</span>
            <h4 class="text-lg font-bold">Mapa GPS en Tiempo Real — Viaje {{ $viajeId }}</h4>
            <p class="text-xs text-slate-400 max-w-md mt-1">
                Aquí se renderiza el mapa interactivo con la ubicación actual de la unidad asignada y ruta estimada.
            </p>
        </div>
    </x-tarjeta-kaluz>
@endsection
