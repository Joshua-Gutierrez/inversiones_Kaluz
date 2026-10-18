@extends('layouts.pwa-mobile')

@section('titulo', 'Acceso Habilitado - KALUZ')
@section('tema', 'claro')

@section('header_centro')
    <span class="text-xs font-headline font-bold text-slate-200">Torre de Control - KALUZ</span>
@endsection

@section('content')
<div class="space-y-4 pt-1">
    {{-- Tarjeta 1: ¡Desbloqueado! Acceso Habilitado (Imagen 2 derecha) --}}
    <div class="bg-white rounded-3xl shadow-md border border-slate-200/80 p-6 text-center space-y-3">
        <div class="w-14 h-14 rounded-full bg-emerald-500 text-white flex items-center justify-center mx-auto shadow-md shadow-emerald-500/30">
            <i class="ri-check-line text-3xl font-bold"></i>
        </div>
        <h1 class="font-headline font-black text-2xl text-[#0A1329] leading-tight">
            ¡Desbloqueado!<br>Acceso Habilitado
        </h1>
    </div>

    {{-- Tarjeta 2: Perfil del Conductor Habilitado --}}
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200/80 p-4 flex items-center gap-3.5">
        <div class="w-12 h-12 rounded-full bg-gradient-to-br from-[#0A1329] to-[#1E3A8A] text-white flex items-center justify-center font-headline font-bold text-lg shrink-0">
            {{ mb_strtoupper(mb_substr($conductor->nombre ?? 'C', 0, 1)) }}
        </div>
        <div class="min-w-0 flex-1">
            <p class="font-headline font-extrabold text-base text-[#0A1329] truncate">
                {{ $conductor->nombre ?? 'Carlos Mendoza' }}
            </p>
            <p class="text-xs font-semibold text-emerald-700 flex items-center gap-1.5 mt-0.5">
                <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                <span>Habilitado • {{ $conductor->placa ?? 'W3X-451' }}</span>
            </p>
        </div>
    </div>

    {{-- Tarjeta 3: Resumen de Pago Diario Validado --}}
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200/80 p-5 space-y-3">
        <div>
            <p class="text-xs font-semibold text-slate-500">Pago diario validado:</p>
            <p class="font-headline font-black text-2xl text-[#0A1329] mt-0.5">S/ 1.00</p>
        </div>

        <div class="border-t border-slate-100 pt-3 space-y-1.5 text-xs">
            <div class="flex items-center justify-between text-slate-600">
                <span>Fecha</span>
                <span class="font-semibold text-[#0A1329]">{{ $fechaPago }}</span>
            </div>
            <div class="flex items-center justify-between text-slate-600">
                <span>Hora</span>
                <span class="font-semibold text-[#0A1329]">{{ $horaPago }}</span>
            </div>
            <div class="flex items-center justify-between text-slate-600">
                <span>Comisión por carrera</span>
                <span class="font-bold text-emerald-600">S/ 0.00 (100% para ti)</span>
            </div>
        </div>
    </div>

    {{-- Tarjeta 4: Volver al Radar de Viajes --}}
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200/80 p-4 text-center space-y-2.5">
        <a href="{{ route('despacho.pwa') }}"
           class="block w-full bg-[#0A1329] hover:bg-[#132347] text-white font-headline font-bold rounded-xl py-3.5 text-sm shadow-md transition">
            Volver al Radar de Viajes
        </a>
        <p class="text-xs font-semibold text-slate-600">¡Excelente ruta hoy!</p>
    </div>
</div>
@endsection
