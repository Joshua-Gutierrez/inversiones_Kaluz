@extends('layouts.pwa-mobile')

@section('titulo', 'Alertas y Candado - Taxi KALUZ')
@section('tema', 'claro')

@section('header_centro')
    <span class="inline-flex items-center gap-1.5 text-[11px] font-headline font-bold uppercase tracking-wide px-3 py-1 rounded-full bg-[#E31B23] text-white shadow-sm">
        <i class="ri-notification-badge-fill text-xs"></i>
        ALERTAS DEL CONDUCTOR
    </span>
@endsection

@section('header_derecha')
    @if(!empty($conductor))
        <span class="text-xs font-bold text-slate-200 truncate max-w-[110px]">{{ $conductor->placa }}</span>
    @endif
@endsection

@section('content')
    <div x-data="{ fotoDoc: '', docEnviado: false }" class="space-y-4">
        {{-- 1. Cabecera tipo notificación WhatsApp de Taxi KALUZ (Cuota Diaria S/ 1.00) --}}
        <div class="rounded-2xl overflow-hidden shadow-lg border border-slate-200/80 bg-[#ECE5DD]">
            <div class="bg-[#075E54] text-white px-4 py-3 flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div class="w-9 h-9 rounded-full bg-[#0A1329] border border-white/20 flex items-center justify-center font-headline font-black text-[10px] text-white leading-none text-center">
                        TAXI<br>KALUZ
                    </div>
                    <div>
                        <p class="font-bold text-sm flex items-center gap-1">
                            <span>Taxi KALUZ</span>
                            <i class="ri-verified-badge-fill text-emerald-300 text-sm"></i>
                        </p>
                        <p class="text-[11px] text-emerald-100">Bot Oficial • Alertas y Candado</p>
                    </div>
                </div>
                <div class="flex items-center gap-3 text-white/80">
                    <i class="ri-phone-fill text-base"></i>
                    <i class="ri-more-2-fill text-base"></i>
                </div>
            </div>

            <div class="p-4 space-y-3">
                <div class="bg-white rounded-2xl rounded-tl-xs p-4 shadow-xs max-w-[92%] space-y-1.5 border border-slate-200/60">
                    <p class="font-headline font-bold text-xs text-slate-900 flex items-center gap-1.5">
                        <i class="ri-alert-fill text-amber-500 text-base"></i>
                        <span>Alerta de Pago KALUZ (S/ 1.00)</span>
                    </p>
                    <p class="text-xs text-slate-700 leading-relaxed">
                        Hola <strong>{{ $conductor->nombre ?? 'Conductor' }}</strong>, tienes 1 cuota diaria de <strong>S/ 1.00</strong> pendiente para poder seguir recibiendo viajes hoy.
                        <br>¡No te quedes sin trabajar!
                    </p>
                    <span class="block text-right text-[10px] text-slate-400">{{ now()->format('H:i') }}</span>
                </div>

                <div class="space-y-2 pt-1">
                    <a href="{{ route('despacho.cuota') }}"
                       class="w-full bg-[#0A1329] hover:bg-[#132347] text-white font-headline font-bold rounded-xl py-3 px-4 text-xs shadow-md flex items-center justify-center gap-2 transition">
                        <i class="ri-qr-code-line text-base text-emerald-400"></i>
                        <span>Yapear S/ 1.00 Ahora</span>
                    </a>
                </div>
            </div>
        </div>

        {{-- 2. Tarjeta de Vencimiento de Documentos (Licencia, SOAT y Revisión Técnica) --}}
        <div class="bg-white rounded-2xl border border-slate-200/80 p-4 shadow-sm space-y-3">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <i class="ri-shield-check-fill text-lg text-[#0A1329]"></i>
                    <div>
                        <h2 class="font-headline font-bold text-sm text-[#0A1329]">Vigencia de Documentos</h2>
                        <p class="text-[11px] text-slate-500">Licencia MTC, SOAT y Revisión Técnica</p>
                    </div>
                </div>
                <span class="text-[10px] font-bold bg-amber-100 text-amber-800 px-2.5 py-1 rounded-full">
                    Revisión activa
                </span>
            </div>

            <div class="space-y-2 text-xs">
                <div class="flex items-center justify-between p-2.5 rounded-xl bg-slate-50 border border-slate-200/60">
                    <div class="flex items-center gap-2">
                        <i class="ri-file-shield-2-line text-base text-amber-600"></i>
                        <div>
                            <p class="font-bold text-[#0A1329]">SOAT / AFOCAT</p>
                            <p class="text-[11px] text-amber-700 font-medium">Próximo a vencer • {{ now()->addDays(4)->format('d/m/Y') }}</p>
                        </div>
                    </div>
                    <span class="text-[10px] font-bold bg-amber-100 text-amber-800 px-2 py-0.5 rounded-md">Por renovar</span>
                </div>

                <div class="flex items-center justify-between p-2.5 rounded-xl bg-slate-50 border border-slate-200/60">
                    <div class="flex items-center gap-2">
                        <i class="ri-Pass-valid-line text-base text-emerald-600"></i>
                        <div>
                            <p class="font-bold text-[#0A1329]">Licencia de Conducir</p>
                            <p class="text-[11px] text-slate-500">Vence: {{ now()->addMonths(8)->format('d/m/Y') }}</p>
                        </div>
                    </div>
                    <span class="text-[10px] font-bold bg-emerald-100 text-emerald-800 px-2 py-0.5 rounded-md">Vigente</span>
                </div>

                <div class="flex items-center justify-between p-2.5 rounded-xl bg-slate-50 border border-slate-200/60">
                    <div class="flex items-center gap-2">
                        <i class="ri-car-line text-base text-emerald-600"></i>
                        <div>
                            <p class="font-bold text-[#0A1329]">Revisión Técnica</p>
                            <p class="text-[11px] text-slate-500">Vence: {{ now()->addMonths(5)->format('d/m/Y') }}</p>
                        </div>
                    </div>
                    <span class="text-[10px] font-bold bg-emerald-100 text-emerald-800 px-2 py-0.5 rounded-md">Vigente</span>
                </div>
            </div>

            {{-- Subir imagen del documento renovado o confirmar renovación --}}
            @if(!empty($conductor))
                <form action="{{ route('finanzas.conductor.renovar', ['id' => $conductor->id]) }}" method="POST" enctype="multipart/form-data" class="space-y-2.5 pt-1">
                    @csrf
                    <input type="hidden" name="documento" value="SOAT / Revisión Técnica">

                    <label class="cursor-pointer flex items-center justify-center gap-2 rounded-xl border-2 border-dashed border-slate-300 bg-slate-50 hover:bg-slate-100 p-3 text-xs text-slate-600 transition">
                        <input type="file" name="foto_documento" accept="image/*" class="hidden"
                               @change="fotoDoc = $event.target.files[0]?.name || ''">
                        <i class="ri-camera-line text-base text-[#0A1329]"></i>
                        <span x-text="fotoDoc || 'Subir foto de SOAT / Revisión renovada'"></span>
                    </label>

                    <button type="submit"
                        class="w-full bg-emerald-600 hover:bg-emerald-500 text-white font-headline font-bold rounded-xl py-3 text-xs shadow-sm transition flex items-center justify-center gap-1.5">
                        <i class="ri-checkbox-circle-line text-base"></i>
                        <span>Confirmar Documento Renovado</span>
                    </button>
                </form>
            @endif
        </div>

        {{-- Cambiar de conductor --}}
        <div class="bg-white rounded-2xl border border-slate-200/80 p-4 flex items-center justify-between shadow-xs">
            <div class="text-xs text-slate-600">
                <p class="font-bold text-[#0A1329]">¿Deseas probar con otro socio?</p>
                <p>Cambia de perfil de conductor en modo demo.</p>
            </div>
            <form action="{{ route('despacho.salir') }}" method="POST">
                @csrf
                <button type="submit" class="bg-slate-100 hover:bg-slate-200 text-[#0A1329] font-bold text-xs px-3.5 py-2 rounded-xl transition flex items-center gap-1.5">
                    <i class="ri-user-shared-line"></i>
                    <span>Cambiar</span>
                </button>
            </form>
        </div>
    </div>
@endsection
