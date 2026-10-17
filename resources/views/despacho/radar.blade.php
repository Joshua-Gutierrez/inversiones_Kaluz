@extends('layouts.pwa-movil')

@section('titulo', 'Radar de Viajes - Taxi Kaluz PWA')

@section('content')
    <div class="space-y-4">
        <!-- Tarjeta de Identificación Conductor -->
        <div class="bg-[#08183D] border border-white/10 rounded-2xl p-4 shadow-md text-white">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-label font-bold text-[#E31B23] uppercase tracking-wider flex items-center gap-1.5">
                    <span class="w-2 h-2 rounded-full bg-[#E31B23]"></span>
                    Radar de Subastas Activo
                </span>
                <span class="text-[11px] font-label text-slate-300 bg-white/10 px-2 py-0.5 rounded-full">Comisión S/ 1.00 OK</span>
            </div>
            <h2 class="font-headline text-lg font-bold">Solicitudes en Huancayo</h2>
            <p class="font-body text-xs text-slate-300 mt-0.5">Acepta la tarifa sugerida o realiza una contraoferta instantánea.</p>
        </div>

        <!-- Tarjeta de Solicitud de Viaje en Subasta -->
        <div class="bg-slate-900 border border-[#08183D] rounded-2xl p-5 space-y-4 shadow-xl">
            <div class="flex justify-between items-start">
                <div>
                    <span class="text-xs font-label bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 px-2.5 py-0.5 rounded-full font-semibold">
                        NUEVO VIAJE • 0.8 km
                    </span>
                    <h3 class="font-headline font-bold text-base mt-2 text-white">Plaza Constitución → Real Plaza</h3>
                    <p class="font-body text-xs text-slate-400 mt-0.5">Pasajero: María G. (vía WhatsApp Bot)</p>
                </div>
                <div class="text-right">
                    <span class="text-[11px] font-label text-slate-400 block uppercase">Oferta Base</span>
                    <span class="font-headline text-2xl font-black text-emerald-400">S/ 8.00</span>
                </div>
            </div>

            <!-- Botones de Acción Kaluz -->
            <div class="grid grid-cols-3 gap-2 pt-2">
                <button type="button" class="bg-emerald-600 hover:bg-emerald-500 text-white font-label font-bold py-3 rounded-xl text-xs transition shadow-sm">
                    Aceptar S/ 8
                </button>
                <button type="button" class="bg-[#08183D] hover:bg-[#0D255B] text-white font-label font-semibold py-3 rounded-xl text-xs border border-white/10 transition">
                    Contraoferta S/ 9
                </button>
                <button type="button" class="bg-[#081328] hover:bg-[#040914] text-white font-label font-semibold py-3 rounded-xl text-xs border border-white/10 transition">
                    Contraoferta S/ 10
                </button>
            </div>
        </div>

        <!-- Barra Inferior Flotante de la PWA (Pill Nav) -->
        <div class="fixed bottom-4 left-0 right-0 max-w-md mx-auto px-4 z-40">
            <div class="bg-[#08183D]/95 backdrop-blur-md border border-white/10 rounded-2xl p-2 shadow-2xl flex items-center justify-around">
                <a href="{{ route('despacho.pwa') }}" class="p-2 text-white flex flex-col items-center gap-1">
                    <span class="w-8 h-8 rounded-xl bg-[#E31B23] flex items-center justify-center text-xs font-bold">📡</span>
                    <span class="text-[10px] font-label font-bold">Radar</span>
                </a>
                <a href="/general" class="p-2 text-slate-300 hover:text-white flex flex-col items-center gap-1">
                    <span class="w-8 h-8 rounded-xl bg-white/10 flex items-center justify-center text-xs font-bold">🎨</span>
                    <span class="text-[10px] font-label">Estilos</span>
                </a>
                <a href="/dashboard" class="p-2 text-slate-300 hover:text-white flex flex-col items-center gap-1">
                    <span class="w-8 h-8 rounded-xl bg-white/10 flex items-center justify-center text-xs font-bold">📊</span>
                    <span class="text-[10px] font-label">Torre</span>
                </a>
            </div>
        </div>
    </div>
@endsection
