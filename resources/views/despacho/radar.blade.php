@extends('layouts.pwa-movil')

@section('titulo', 'Radar de Viajes - Taxi Kaluz PWA')

@section('content')
    <div class="space-y-4">
        <div class="bg-slate-900 border border-slate-800 rounded-xl p-4">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-semibold text-amber-400 uppercase">👨‍💻 Zona Dev 1 • Radar de Subastas</span>
                <span class="text-xs text-slate-400">Comisión S/ 1.00 al día</span>
            </div>
            <h2 class="text-lg font-bold text-white">Solicitudes Cercanas</h2>
            <p class="text-xs text-slate-400 mt-1">Acepta el precio sugerido o envía una contraoferta rápida.</p>
        </div>

        <!-- Tarjeta de Solicitud de Viaje en Subasta -->
        <div class="bg-slate-900 border border-emerald-500/40 rounded-xl p-4 space-y-3 shadow-lg">
            <div class="flex justify-between items-start">
                <div>
                    <span class="text-xs bg-emerald-500/20 text-emerald-300 px-2 py-0.5 rounded font-semibold">NUEVO VIAJE • 0.8 km</span>
                    <h3 class="font-bold text-base mt-1">Plaza Constitución → Real Plaza</h3>
                    <p class="text-xs text-slate-400">Pasajero: María G. (vía WhatsApp)</p>
                </div>
                <div class="text-right">
                    <span class="text-xs text-slate-400 block">Oferta Base</span>
                    <span class="text-2xl font-extrabold text-emerald-400">S/ 8.00</span>
                </div>
            </div>

            <div class="grid grid-cols-3 gap-2 pt-2">
                <button type="button" class="bg-emerald-600 hover:bg-emerald-500 text-white font-bold py-2.5 rounded-lg text-sm transition">
                    Aceptar S/ 8
                </button>
                <button type="button" class="bg-slate-800 hover:bg-slate-700 text-amber-300 font-bold py-2.5 rounded-lg text-sm border border-amber-500/30 transition">
                    + S/ 9.00
                </button>
                <button type="button" class="bg-slate-800 hover:bg-slate-700 text-amber-300 font-bold py-2.5 rounded-lg text-sm border border-amber-500/30 transition">
                    + S/ 10.00
                </button>
            </div>
        </div>
    </div>
@endsection
