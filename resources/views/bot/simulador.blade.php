@extends('layouts.torre-control')

@section('titulo', 'Simulador de Bot WhatsApp - Taxi Kaluz')
@section('encabezado', '👨‍💻 Zona Dev 4: Simulador de Recepción WhatsApp y Despacho Automático')

@section('content')
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <x-tarjeta-kaluz titulo="Simulador de Chat WhatsApp" subtitulo="Envía un mensaje como pasajero para disparar una subasta en tiempo real">
            <div class="bg-[#F8FAFC] border border-slate-200 rounded-2xl p-4 space-y-3">
                <div class="bg-white p-3.5 rounded-xl border border-slate-200/80 shadow-xs text-xs font-body text-slate-700">
                    <strong class="text-[#08183D]">Cliente (+51 999 888 777):</strong> Hola Kaluz, necesito un taxi de El Tambo a Parque Industrial
                </div>
                <div class="bg-emerald-50 border border-emerald-200 p-3.5 rounded-xl shadow-xs text-xs font-body text-emerald-950 ml-6">
                    <strong class="text-emerald-800">🤖 Bot Taxi Kaluz:</strong> ¡Hola! Detectamos tu ruta <em>El Tambo → Parque Industrial</em>. Tarifa sugerida: <strong class="text-[#E31B23]">S/ 7.00</strong>. Buscando conductor habilitado...
                </div>
            </div>

            <div class="mt-4 pt-3 flex gap-2">
                <x-general.button variant="accent" size="sm">
                    <span>⚡ Simular Nuevo Mensaje</span>
                </x-general.button>
                <a href="{{ route('despacho.pwa') }}" class="btn-primary text-xs">
                    <span>Ver en Radar PWA →</span>
                </a>
            </div>
        </x-tarjeta-kaluz>

        <x-tarjeta-kaluz titulo="Resultado del Analizador NLP (AnalizadorWhatsAppService)" subtitulo="Payload JSON enviado a la cola de subasta atómica">
            <div class="bg-[#081328] text-emerald-400 p-5 rounded-2xl text-xs font-mono overflow-x-auto shadow-inner border border-white/5">
<pre>{
    "intencion": "solicitar_viaje",
    "origen_detectado": "El Tambo",
    "destino_detectado": "Parque Industrial",
    "precio_sugerido": 7.00,
    "canal_origen": "whatsapp",
    "timestamp": "2026-10-17 16:05:00",
    "estado": "en_subasta_atomica"
}</pre>
            </div>
        </x-tarjeta-kaluz>
    </div>
@endsection
