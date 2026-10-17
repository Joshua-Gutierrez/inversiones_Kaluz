@extends('layouts.torre-control')

@section('titulo', 'Panel General - Taxi Kaluz')
@section('encabezado', 'Panel Operativo en Tiempo Real')

@section('content')
<div class="space-y-6">
    <!-- Barra Superior de Acciones Rápidas con Search de la Carpeta General -->
    <div class="flex flex-col md:flex-row items-center justify-between gap-4 bg-white p-4 rounded-2xl border border-slate-200/80 shadow-xs">
        <div class="w-full md:w-96">
            <x-general.search placeholder="Buscar placa, conductor o viaje..." />
        </div>
        <div class="flex items-center gap-2 w-full md:w-auto justify-end">
            <a href="{{ route('general.index') }}" class="btn-secondary text-xs">
                🎨 Ver Guía de Estilos General
            </a>
            <a href="{{ route('despacho.pwa') }}" class="btn-primary text-xs">
                📱 Abrir Radar PWA
            </a>
        </div>
    </div>

    <!-- Métricas Principales (Kpis con Montserrat + Tokens de Color) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
        <!-- Tarjeta 1: Viajes Hoy -->
        <div class="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-sm relative overflow-hidden transition hover:shadow-md">
            <div class="absolute top-0 left-0 h-1.5 w-full bg-[#08183D]"></div>
            <div class="flex items-center justify-between">
                <span class="font-label text-xs uppercase tracking-wider text-[#64748B]">Viajes Hoy</span>
                <span class="p-2 rounded-xl bg-[#08183D]/5 text-[#08183D]">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                </span>
            </div>
            <p class="font-headline text-3xl font-bold text-[#08183D] mt-3">142</p>
            <p class="font-body text-xs text-emerald-600 mt-1 flex items-center gap-1 font-semibold">
                <span>↑ +18%</span> <span class="text-[#64748B] font-normal">vs. ayer</span>
            </p>
        </div>

        <!-- Tarjeta 2: Conductores Habilitados -->
        <div class="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-sm relative overflow-hidden transition hover:shadow-md">
            <div class="absolute top-0 left-0 h-1.5 w-full bg-emerald-500"></div>
            <div class="flex items-center justify-between">
                <span class="font-label text-xs uppercase tracking-wider text-[#64748B]">Flota Activa</span>
                <span class="p-2 rounded-xl bg-emerald-50 text-emerald-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </span>
            </div>
            <p class="font-headline text-3xl font-bold text-[#08183D] mt-3">287 / 300</p>
            <p class="font-body text-xs text-[#64748B] mt-1">Socios con pase al día</p>
        </div>

        <!-- Tarjeta 3: Candados S/ 1.00 -->
        <div class="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-sm relative overflow-hidden transition hover:shadow-md">
            <div class="absolute top-0 left-0 h-1.5 w-full bg-[#E31B23]"></div>
            <div class="flex items-center justify-between">
                <span class="font-label text-xs uppercase tracking-wider text-[#64748B]">Candado S/ 1.00</span>
                <span class="p-2 rounded-xl bg-red-50 text-[#E31B23]">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                </span>
            </div>
            <p class="font-headline text-3xl font-bold text-[#E31B23] mt-3">13</p>
            <p class="font-body text-xs text-[#E31B23] mt-1 font-semibold">Socios con acceso restringido</p>
        </div>

        <!-- Tarjeta 4: Turismo y Combis -->
        <div class="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-sm relative overflow-hidden transition hover:shadow-md">
            <div class="absolute top-0 left-0 h-1.5 w-full bg-[#081328]"></div>
            <div class="flex items-center justify-between">
                <span class="font-label text-xs uppercase tracking-wider text-[#64748B]">Combis en Ruta</span>
                <span class="p-2 rounded-xl bg-slate-100 text-[#081328]">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path></svg>
                </span>
            </div>
            <p class="font-headline text-3xl font-bold text-[#08183D] mt-3">8 Combis</p>
            <p class="font-body text-xs text-[#64748B] mt-1">Con prioridad flota propia</p>
        </div>
    </div>

    <!-- Secciones Operativas por Módulos -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Radar & Subasta Rápida -->
        <x-general.card title="Radar de Solicitudes en Vivo" subtitle="Asignación atómica de viajes en Huancayo">
            <div class="space-y-3">
                <div class="p-4 rounded-xl bg-[#F8FAFC] border border-slate-200 flex items-center justify-between">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="font-headline font-bold text-sm text-[#08183D]">Viaje #1024</span>
                            <x-general.badge variant="red">S/ 12.00</x-general.badge>
                        </div>
                        <p class="font-body text-xs text-[#64748B] mt-1">Real Plaza Huancayo ➔ El Tambo (Jr. Ayacucho)</p>
                    </div>
                    <a href="{{ route('despacho.pwa') }}" class="btn-primary text-xs">
                        Ver Subasta
                    </a>
                </div>

                <div class="p-4 rounded-xl bg-[#F8FAFC] border border-slate-200 flex items-center justify-between">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="font-headline font-bold text-sm text-[#08183D]">Tour Turístico #402</span>
                            <x-general.badge variant="navy">S/ 85.00</x-general.badge>
                        </div>
                        <p class="font-body text-xs text-[#64748B] mt-1">Nevado Huaytapallana • Combi Flota Propia (14 pax)</p>
                    </div>
                    <a href="{{ route('turismo.index') }}" class="btn-secondary text-xs">
                        Ver Ruta GPS
                    </a>
                </div>
            </div>
        </x-general.card>

        <!-- Estado del Sistema y Accesos a Módulos -->
        <x-general.card title="Módulos del Sistema Hackathon" subtitle="Desarrollo distribuido con cero conflictos Git">
            <div class="grid grid-cols-2 gap-3">
                <a href="{{ route('despacho.pwa') }}" class="p-3.5 rounded-xl border border-slate-200 hover:border-[#08183D] transition group bg-[#F8FAFC]">
                    <span class="font-headline font-bold text-xs text-[#08183D] group-hover:text-[#E31B23] block">📱 Zona Dev 1</span>
                    <span class="font-body text-[11px] text-[#64748B]">Subasta Atómica & PWA</span>
                </a>

                <a href="{{ route('finanzas.padron') }}" class="p-3.5 rounded-xl border border-slate-200 hover:border-[#08183D] transition group bg-[#F8FAFC]">
                    <span class="font-headline font-bold text-xs text-[#08183D] group-hover:text-[#E31B23] block">🔒 Zona Dev 2</span>
                    <span class="font-body text-[11px] text-[#64748B]">Padrón 300 & Candado</span>
                </a>

                <a href="{{ route('turismo.index') }}" class="p-3.5 rounded-xl border border-slate-200 hover:border-[#08183D] transition group bg-[#F8FAFC]">
                    <span class="font-headline font-bold text-xs text-[#08183D] group-hover:text-[#E31B23] block">🚐 Zona Dev 3</span>
                    <span class="font-body text-[11px] text-[#64748B]">Combi Tours & Rastreo GPS</span>
                </a>

                <a href="{{ route('bot.simulador') }}" class="p-3.5 rounded-xl border border-slate-200 hover:border-[#08183D] transition group bg-[#F8FAFC]">
                    <span class="font-headline font-bold text-xs text-[#08183D] group-hover:text-[#E31B23] block">💬 Zona Dev 4</span>
                    <span class="font-body text-[11px] text-[#64748B]">Simulador WA & KPIs</span>
                </a>
            </div>

            <div class="mt-4 pt-4 border-t border-slate-100 flex items-center justify-between">
                <span class="font-body text-xs text-[#64748B]">Componentes compartidos en carpeta general:</span>
                <a href="{{ route('general.index') }}" class="text-xs font-bold font-headline text-[#E31B23] hover:underline">
                    Ver Catálogo Completo ➔
                </a>
            </div>
        </x-general.card>
    </div>
</div>
@endsection
