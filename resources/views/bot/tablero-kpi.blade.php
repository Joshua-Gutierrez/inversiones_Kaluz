@extends('layouts.torre-control')

@section('titulo', 'Tablero de KPIs - Torre de Control Kaluz')
@section('encabezado', '👨‍💻 Zona Dev 4: Home de Torre de Control e Indicadores Clave (KPIs)')

@section('content')
    <div class="space-y-6">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
            <x-tarjeta-kaluz titulo="Viajes Hoy" subtitulo="Completados por WhatsApp + Web">
                <p class="font-headline text-3xl font-bold text-[#08183D]">142</p>
                <p class="font-body text-xs text-emerald-600 mt-1 font-semibold">↑ +18% vs. promedio semana</p>
            </x-tarjeta-kaluz>

            <x-tarjeta-kaluz titulo="Recaudación S/ 1.00" subtitulo="Comisiones Yape validadas">
                <p class="font-headline text-3xl font-bold text-emerald-600">S/ 142.00</p>
                <p class="font-body text-xs text-[#64748B] mt-1">100% bancarizado vía Yape</p>
            </x-tarjeta-kaluz>

            <x-tarjeta-kaluz titulo="Tiempo Respuesta Bot" subtitulo="Promedio de asignación">
                <p class="font-headline text-3xl font-bold text-[#08183D]">14 seg</p>
                <p class="font-body text-xs text-[#64748B] mt-1">NLP + Subasta Atómica</p>
            </x-tarjeta-kaluz>

            <x-tarjeta-kaluz titulo="Tours Flota Propia" subtitulo="Ocupación de combis hoy">
                <p class="font-headline text-3xl font-bold text-[#E31B23]">85%</p>
                <p class="font-body text-xs text-[#64748B] mt-1">Alta rentabilidad turística</p>
            </x-tarjeta-kaluz>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <x-general.card title="Rendimiento del Bot de WhatsApp" subtitle="Canal principal de adquisición de carreras">
                <div class="space-y-3 font-body text-xs">
                    <div class="flex justify-between items-center p-3 bg-[#F8FAFC] rounded-xl border border-slate-200">
                        <span class="font-semibold text-[#08183D]">Mensajes procesados hoy</span>
                        <span class="font-headline font-bold text-[#08183D]">530</span>
                    </div>
                    <div class="flex justify-between items-center p-3 bg-[#F8FAFC] rounded-xl border border-slate-200">
                        <span class="font-semibold text-[#08183D]">Carreras convertidas</span>
                        <span class="font-headline font-bold text-emerald-600">142 (26.8%)</span>
                    </div>
                    <div class="flex justify-between items-center p-3 bg-[#F8FAFC] rounded-xl border border-slate-200">
                        <span class="font-semibold text-[#08183D]">Tours turísticos cotizados</span>
                        <span class="font-headline font-bold text-[#E31B23]">24</span>
                    </div>
                </div>
            </x-general.card>

            <x-general.card title="Acciones de Control General" subtitle="Operaciones rápidas de la central">
                <div class="flex flex-wrap gap-2 pt-2">
                    <x-general.button variant="primary" size="sm" href="{{ route('general.index') }}">
                        🎨 Abrir Guía de Estilos General
                    </x-general.button>
                    <x-general.button variant="accent" size="sm" href="{{ route('bot.simulador') }}">
                        💬 Probar Simulador Bot
                    </x-general.button>
                    <x-general.button variant="secondary" size="sm" href="{{ route('finanzas.padron') }}">
                        🔒 Ver Padrón y Deudores
                    </x-general.button>
                </div>
            </x-general.card>
        </div>
    </div>
@endsection
