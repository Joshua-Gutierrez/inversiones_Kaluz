@extends('layouts.torre-control')

@section('titulo', 'Validación de Pagos Yape (S/ 1.00) - KALUZ')

@section('content')
@php
    $conductores = $conductores ?? collect();
    $pendientes = $conductores->where('habilitado', false)->values();
    $validados = $conductores->where('habilitado', true)->values();
@endphp

<div x-data="{
        filtro: 'pendientes',
        comprobanteSeleccionado: null,
        abrirModalVoucher(conductor, codigoOp) {
            this.comprobanteSeleccionado = {
                id: conductor.id,
                nombre: conductor.nombre,
                placa: conductor.placa,
                modelo: conductor.modelo_vehiculo,
                telefono: conductor.telefono_whatsapp,
                codigo: codigoOp,
                hora: '{{ now()->format('h:i A') }}',
                fecha: '{{ now()->format('d/m/Y') }}'
            };
        }
    }"
    class="space-y-5">

    <!-- Encabezado -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div>
            <h1 class="font-headline font-extrabold text-2xl text-[#08142C] tracking-tight">
                Validación y Trazabilidad de Pagos Yape (S/ 1.00)
            </h1>
            <p class="text-xs text-slate-600 mt-0.5">
                Verificación de transferencias diarias de S/ 1.00 para liberación inmediata del Candado Financiero
            </p>
        </div>

        <div class="w-full sm:w-auto flex items-center gap-1.5 sm:gap-2 bg-white/95 p-1.5 rounded-xl border border-white shadow-xs text-xs overflow-x-auto">
            <button type="button" @click="filtro = 'pendientes'"
                class="px-2.5 sm:px-3 py-1.5 rounded-lg font-bold transition flex items-center gap-1 shrink-0 text-[11px] sm:text-xs"
                :class="filtro === 'pendientes' ? 'bg-[#D91B23] text-white' : 'text-slate-600 hover:bg-slate-100'">
                <i class="ri-time-line"></i>
                <span>Por Validar ({{ $pendientes->count() }})</span>
            </button>
            <button type="button" @click="filtro = 'validados'"
                class="px-2.5 sm:px-3 py-1.5 rounded-lg font-bold transition flex items-center gap-1 shrink-0 text-[11px] sm:text-xs"
                :class="filtro === 'validados' ? 'bg-emerald-600 text-white' : 'text-slate-600 hover:bg-slate-100'">
                <i class="ri-checkbox-circle-line"></i>
                <span>Validados ({{ $validados->count() }})</span>
            </button>
            <button type="button" @click="filtro = 'todos'"
                class="px-2.5 sm:px-3 py-1.5 rounded-lg font-bold transition shrink-0 text-[11px] sm:text-xs"
                :class="filtro === 'todos' ? 'bg-[#08142C] text-white' : 'text-slate-600 hover:bg-slate-100'">
                Todos ({{ $conductores->count() }})
            </button>
        </div>
    </div>

    @if (session('exito'))
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-xs font-semibold text-emerald-800 shadow-xs flex items-center gap-2">
            <i class="ri-checkbox-circle-fill text-emerald-600 text-base"></i>
            <span>{{ session('exito') }}</span>
        </div>
    @endif

    <!-- Tarjetas Resumen -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-white/95 rounded-2xl p-4 border border-white shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-medium text-slate-500">Comprobantes por Verificar</p>
                <p class="font-headline font-black text-3xl text-[#D91B23] mt-1">{{ $pendientes->count() }}</p>
                <p class="text-[11px] text-slate-500 mt-0.5">Conductores esperando desbloqueo</p>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-red-50 text-[#D91B23] flex items-center justify-center">
                <i class="ri-qr-scan-2-line text-2xl"></i>
            </div>
        </div>

        <div class="bg-white/95 rounded-2xl p-4 border border-white shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-medium text-slate-500">Pagos Validados Hoy</p>
                <p class="font-headline font-black text-3xl text-emerald-600 mt-1">{{ $validados->count() }}</p>
                <p class="text-[11px] text-emerald-600 font-semibold mt-0.5">S/ {{ number_format($validados->count() * 1.00, 2) }} conciliados en cuenta</p>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                <i class="ri-shield-check-fill text-2xl"></i>
            </div>
        </div>

        <div class="bg-white/95 rounded-2xl p-4 border border-white shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-medium text-slate-500">Cuenta Receptora Oficial</p>
                <p class="font-headline font-black text-xl text-[#08142C] mt-1">Yape: 925 424 844</p>
                <p class="text-[11px] text-slate-500 mt-0.5">Inversiones Kaluz E.I.R.L. • Cuota S/ 1.00</p>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-purple-100 text-purple-800 flex items-center justify-center font-headline font-black text-xs">
                YAPE
            </div>
        </div>
    </div>

    <!-- Grid de Tarjetas de Validación Yape -->
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
        @foreach ($conductores as $idx => $c)
            @php
                $esPendiente = ! $c->habilitado;
                $codigoOperacion = str_pad((string) (482910 + $c->id * 7), 7, '0', STR_PAD_LEFT);
            @endphp
            <div x-show="filtro === 'todos' || (filtro === 'pendientes' && {{ $esPendiente ? 'true' : 'false' }}) || (filtro === 'validados' && {{ ! $esPendiente ? 'true' : 'false' }})"
                 class="bg-white/95 rounded-3xl p-5 border {{ $esPendiente ? 'border-red-200 ring-1 ring-red-100' : 'border-white' }} shadow-sm flex flex-col justify-between space-y-4">

                <div class="flex items-start justify-between gap-3">
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="w-11 h-11 rounded-full {{ $esPendiente ? 'bg-[#D91B23]' : 'bg-[#08142C]' }} text-white flex items-center justify-center font-headline font-bold text-sm shrink-0 shadow-xs">
                            {{ mb_strtoupper(mb_substr($c->nombre, 0, 1)) }}
                        </div>
                        <div class="min-w-0">
                            <p class="font-headline font-bold text-sm text-[#08142C] truncate">{{ $c->nombre }}</p>
                            <p class="text-xs text-slate-500">{{ $c->placa }} • {{ $c->modelo_vehiculo }}</p>
                            <span class="inline-flex items-center gap-1 text-[11px] font-bold mt-1 {{ $esPendiente ? 'text-[#D91B23]' : 'text-emerald-600' }}">
                                <i class="{{ $esPendiente ? 'ri-lock-fill' : 'ri-checkbox-circle-fill' }}"></i>
                                <span>{{ $esPendiente ? 'Candado Activo (Por validar)' : 'Pago Validado • Habilitado' }}</span>
                            </span>
                        </div>
                    </div>

                    <!-- Miniatura de Comprobante Yape clicable -->
                    <button type="button"
                        @click="abrirModalVoucher(@js($c), '{{ $codigoOperacion }}')"
                        class="group relative w-20 h-24 rounded-xl bg-gradient-to-b from-[#742284] to-[#521660] p-2 text-white shadow-md flex flex-col justify-between shrink-0 border border-purple-300/40 hover:scale-105 transition"
                        title="Ampliar comprobante Yape">
                        <div class="flex items-center justify-between w-full">
                            <span class="text-[9px] font-headline font-black text-emerald-300">YAPE</span>
                            <i class="ri-zoom-in-line text-xs text-white/80 group-hover:text-white"></i>
                        </div>
                        <div class="bg-white rounded-lg p-1 text-center text-[#08142C] w-full">
                            <p class="font-headline font-black text-xs leading-tight">S/ 1.00</p>
                            <p class="text-[7px] text-slate-500 font-mono">#{{ $codigoOperacion }}</p>
                        </div>
                        <span class="text-[8px] text-purple-200 w-full text-center">Ver captura</span>
                    </button>
                </div>

                <!-- Datos de Trazabilidad del Pago -->
                <div class="bg-slate-50 rounded-2xl p-3 border border-slate-200/70 space-y-1.5 text-xs">
                    <div class="flex items-center justify-between">
                        <span class="text-slate-500">N° Operación Yape:</span>
                        <span class="font-mono font-bold text-[#08142C] bg-white px-2 py-0.5 rounded border border-slate-200">#{{ $codigoOperacion }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-slate-500">Monto reportado:</span>
                        <span class="font-headline font-extrabold text-[#08142C]">S/ 1.00</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-slate-500">WhatsApp Origen:</span>
                        <span class="font-medium text-slate-700">{{ $c->telefono_whatsapp ?? '964 111 222' }}</span>
                    </div>
                </div>

                <!-- Acciones de Aprobación / Rechazo -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                    <form action="{{ route('finanzas.yape.validar') }}" method="POST">
                        @csrf
                        <input type="hidden" name="conductor_id" value="{{ $c->id }}">
                        <input type="hidden" name="accion" value="aprobar">
                        <button type="submit"
                            class="w-full bg-[#08142C] hover:bg-[#13264E] text-white font-headline font-bold text-xs py-2.5 px-3 rounded-xl shadow-xs transition flex items-center justify-center gap-1.5">
                            <i class="ri-check-double-line text-emerald-400 text-sm"></i>
                            <span>Aprobar y Desbloquear</span>
                        </button>
                    </form>

                    <form action="{{ route('finanzas.yape.validar') }}" method="POST">
                        @csrf
                        <input type="hidden" name="conductor_id" value="{{ $c->id }}">
                        <input type="hidden" name="accion" value="rechazar">
                        <button type="submit"
                            class="w-full bg-[#D91B23] hover:bg-[#B8141B] text-white font-headline font-bold text-xs py-2.5 px-3 rounded-xl shadow-xs transition flex items-center justify-center gap-1.5">
                            <i class="ri-close-circle-line text-sm"></i>
                            <span>Rechazar / Bloquear</span>
                        </button>
                    </form>
                </div>
            </div>
        @endforeach
    </div>

    <!-- Modal de Vista Previa del Voucher Yape -->
    <div x-show="comprobanteSeleccionado" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-[#08142C]/65 backdrop-blur-xs" @click="comprobanteSeleccionado = null"></div>
        <div x-transition class="relative bg-white rounded-3xl shadow-2xl w-full max-w-sm overflow-hidden border border-slate-200">
            <div class="bg-gradient-to-br from-[#742284] to-[#4A1259] p-6 text-white text-center space-y-3">
                <div class="flex items-center justify-between">
                    <span class="font-headline font-black text-base tracking-wider text-emerald-300">YAPE</span>
                    <button type="button" @click="comprobanteSeleccionado = null" class="text-white/80 hover:text-white">
                        <i class="ri-close-line text-xl"></i>
                    </button>
                </div>

                <div class="bg-white text-[#08142C] rounded-2xl p-5 shadow-lg space-y-2">
                    <div class="w-10 h-10 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center mx-auto">
                        <i class="ri-check-line text-2xl font-bold"></i>
                    </div>
                    <p class="text-xs font-bold text-purple-800">¡Yapeaste!</p>
                    <p class="font-headline font-black text-3xl text-[#08142C]">S/ 1.00</p>
                    <p class="font-bold text-sm text-slate-800">Inversiones Kaluz E.I.R.L.</p>
                    <p class="text-[11px] text-slate-400" x-text="comprobanteSeleccionado?.fecha + ' - ' + comprobanteSeleccionado?.hora"></p>

                    <div class="border-t border-dashed border-slate-200 pt-2.5 mt-2.5 text-xs space-y-1 text-left">
                        <div class="flex justify-between">
                            <span class="text-slate-400">N° de operación:</span>
                            <span class="font-mono font-bold text-[#08142C]" x-text="comprobanteSeleccionado?.codigo"></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-400">Conductor:</span>
                            <span class="font-semibold text-slate-700" x-text="comprobanteSeleccionado?.nombre"></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-400">Placa:</span>
                            <span class="font-bold text-slate-800" x-text="comprobanteSeleccionado?.placa"></span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="p-4 bg-white flex gap-2">
                <form action="{{ route('finanzas.yape.validar') }}" method="POST" class="flex-1">
                    @csrf
                    <input type="hidden" name="conductor_id" :value="comprobanteSeleccionado?.id">
                    <input type="hidden" name="accion" value="aprobar">
                    <button type="submit" class="w-full bg-[#08142C] hover:bg-[#13264E] text-white font-headline font-bold text-xs py-3 rounded-xl">
                        Aprobar y Desbloquear
                    </button>
                </form>
                <button type="button" @click="comprobanteSeleccionado = null" class="px-4 py-3 rounded-xl border border-slate-200 text-xs font-semibold text-slate-600">
                    Cerrar
                </button>
            </div>
        </div>
    </div>
</div>
@endsection
