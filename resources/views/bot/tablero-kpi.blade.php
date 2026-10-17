@extends('layouts.torre-control')

@section('titulo', 'Tablero de KPIs - Torre de Control Kaluz')
@section('encabezado', '👨‍💻 Zona Dev 4: Home de Torre de Control e Indicadores Clave (KPIs)')

@section('content')
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <x-tarjeta-kaluz titulo="Viajes Hoy" subtitulo="Completados por WhatsApp + Web">
            <p class="text-3xl font-extrabold text-slate-800">142</p>
        </x-tarjeta-kaluz>
        <x-tarjeta-kaluz titulo="Recaudación S/ 1.00" subtitulo="Comisiones Yape validadas">
            <p class="text-3xl font-extrabold text-emerald-600">S/ 142.00</p>
        </x-tarjeta-kaluz>
        <x-tarjeta-kaluz titulo="Tiempo Respuesta Bot" subtitulo="Promedio de asignación">
            <p class="text-3xl font-extrabold text-blue-600">14 seg</p>
        </x-tarjeta-kaluz>
        <x-tarjeta-kaluz titulo="Tours Flota Propia" subtitulo="Ocupación de combis hoy">
            <p class="text-3xl font-extrabold text-amber-600">85%</p>
        </x-tarjeta-kaluz>
    </div>
@endsection
