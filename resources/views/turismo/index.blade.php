@extends('layouts.torre-control')

@section('titulo', 'Paquetes Turísticos y Flota Propia - Taxi Kaluz')
@section('encabezado', '👨‍💻 Zona Dev 3: Paquetes Turísticos, Combis y Prioridad Flota Propia')

@section('content')
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <x-tarjeta-kaluz titulo="Tour Nevado Huaytapallana" subtitulo="Prioridad automática para Combis / Flota Propia Kaluz">
            <div class="flex items-center justify-between mt-2">
                <div>
                    <span class="text-xs bg-blue-100 text-blue-800 px-2.5 py-1 rounded-full font-semibold">Flota Propia Prioritaria</span>
                    <p class="text-sm text-slate-600 mt-2">Duración: 8 horas • Capacidad hasta 14 pasajeros</p>
                </div>
                <div class="text-right">
                    <p class="text-xs text-slate-400">Tarifa Combi</p>
                    <p class="text-2xl font-extrabold text-slate-800">S/ 280.00</p>
                </div>
            </div>
            <div class="mt-4 pt-3 border-t border-slate-100 flex justify-end">
                <a href="{{ route('turismo.rastreo', ['id' => 'KALUZ-101']) }}" class="text-xs font-bold text-red-600 hover:underline">
                    📍 Ver Rastreo GPS en vivo (/rastreo/KALUZ-101) →
                </a>
            </div>
        </x-tarjeta-kaluz>

        <x-tarjeta-kaluz titulo="Circuito Valle del Mantaro (Ingenio / Concepción)" subtitulo="Disponible en Taxi Ejecutivo y Combi Turística">
            <div class="flex items-center justify-between mt-2">
                <div>
                    <span class="text-xs bg-emerald-100 text-emerald-800 px-2.5 py-1 rounded-full font-semibold">Alta Demanda</span>
                    <p class="text-sm text-slate-600 mt-2">Duración: 6 horas • Paradas turísticas guiadas</p>
                </div>
                <div class="text-right">
                    <p class="text-xs text-slate-400">Tarifa Taxi</p>
                    <p class="text-2xl font-extrabold text-slate-800">S/ 150.00</p>
                </div>
            </div>
            <div class="mt-4 pt-3 border-t border-slate-100 flex justify-end">
                <a href="{{ route('turismo.rastreo', ['id' => 'KALUZ-202']) }}" class="text-xs font-bold text-red-600 hover:underline">
                    📍 Ver Rastreo GPS en vivo (/rastreo/KALUZ-202) →
                </a>
            </div>
        </x-tarjeta-kaluz>
    </div>
@endsection
