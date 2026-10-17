@extends('layouts.torre-control')

@section('titulo', 'Simulador de Bot WhatsApp - Taxi Kaluz')
@section('encabezado', '👨‍💻 Zona Dev 4: Simulador de Recepción WhatsApp y Despacho Automático')

@section('content')
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <x-tarjeta-kaluz titulo="Simulador de Chat WhatsApp" subtitulo="Envía un mensaje como pasajero para disparar una subasta en tiempo real">
            <div class="bg-emerald-950/10 border border-emerald-200 rounded-xl p-4 space-y-3">
                <div class="bg-white p-3 rounded-lg shadow-sm text-sm text-slate-700">
                    <strong>Cliente (+51 999 888 777):</strong> Hola Kaluz, necesito un taxi de El Tambo a Parque Industrial
                </div>
                <div class="bg-emerald-100 p-3 rounded-lg shadow-sm text-sm text-emerald-950 ml-6">
                    <strong>🤖 Bot Taxi Kaluz:</strong> ¡Hola! Detectamos tu ruta <em>El Tambo → Parque Industrial</em>. Tarifa sugerida: <strong>S/ 7.00</strong>. Buscando conductor habilitado...
                </div>
            </div>
        </x-tarjeta-kaluz>

        <x-tarjeta-kaluz titulo="Resultado del Analizador NLP (AnalizadorWhatsAppService)" subtitulo="JSON generado hacia la cola de subasta atómica">
            <pre class="bg-slate-900 text-emerald-400 p-4 rounded-xl text-xs overflow-x-auto">{
    "intencion": "solicitar_viaje",
    "origen_detectado": "El Tambo",
    "destino_detectado": "Parque Industrial",
    "precio_sugerido": 7.00,
    "canal_origen": "whatsapp"
}</pre>
        </x-tarjeta-kaluz>
    </div>
@endsection
