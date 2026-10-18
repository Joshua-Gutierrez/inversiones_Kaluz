@extends('layouts.pwa-mobile')

@section('titulo', 'Mi Cuenta - Taxi KALUZ')
@section('tema', 'oscuro')

@section('header_centro')
    <span class="inline-flex items-center gap-1.5 text-[11px] font-headline font-bold uppercase tracking-wide px-3 py-1 rounded-full bg-white/10 text-slate-200 border border-white/15">
        <i class="ri-taxi-line text-emerald-400"></i>
        FLOTA HUANCAYO
    </span>
@endsection

@section('header_derecha')
    @if (!empty($conductor))
        <span class="text-xs font-headline font-bold text-emerald-400">{{ $conductor->placa }}</span>
    @endif
@endsection

@section('content')
    @php
        $rc = $resumenCuenta ?? [];
        $act = $rc['actividad'] ?? [];
        $pag = $rc['pagos'] ?? [];
        $docs = $rc['documentos'] ?? [];
        $veh = $rc['vehiculo'] ?? [];
    @endphp

    <div x-data="{ busqueda: '', mostrarSelector: {{ empty($conductor) ? 'true' : 'false' }} }" class="space-y-4">

        {{-- Encabezado principal + Botón Cambiar de Conductor --}}
        <div class="flex items-start justify-between gap-2">
            <div>
                <h1 class="font-headline font-extrabold text-2xl text-white">Mi Cuenta KALUZ</h1>
                <p class="text-xs text-slate-400 mt-0.5">Resumen de actividad, pagos, documentos y unidad asignada</p>
            </div>

            @if (!empty($conductor))
                <button type="button"
                    @click="mostrarSelector = !mostrarSelector"
                    class="shrink-0 inline-flex items-center gap-1.5 bg-[#162647] hover:bg-[#1E325C] text-slate-200 border border-white/15 rounded-xl px-3 py-2 text-[11px] font-semibold transition">
                    <i class="ri-arrow-left-right-line text-sm text-amber-400"></i>
                    <span x-text="mostrarSelector ? 'Ver mi ficha' : 'Cambiar chofer'"></span>
                </button>
            @endif
        </div>

        @if (session('demo_ok'))
            <div class="bg-emerald-500/15 text-emerald-300 border border-emerald-500/30 rounded-2xl px-4 py-3 text-xs font-medium flex items-center gap-2">
                <i class="ri-checkbox-circle-fill text-base text-emerald-400"></i>
                <span>{{ session('demo_ok') }}</span>
            </div>
        @endif

        @if (!empty($conductor) && !empty($rc))
            {{-- TARJETA 1: PERFIL DEL CONDUCTOR ACTIVO --}}
            <div class="bg-gradient-to-br from-[#111E3B] to-[#0B152A] rounded-2xl border border-white/15 p-4 shadow-lg space-y-3">
                <div class="flex items-center justify-between gap-3">
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="w-13 h-13 rounded-2xl bg-gradient-to-br from-[#E31B23] to-[#991B1B] border-2 border-white/25 flex items-center justify-center font-headline font-black text-xl text-white shrink-0 shadow-md">
                            {{ mb_strtoupper(mb_substr($rc['nombre_limpio'] ?? $conductor->nombre, 0, 1)) }}
                        </div>
                        <div class="min-w-0">
                            <div class="flex items-center gap-1.5 flex-wrap">
                                <span class="text-[10px] font-bold uppercase tracking-wider text-emerald-400 bg-emerald-500/10 border border-emerald-500/30 px-2 py-0.5 rounded-md">
                                    {{ $veh['padron'] ?? 'KALUZ-01' }}
                                </span>
                                <span class="text-[10px] font-semibold text-slate-300">
                                    DNI {{ $rc['dni'] ?? '45892301' }}
                                </span>
                            </div>
                            <h2 class="font-headline font-extrabold text-base text-white truncate mt-0.5">
                                {{ $rc['nombre_limpio'] ?? $conductor->nombre }}
                            </h2>
                            <p class="text-xs text-slate-400 flex items-center gap-2">
                                <span><i class="ri-whatsapp-line text-emerald-400"></i> {{ $rc['telefono'] }}</span>
                                <span>•</span>
                                <span class="font-bold text-slate-200">{{ $veh['placa'] }}</span>
                            </p>
                        </div>
                    </div>

                    <span class="shrink-0 inline-flex items-center gap-1 text-[10px] font-bold px-2.5 py-1 rounded-full
                        {{ $rc['habilitado']
                            ? 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/35'
                            : 'bg-red-500/20 text-red-300 border border-red-500/35' }}">
                        <i class="{{ $rc['habilitado'] ? 'ri-checkbox-blank-circle-fill text-[7px] text-emerald-400 animate-pulse' : 'ri-lock-fill text-[10px] text-red-400' }}"></i>
                        {{ $rc['habilitado'] ? 'HABILITADO' : 'BLOQUEADO' }}
                    </span>
                </div>

                {{-- Barra rápida de estado GPS y Acceso directo al Radar --}}
                <div class="pt-2 border-t border-white/10 flex items-center justify-between gap-2 text-xs">
                    <span class="flex items-center gap-1.5 text-emerald-400 font-medium">
                        <span class="w-2 h-2 rounded-full bg-emerald-400 animate-ping"></span>
                        <span>{{ $veh['gps'] ?? 'GPS PWA Activo' }}</span>
                    </span>

                    <a href="{{ route('despacho.pwa', ['conductor_id' => $conductor->id]) }}"
                       class="inline-flex items-center gap-1 font-bold text-white bg-[#E31B23] hover:bg-red-700 px-3 py-1.5 rounded-xl transition shadow-xs">
                        <i class="ri-radar-line"></i>
                        <span>Ir al Radar</span>
                    </a>
                </div>
            </div>

            {{-- TARJETA 2: RESUMEN DE ACTIVIDAD DEL CONDUCTOR --}}
            <div class="bg-[#111E3B] rounded-2xl border border-white/10 p-4 space-y-3 shadow-lg">
                <div class="flex items-center justify-between">
                    <h3 class="font-headline font-bold text-sm text-white flex items-center gap-2">
                        <i class="ri-bar-chart-grouped-line text-emerald-400 text-base"></i>
                        <span>Resumen de Actividad</span>
                    </h3>
                    <span class="text-[11px] text-slate-400 font-medium flex items-center gap-1">
                        <i class="ri-time-line text-emerald-400"></i>
                        <span>{{ $act['horas_conectado'] ?? '6.5 h hoy' }}</span>
                    </span>
                </div>

                <div class="grid grid-cols-2 gap-2.5">
                    <div class="bg-[#081124] rounded-xl p-3 border border-white/10">
                        <p class="text-[11px] text-slate-400 font-medium">Viajes de Hoy</p>
                        <div class="flex items-baseline justify-between mt-1">
                            <p class="font-headline font-black text-2xl text-white">{{ $act['viajes_hoy'] ?? 6 }}</p>
                            <span class="text-[10px] font-bold text-emerald-400 bg-emerald-500/10 px-2 py-0.5 rounded-full">Completados</span>
                        </div>
                    </div>

                    <div class="bg-[#081124] rounded-xl p-3 border border-white/10">
                        <p class="text-[11px] text-slate-400 font-medium">Ingresos de Hoy</p>
                        <div class="flex items-baseline justify-between mt-1">
                            <p class="font-headline font-black text-2xl text-emerald-400">
                                S/ {{ number_format((float) ($act['ingresos_hoy'] ?? 54), 2) }}
                            </p>
                            <span class="text-[10px] text-slate-400">100% tuyo</span>
                        </div>
                    </div>

                    <div class="bg-[#081124] rounded-xl p-3 border border-white/10">
                        <p class="text-[11px] text-slate-400 font-medium">Acumulado Mensual</p>
                        <p class="font-headline font-extrabold text-base text-white mt-1">
                            S/ {{ number_format((float) ($act['ingresos_mes'] ?? 1740), 2) }}
                        </p>
                        <p class="text-[10px] text-slate-400">{{ $act['viajes_mes'] ?? 124 }} carreras en el mes</p>
                    </div>

                    <div class="bg-[#081124] rounded-xl p-3 border border-white/10">
                        <p class="text-[11px] text-slate-400 font-medium">Reputación KALUZ</p>
                        <p class="font-headline font-extrabold text-base text-amber-400 mt-1 flex items-center gap-1">
                            <i class="ri-star-fill"></i>
                            <span>{{ $act['calificacion'] ?? '4.95' }} / 5.0</span>
                        </p>
                        <p class="text-[10px] text-slate-400">Socio Destacado Huancayo</p>
                    </div>
                </div>

                @if (!empty($act['ultimos_viajes']) && count($act['ultimos_viajes']) > 0)
                    <div class="pt-1 space-y-1.5">
                        <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Últimos servicios registrados</p>
                        @foreach ($act['ultimos_viajes'] as $uv)
                            <div class="bg-[#081124]/80 border border-white/5 rounded-xl px-3 py-2 flex items-center justify-between gap-2 text-xs">
                                <div class="min-w-0">
                                    <p class="text-white font-semibold truncate">
                                        {{ $uv->origen_texto }} <i class="ri-arrow-right-line text-slate-400 text-[10px]"></i> {{ $uv->destino_texto }}
                                    </p>
                                    <p class="text-[10px] text-slate-400">
                                        {{ ucfirst(str_replace('_', ' ', (string) $uv->estado)) }}
                                    </p>
                                </div>
                                <span class="font-headline font-bold text-emerald-400 shrink-0">
                                    S/ {{ number_format((float) ($uv->precio_final ?? $uv->oferta_cliente), 2) }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- TARJETA 3: PAGOS DIARIOS (S/ 1.00) Y CUOTAS MENSUALES --}}
            <div class="bg-[#111E3B] rounded-2xl border border-white/10 p-4 space-y-3.5 shadow-lg">
                <div class="flex items-center justify-between">
                    <h3 class="font-headline font-bold text-sm text-white flex items-center gap-2">
                        <i class="ri-wallet-3-line text-amber-400 text-base"></i>
                        <span>Pagos Diarios y Cuotas Mensuales</span>
                    </h3>
                    <a href="{{ route('despacho.cuota', ['conductor_id' => $conductor->id]) }}"
                       class="text-[11px] font-bold text-emerald-400 hover:underline flex items-center gap-0.5">
                        <span>Pagar con Yape</span>
                        <i class="ri-arrow-right-s-line"></i>
                    </a>
                </div>

                {{-- Pago Diario S/ 1.00 --}}
                <div class="bg-[#081124] rounded-xl p-3.5 border {{ ($pag['diario_al_dia'] ?? true) ? 'border-emerald-500/30' : 'border-red-500/40' }} space-y-2">
                    <div class="flex items-start justify-between gap-2">
                        <div class="flex items-center gap-2.5">
                            <div class="w-9 h-9 rounded-xl {{ ($pag['diario_al_dia'] ?? true) ? 'bg-emerald-500/15 text-emerald-400' : 'bg-red-500/15 text-red-400' }} flex items-center justify-center shrink-0">
                                <i class="ri-calendar-check-line text-lg"></i>
                            </div>
                            <div>
                                <p class="font-headline font-bold text-xs text-white">Pago Diario de Plataforma (S/ 1.00)</p>
                                <p class="text-[11px] text-slate-400">{{ $pag['ultimo_pago_diario'] ?? '' }}</p>
                            </div>
                        </div>

                        <span class="shrink-0 text-[10px] font-bold uppercase px-2.5 py-1 rounded-full
                            {{ ($pag['diario_al_dia'] ?? true)
                                ? 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/30'
                                : 'bg-red-500/20 text-red-300 border border-red-500/30' }}">
                            {{ ($pag['diario_al_dia'] ?? true) ? 'AL DÍA' : 'DEBE S/ ' . number_format((float) ($pag['deuda_diaria'] ?? 1), 2) }}
                        </span>
                    </div>

                    <div class="flex items-center justify-between text-[11px] text-slate-400 pt-1 border-t border-white/5">
                        <span>Próximo corte: {{ $pag['proximo_pago_diario'] ?? 'Mañana 06:00 AM' }}</span>
                        @if (!($pag['diario_al_dia'] ?? true))
                            <a href="{{ route('despacho.cuota', ['conductor_id' => $conductor->id]) }}"
                               class="text-red-400 font-bold hover:underline">
                                Regularizar ahora →
                            </a>
                        @else
                            <span class="text-emerald-400 font-semibold">Candado liberado</span>
                        @endif
                    </div>
                </div>

                {{-- Cuota Mensual de Flota / Padrón --}}
                <div class="bg-[#081124] rounded-xl p-3.5 border {{ ($pag['mensual_al_dia'] ?? true) ? 'border-white/10' : 'border-amber-500/40' }} space-y-2.5">
                    <div class="flex items-start justify-between gap-2">
                        <div class="flex items-center gap-2.5">
                            <div class="w-9 h-9 rounded-xl bg-blue-500/15 text-blue-400 flex items-center justify-center shrink-0">
                                <i class="ri-bank-card-line text-lg"></i>
                            </div>
                            <div>
                                <p class="font-headline font-bold text-xs text-white">{{ $pag['concepto_mensual'] ?? 'Cuota Mensual de Padrón KALUZ' }}</p>
                                <p class="text-[11px] text-slate-400">
                                    Monto mensual: <strong class="text-slate-200">S/ {{ number_format((float) ($pag['monto_mensual'] ?? 60), 2) }}</strong>
                                    • Vence: {{ $pag['vencimiento_mensual'] ?? '' }}
                                </p>
                            </div>
                        </div>

                        <span class="shrink-0 text-[10px] font-bold uppercase px-2.5 py-1 rounded-full
                            {{ ($pag['mensual_al_dia'] ?? true)
                                ? 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/30'
                                : 'bg-amber-500/20 text-amber-300 border border-amber-500/30' }}">
                            {{ ($pag['mensual_al_dia'] ?? true) ? 'AL DÍA' : 'POR REGULARIZAR' }}
                        </span>
                    </div>

                    <div class="space-y-1">
                        <div class="flex items-center justify-between text-[11px]">
                            <span class="text-slate-400">Récord de cuotas anuales:</span>
                            <span class="font-semibold text-slate-200">{{ $pag['cuotas_anio'] ?? '10 / 12 meses al día' }}</span>
                        </div>
                        <div class="w-full h-1.5 bg-white/10 rounded-full overflow-hidden">
                            <div class="h-full bg-emerald-500 rounded-full" style="width: {{ $pag['porcentaje_anio'] ?? 84 }}%"></div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- TARJETA 4: DOCUMENTACIÓN Y VIGENCIAS (LICENCIA, SOAT, REVISIÓN TÉCNICA) --}}
            <div class="bg-[#111E3B] rounded-2xl border border-white/10 p-4 space-y-3 shadow-lg">
                <div class="flex items-center justify-between">
                    <h3 class="font-headline font-bold text-sm text-white flex items-center gap-2">
                        <i class="ri-shield-check-line text-emerald-400 text-base"></i>
                        <span>Licencia, SOAT y Revisión Técnica</span>
                    </h3>
                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Control MTC / SUTRAN</span>
                </div>

                <div class="space-y-2.5">
                    @foreach (['licencia' => 'ri-id-card-line', 'soat' => 'ri-First-aid-kit-line', 'revision' => 'ri-car-line'] as $claveDoc => $iconoDoc)
                        @php
                            $d = $docs[$claveDoc] ?? [];
                            $est = $d['estado'] ?? 'vigente';
                            $badgeClass = $est === 'vigente'
                                ? 'bg-emerald-500/20 text-emerald-300 border-emerald-500/30'
                                : ($est === 'por_vencer'
                                    ? 'bg-amber-500/20 text-amber-300 border-amber-500/30'
                                    : 'bg-red-500/20 text-red-300 border-red-500/30');
                            $iconBoxClass = $est === 'vigente'
                                ? 'bg-emerald-500/15 text-emerald-400'
                                : ($est === 'por_vencer'
                                    ? 'bg-amber-500/15 text-amber-400'
                                    : 'bg-red-500/15 text-red-400');
                        @endphp
                        <div class="bg-[#081124] rounded-xl p-3 border border-white/10 flex items-center justify-between gap-3">
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="w-9 h-9 rounded-xl {{ $iconBoxClass }} flex items-center justify-center shrink-0">
                                    <i class="{{ $claveDoc === 'soat' ? 'ri-shield-cross-line' : $iconoDoc }} text-lg"></i>
                                </div>
                                <div class="min-w-0">
                                    <p class="font-headline font-bold text-xs text-white truncate">{{ $d['titulo'] ?? '' }}</p>
                                    <p class="text-[11px] text-slate-400 truncate">
                                        {{ $d['detalle'] ?? '' }} • <span class="text-slate-300 font-medium">{{ $d['numero'] ?? '' }}</span>
                                    </p>
                                    <p class="text-[10px] text-slate-400 mt-0.5">
                                        Vencimiento: <strong class="text-slate-200">{{ $d['vence'] ?? '' }}</strong>
                                    </p>
                                </div>
                            </div>

                            <span class="shrink-0 text-[10px] font-bold uppercase px-2.5 py-1 rounded-full border {{ $badgeClass }}">
                                {{ $d['etiqueta'] ?? 'VIGENTE' }}
                            </span>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- TARJETA 5: DATOS DEL VEHÍCULO --}}
            <div class="bg-[#111E3B] rounded-2xl border border-white/10 p-4 space-y-3 shadow-lg">
                <div class="flex items-center justify-between">
                    <h3 class="font-headline font-bold text-sm text-white flex items-center gap-2">
                        <i class="ri-taxi-wifi-line text-blue-400 text-base"></i>
                        <span>Datos del Vehículo Asignado</span>
                    </h3>
                    <span class="text-[10px] font-bold uppercase bg-white/10 text-slate-200 px-2.5 py-0.5 rounded-full">
                        {{ $veh['placa'] ?? $conductor->placa }}
                    </span>
                </div>

                <div class="grid grid-cols-2 gap-2.5 text-xs">
                    <div class="bg-[#081124] rounded-xl p-2.5 border border-white/10">
                        <span class="text-[10px] text-slate-400 block">Marca y Modelo</span>
                        <span class="font-headline font-bold text-white">{{ $veh['marca_modelo'] ?? $conductor->modelo_vehiculo }}</span>
                    </div>

                    <div class="bg-[#081124] rounded-xl p-2.5 border border-white/10">
                        <span class="text-[10px] text-slate-400 block">Año / Placa de Rodaje</span>
                        <span class="font-headline font-bold text-white">{{ $veh['anio'] ?? '2022' }} • {{ $veh['placa'] ?? $conductor->placa }}</span>
                    </div>

                    <div class="bg-[#081124] rounded-xl p-2.5 border border-white/10">
                        <span class="text-[10px] text-slate-400 block">Modalidad de Flota</span>
                        <span class="font-semibold text-emerald-400">{{ $veh['tipo_flota_label'] ?? 'Socio Afiliado' }}</span>
                    </div>

                    <div class="bg-[#081124] rounded-xl p-2.5 border border-white/10">
                        <span class="text-[10px] text-slate-400 block">Capacidad / Carrocería</span>
                        <span class="font-semibold text-slate-200">{{ $veh['carroceria'] ?? 'Sedán 4 pasajeros' }}</span>
                    </div>

                    <div class="bg-[#081124] rounded-xl p-2.5 border border-white/10">
                        <span class="text-[10px] text-slate-400 block">Combustible</span>
                        <span class="font-semibold text-slate-200">{{ $veh['combustible'] ?? 'Dual GLP / Gasolina' }}</span>
                    </div>

                    <div class="bg-[#081124] rounded-xl p-2.5 border border-white/10">
                        <span class="text-[10px] text-slate-400 block">Padrón Municipal / Color</span>
                        <span class="font-semibold text-slate-200">{{ $veh['padron'] ?? 'KALUZ-01' }} • Blanco</span>
                    </div>
                </div>
            </div>
        @endif

        {{-- SECCIÓN PARA CAMBIAR DE CONDUCTOR O INGRESAR CON OTRO WHATSAPP --}}
        <div x-show="mostrarSelector" x-transition class="space-y-4">
            {{-- Ingreso rápido por WhatsApp --}}
            <div class="bg-[#111E3B] rounded-2xl border border-white/10 p-4 space-y-3 shadow-lg">
                <div class="flex items-center gap-2">
                    <i class="ri-whatsapp-line text-lg text-emerald-400"></i>
                    <div>
                        <h2 class="font-headline font-bold text-sm text-white">Ingreso por WhatsApp</h2>
                        <p class="text-[11px] text-slate-400">Ingresa tu teléfono registrado en el padrón de Inversiones Kaluz</p>
                    </div>
                </div>

                <form action="{{ route('despacho.entrar') }}" method="POST" class="flex gap-2">
                    @csrf
                    <input type="hidden" name="volver_a" value="cuenta">
                    <input
                        type="tel"
                        name="telefono_whatsapp"
                        value="{{ old('telefono_whatsapp') }}"
                        placeholder="Ej: 964 111 222"
                        class="flex-1 min-w-0 bg-[#081124] border border-white/15 rounded-xl px-3.5 py-2.5 text-sm text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-[#E31B23]"
                        required
                    >
                    <button type="submit"
                        class="bg-[#D91B23] hover:bg-[#B9151C] text-white font-bold rounded-xl shadow-md px-4 py-2.5 text-xs whitespace-nowrap flex items-center gap-1.5">
                        <i class="ri-login-box-line text-sm"></i>
                        <span>Entrar</span>
                    </button>
                </form>

                @error('telefono_whatsapp')
                    <p class="text-xs text-red-400 flex items-center gap-1">
                        <i class="ri-error-warning-line"></i>
                        <span>{{ $message }}</span>
                    </p>
                @enderror
            </div>

            {{-- Lista de conductores de la flota --}}
            <div class="bg-[#111E3B] rounded-2xl border border-white/10 p-4 space-y-3 shadow-lg">
                <div class="flex items-center justify-between">
                    <h2 class="font-headline font-bold text-sm text-white flex items-center gap-1.5">
                        <i class="ri-steering-2-line text-slate-300"></i>
                        <span>Seleccionar Conductor de la Flota</span>
                    </h2>
                    <span class="text-[10px] font-bold uppercase tracking-wide bg-white/10 text-slate-300 px-2.5 py-0.5 rounded-full">
                        {{ count($conductores) }} unidades
                    </span>
                </div>

                <div class="relative">
                    <i class="ri-search-line absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
                    <input
                        type="search"
                        x-model="busqueda"
                        placeholder="Buscar por nombre o placa..."
                        class="w-full bg-[#081124] border border-white/15 rounded-xl pl-9 pr-3 py-2 text-xs text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-white/20"
                    >
                </div>

                <ul class="space-y-2 max-h-[340px] overflow-y-auto pr-0.5">
                    @forelse ($conductores as $c)
                        <li x-show="(@js(mb_strtolower($c->nombre . ' ' . $c->placa))).includes(busqueda.toLowerCase())">
                            <form action="{{ route('despacho.entrar') }}" method="POST">
                                @csrf
                                <input type="hidden" name="conductor_id" value="{{ $c->id }}">
                                <input type="hidden" name="volver_a" value="cuenta">
                                <button type="submit"
                                    class="w-full text-left flex items-center gap-3 {{ isset($conductor) && $conductor->id === $c->id ? 'bg-[#16274C] border-emerald-500/50' : 'bg-[#081124]/80 hover:bg-[#16274C] border-white/10' }} border rounded-xl px-3 py-2.5 transition">
                                    <span class="w-9 h-9 shrink-0 rounded-full {{ $c->habilitado ? 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/40' : 'bg-red-500/20 text-red-300 border border-red-500/40' }} flex items-center justify-center font-headline font-bold text-xs">
                                        {{ mb_substr($c->nombre, 0, 1) }}
                                    </span>
                                    <span class="flex-1 min-w-0">
                                        <span class="block font-headline font-bold text-xs text-white truncate">{{ $c->nombre }}</span>
                                        <span class="block text-[11px] text-slate-400 truncate">{{ $c->placa }} • {{ $c->modelo_vehiculo }}</span>
                                    </span>
                                    <span class="shrink-0 inline-flex items-center gap-1 text-[10px] font-bold px-2.5 py-1 rounded-full
                                        {{ $c->habilitado
                                            ? 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/30'
                                            : 'bg-red-500/20 text-red-300 border border-red-500/30' }}">
                                        <i class="{{ $c->habilitado ? 'ri-checkbox-blank-circle-fill text-[7px] text-emerald-400' : 'ri-lock-fill text-[10px] text-red-400' }}"></i>
                                        {{ $c->habilitado ? 'HABILITADO' : 'BLOQUEADO' }}
                                    </span>
                                </button>
                            </form>
                        </li>
                    @empty
                        <li class="text-center text-xs text-slate-400 py-6 space-y-3">
                            <p>No hay conductores registrados aún.</p>
                            <a href="{{ route('despacho.demo.sembrar-conductores') }}"
                               class="inline-flex items-center gap-1.5 bg-[#E31B23] text-white font-bold rounded-xl px-4 py-2.5 text-xs shadow-sm">
                                <i class="ri-database-2-line"></i>
                                <span>Sembrar 8 conductores de prueba</span>
                            </a>
                        </li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
@endsection
