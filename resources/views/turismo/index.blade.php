@extends('layouts.torre-control')

@section('titulo', 'Paquetes Turísticos y Flota Propia - Taxi Kaluz')
@section('encabezado', '👨‍💻 Zona Dev 3: Paquetes Turísticos, Combis y Prioridad Flota Propia')

@section('content')
    <div class="space-y-6">
        <!-- Barra de Búsqueda de Tours -->
        <div class="flex flex-col md:flex-row items-center justify-between gap-4 bg-white p-4 rounded-2xl border border-slate-200/80 shadow-xs">
            <div class="w-full md:w-96">
                <x-general.search placeholder="Buscar circuito turístico o combi..." />
            </div>
            <div class="flex items-center gap-2">
                <x-general.button variant="primary" size="sm">
                    <span>+ Crear Paquete Turístico</span>
                </x-general.button>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <x-tarjeta-kaluz titulo="Tour Nevado Huaytapallana" subtitulo="Prioridad automática para Combis / Flota Propia Kaluz">
                <div class="flex items-center justify-between mt-2">
                    <div>
                        <x-general.badge variant="navy">Flota Propia Prioritaria</x-general.badge>
                        <p class="font-body text-xs text-[#64748B] mt-2">Duración: 8 horas • Capacidad hasta 14 pasajeros</p>
                    </div>
                    <div class="text-right">
                        <p class="font-label text-xs text-[#64748B] uppercase">Tarifa Combi</p>
                        <p class="font-headline text-2xl font-black text-[#08183D]">S/ 280.00</p>
                    </div>
                </div>
                <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between">
                    <span class="text-xs font-label text-emerald-600 font-semibold">● 3 Combis Disponibles</span>
                    <a href="{{ route('turismo.rastreo', ['id' => 'KALUZ-101']) }}" class="btn-accent text-xs">
                        📍 Rastreo GPS en Vivo
                    </a>
                </div>
            </x-tarjeta-kaluz>

            <x-tarjeta-kaluz titulo="Circuito Valle del Mantaro (Ingenio / Concepción)" subtitulo="Disponible en Taxi Ejecutivo y Combi Turística">
                <div class="flex items-center justify-between mt-2">
                    <div>
                        <x-general.badge variant="red">Alta Demanda Fin de Semana</x-general.badge>
                        <p class="font-body text-xs text-[#64748B] mt-2">Duración: 6 horas • Paradas gastronómicas y artesanales</p>
                    </div>
                    <div class="text-right">
                        <p class="font-label text-xs text-[#64748B] uppercase">Tarifa Taxi</p>
                        <p class="font-headline text-2xl font-black text-[#08183D]">S/ 150.00</p>
                    </div>
                </div>
                <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between">
                    <span class="text-xs font-label text-emerald-600 font-semibold">● 8 Unidades Disponibles</span>
                    <a href="{{ route('turismo.rastreo', ['id' => 'KALUZ-202']) }}" class="btn-primary text-xs">
                        📍 Rastreo GPS en Vivo
                    </a>
                </div>
            </x-tarjeta-kaluz>
        </div>
    </div>
@endsection
