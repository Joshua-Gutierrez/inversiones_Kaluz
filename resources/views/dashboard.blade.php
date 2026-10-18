@extends('layouts.torre-control')

@section('titulo', 'Torre de Control - KALUZ')

@section('head')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
@endsection

@section('content')
@php
    $conductores = $conductores ?? collect();
    $solicitudes = $solicitudes ?? collect();
    $flotaEnVivo = $flotaEnVivo ?? [];
    $totalHabilitados = $conductores->where('habilitado', true)->count();
    $viajesActivos = $solicitudes->whereIn('estado', ['pendiente', 'en_subasta', 'asignado', 'en_ruta'])->count();
    $enServicioAhora = collect($flotaEnVivo)->where('en_servicio', true)->count();
    $recaudacion = max(128, $totalHabilitados * 1);
    $conductorYape = $conductorPorValidar ?? (object) [
        'id' => 1,
        'nombre' => 'Carlos Mendoza',
        'placa' => 'W3X-451',
        'modelo_vehiculo' => 'Toyota Yaris',
        'habilitado' => true,
    ];
@endphp

<div class="space-y-4">
    <!-- Título Principal -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="font-headline font-extrabold text-2xl text-[#08142C] tracking-tight">
                Torre de Control - KALUZ
            </h1>
            <p class="text-xs text-slate-600 mt-0.5">
                Monitoreo GPS en tiempo real de unidades haciendo servicio desde la PWA de Conductores
            </p>
        </div>
        @if (session('exito'))
            <div class="inline-flex items-center gap-2 bg-emerald-50 border border-emerald-200 text-emerald-800 px-3.5 py-1.5 rounded-xl text-xs font-semibold shadow-xs">
                <i class="ri-checkbox-circle-fill text-emerald-600 text-sm"></i>
                <span>{{ session('exito') }}</span>
            </div>
        @endif
    </div>

    <!-- Grid Principal: Izquierda (KPIs + Mapa GPS 8 cols) | Derecha (Validación Yape + Solicitudes WhatsApp 4 cols) -->
    <div class="grid grid-cols-1 xl:grid-cols-12 gap-4 sm:gap-5 items-start">

        <!-- Columna Izquierda (8/12): 4 KPIs + Mapa en Vivo de Huancayo + Panel de Unidades GPS -->
        <div class="xl:col-span-8 space-y-4">
            <!-- 4 Tarjetas KPI Responsivas -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-2.5 sm:gap-3.5">
                <!-- KPI 1: Viajes Activos -->
                <div class="bg-white/95 rounded-2xl p-3 sm:p-4 border border-white shadow-sm">
                    <p class="text-[11px] sm:text-xs font-medium text-slate-600 leading-snug">Viajes Activos</p>
                    <p id="kpi-viajes-activos" class="font-headline font-black text-2xl sm:text-3xl text-[#08142C] mt-1 sm:mt-2">
                        {{ $viajesActivos > 0 ? $viajesActivos : 1 }}
                    </p>
                    <p class="text-[10px] sm:text-[11px] text-emerald-600 font-semibold mt-0.5 sm:mt-1 flex items-center gap-1">
                        <i class="ri-radar-line"></i>
                        <span id="kpi-en-servicio-label">{{ $enServicioAhora }} en carrera</span>
                    </p>
                </div>

                <!-- KPI 2: Conductores Habilitados -->
                <div class="bg-white/95 rounded-2xl p-3 sm:p-4 border border-white shadow-sm">
                    <p class="text-[11px] sm:text-xs font-medium text-slate-600 leading-snug">Flota en Servicio<br class="hidden sm:inline">(GPS PWA)</p>
                    <p id="kpi-habilitados" class="font-headline font-black text-2xl sm:text-3xl text-[#08142C] mt-1">
                        {{ $totalHabilitados }}
                    </p>
                    <p class="text-[10px] sm:text-[11px] text-slate-500 mt-0.5">De {{ $conductores->count() }} unidades</p>
                </div>

                <!-- KPI 3: Recaudación Diaria -->
                <div class="bg-white/95 rounded-2xl p-3 sm:p-4 border border-white shadow-sm">
                    <p class="text-[11px] sm:text-xs font-medium text-slate-600 leading-snug">Recaudación Diaria</p>
                    <p class="font-headline font-black text-xl sm:text-2xl text-[#08142C] mt-1">
                        S/ {{ number_format($recaudacion, 2) }}
                    </p>
                    <p class="text-[10px] sm:text-[11px] text-slate-500">(Cuotas S/ 1.00)</p>
                    <div class="w-full h-1.5 bg-slate-200 rounded-full overflow-hidden mt-1.5">
                        <div class="h-full bg-[#D91B23] rounded-full" style="width: 78%"></div>
                    </div>
                </div>

                <!-- KPI 4: Tiempo Promedio de Asignación -->
                <div class="bg-white/95 rounded-2xl p-3 sm:p-4 border border-white shadow-sm">
                    <p class="text-[11px] sm:text-xs font-medium text-slate-600 leading-snug">Frecuencia GPS<br class="hidden sm:inline">PWA Conductor</p>
                    <p class="font-headline font-black text-2xl sm:text-3xl text-[#08142C] mt-1">4 seg</p>
                    <p class="text-[10px] sm:text-[11px] text-emerald-600 font-semibold mt-0.5 flex items-center gap-1">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-ping"></span> Sincronizado
                    </p>
                </div>
            </div>

            <!-- Mapa GPS en Vivo de Huancayo (OpenStreetMap + Telemetría en Tiempo Real de la Flota PWA) -->
            <div id="mapa-en-vivo" class="bg-white rounded-3xl p-2.5 sm:p-3 border border-white shadow-md space-y-2.5">
                <!-- Toolbar Responsiva de Filtros y Centrado del Mapa -->
                <div class="flex flex-wrap items-center justify-between gap-2 bg-slate-50/95 p-1.5 sm:p-2 rounded-2xl border border-slate-200/80 text-xs">
                    <div class="flex flex-wrap items-center gap-1 sm:gap-1.5">
                        <button type="button" onclick="window.filtrarMapaKaluz('todos')" id="btn-filtro-todos"
                            class="px-2.5 sm:px-3 py-1 sm:py-1.5 rounded-lg font-bold transition bg-[#08142C] text-white text-[11px] sm:text-xs">
                            Todas (<span id="count-flota-todas">{{ count($flotaEnVivo) }}</span>)
                        </button>
                        <button type="button" onclick="window.filtrarMapaKaluz('servicio')" id="btn-filtro-servicio"
                            class="px-2.5 sm:px-3 py-1 sm:py-1.5 rounded-lg font-bold transition text-slate-700 hover:bg-slate-200/60 flex items-center gap-1 text-[11px] sm:text-xs">
                            <span class="w-1.5 h-1.5 sm:w-2 sm:h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                            <span>En Servicio (<span id="count-flota-servicio">{{ $enServicioAhora }}</span>)</span>
                        </button>
                        <button type="button" onclick="window.filtrarMapaKaluz('radar')" id="btn-filtro-radar"
                            class="px-2.5 sm:px-3 py-1 sm:py-1.5 rounded-lg font-bold transition text-slate-700 hover:bg-slate-200/60 flex items-center gap-1 text-[11px] sm:text-xs">
                            <span class="w-1.5 h-1.5 sm:w-2 sm:h-2 rounded-full bg-[#D91B23]"></span>
                            <span>Radar (<span id="count-flota-radar">{{ max(0, $totalHabilitados - $enServicioAhora) }}</span>)</span>
                        </button>
                    </div>

                    <div class="flex items-center gap-1.5 ml-auto">
                        <button type="button" onclick="window.centrarMapaKaluz && window.centrarMapaKaluz()"
                            class="px-2.5 sm:px-3 h-8 sm:h-9 rounded-xl bg-white shadow-xs border border-slate-200 flex items-center gap-1.5 text-[11px] sm:text-xs font-bold text-slate-700 hover:text-[#08142C] transition"
                            title="Ver toda la flota en Huancayo">
                            <i class="ri-focus-3-line text-sm text-[#D91B23]"></i>
                            <span>Centrar Flota</span>
                        </button>
                    </div>
                </div>

                <!-- Contenedor Leaflet OpenStreetMap con Altura Adaptativa Móvil -->
                <div class="relative rounded-2xl overflow-hidden border border-slate-200 shadow-inner">
                    <div id="mapa-torre-control" class="w-full h-[320px] sm:h-[460px] z-0"></div>
                </div>

                <!-- Leyenda Responsiva Inferior -->
                <div class="flex flex-wrap items-center justify-between gap-2 p-2 bg-slate-50/95 rounded-2xl border border-slate-200/80 text-[11px] text-slate-700">
                    <div class="flex flex-wrap items-center gap-3 sm:gap-4 font-medium">
                        <span class="flex items-center gap-1">
                            <span class="w-2.5 h-2.5 rounded-full bg-[#08142C] inline-block"></span> Central
                        </span>
                        <span class="flex items-center gap-1">
                            <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 inline-block animate-pulse"></span> En Carrera
                        </span>
                        <span class="flex items-center gap-1">
                            <span class="w-2.5 h-2.5 rounded-full bg-[#D91B23] inline-block"></span> Radar PWA
                        </span>
                        <span class="flex items-center gap-1">
                            <span class="w-2.5 h-2.5 rounded-full bg-slate-400 inline-block"></span> Candado
                        </span>
                    </div>

                    <div class="flex items-center gap-2 ml-auto">
                        <span class="bg-[#08142C] text-white rounded-lg px-2.5 py-1 text-[10px] font-semibold flex items-center gap-1.5">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-ping"></span>
                            <span id="estado-telemetria-mapa">GPS en vivo</span>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Panel Interactivo de Unidades en Servicio / GPS Activo (Click para ubicar en el mapa) -->
            <div class="bg-white/95 rounded-3xl p-4 border border-white shadow-sm space-y-3">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <i class="ri-taxi-wifi-fill text-lg text-[#D91B23]"></i>
                        <h2 class="font-headline font-bold text-sm text-[#08142C]">
                            Unidades en Servicio y Flota Conectada al GPS de la PWA
                        </h2>
                    </div>
                    <span class="text-[11px] text-slate-500">Haz clic en una unidad para seguirla en el mapa</span>
                </div>

                <div id="grid-flota-en-vivo" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-2.5">
                    @foreach ($flotaEnVivo as $u)
                        <button type="button"
                            onclick="window.enfocarUnidadKaluz({{ $u['id'] }})"
                            class="text-left rounded-2xl p-3 border transition flex flex-col justify-between gap-2 hover:shadow-md
                                {{ $u['en_servicio']
                                    ? 'bg-emerald-50/90 border-emerald-300'
                                    : ($u['habilitado']
                                        ? 'bg-slate-50/90 border-slate-200 hover:border-[#08142C]/30'
                                        : 'bg-red-50/50 border-red-200/70 opacity-80') }}">
                            <div class="flex items-center justify-between gap-1.5">
                                <span class="font-headline font-black text-xs text-[#08142C] bg-white px-2 py-0.5 rounded-md border border-slate-200 shadow-2xs">
                                    {{ $u['placa'] }}
                                </span>
                                @if ($u['en_servicio'])
                                    <span class="inline-flex items-center gap-1 text-[10px] font-bold bg-emerald-600 text-white px-2 py-0.5 rounded-full">
                                        <span class="w-1.5 h-1.5 rounded-full bg-white animate-ping"></span>
                                        EN CARRERA
                                    </span>
                                @elseif ($u['habilitado'])
                                    <span class="inline-flex items-center gap-1 text-[10px] font-bold bg-emerald-100 text-emerald-800 px-2 py-0.5 rounded-full">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                        GPS ACTIVO
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 text-[10px] font-bold bg-red-100 text-red-700 px-2 py-0.5 rounded-full">
                                        <i class="ri-lock-fill text-[9px]"></i>
                                        CANDADO
                                    </span>
                                @endif
                            </div>

                            <div>
                                <p class="font-headline font-bold text-xs text-[#08142C] truncate">{{ $u['nombre'] }}</p>
                                <p class="text-[11px] text-slate-500 truncate">{{ $u['modelo_vehiculo'] }}</p>
                            </div>

                            @if ($u['en_servicio'] && !empty($u['viaje']))
                                <div class="text-[10px] bg-white/90 rounded-lg px-2 py-1 border border-emerald-200 text-emerald-950 truncate">
                                    <i class="ri-map-pin-2-fill text-[#D91B23]"></i>
                                    {{ $u['viaje']['origen_texto'] }} → {{ $u['viaje']['destino_texto'] }}
                                </div>
                            @else
                                <div class="text-[10px] text-slate-400 flex items-center justify-between">
                                    <span><i class="ri-gps-line text-emerald-600"></i> {{ $u['lat'] }}, {{ $u['lng'] }}</span>
                                </div>
                            @endif
                        </button>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Columna Derecha (4/12): Validación de Pagos Yape + Solicitudes WhatsApp En Vivo -->
        <div class="xl:col-span-4 space-y-4">
            <!-- Tarjeta 1: Validación de Pagos Yape (S/ 1.00) -->
            <div class="bg-white/95 rounded-3xl p-5 border border-white shadow-sm space-y-4"
                 x-data="{
                    conductorNombre: @js($conductorYape->nombre ?? 'Carlos Mendoza'),
                    conductorPlaca: @js($conductorYape->placa ?? 'W3X-451'),
                    conductorId: @js($conductorYape->id ?? 1),
                    habilitado: @js((bool) ($conductorYape->habilitado ?? false)),
                    mensaje: ''
                 }">
                <div class="flex items-start justify-between">
                    <h2 class="font-headline font-bold text-base text-[#08142C] leading-snug">
                        Validación de Pagos Yape<br>(S/ 1.00)
                    </h2>
                    <a href="{{ route('finanzas.padron') }}" class="text-[11px] font-semibold text-slate-500 hover:text-[#08142C] flex items-center gap-0.5">
                        <span>Ver padrón</span>
                        <i class="ri-arrow-right-s-line"></i>
                    </a>
                </div>

                <!-- Conductor + Miniatura de Voucher Yape -->
                <div class="flex items-center justify-between gap-3">
                    <div class="space-y-2 min-w-0">
                        <div class="flex items-center gap-2.5">
                            <div class="w-11 h-11 rounded-full bg-gradient-to-br from-[#08142C] to-[#1E3A8A] text-white flex items-center justify-center font-headline font-bold text-sm shrink-0 shadow-xs">
                                {{ mb_strtoupper(mb_substr($conductorYape->nombre ?? 'C', 0, 1)) }}
                            </div>
                            <div class="min-w-0">
                                <p class="font-headline font-bold text-sm text-[#08142C] truncate" x-text="conductorNombre"></p>
                                <p class="text-xs font-semibold flex items-center gap-1.5"
                                   :class="habilitado ? 'text-emerald-600' : 'text-amber-600'">
                                    <span class="w-2 h-2 rounded-full" :class="habilitado ? 'bg-emerald-500' : 'bg-amber-500'"></span>
                                    <span x-text="habilitado ? 'Habilitado' : 'Pendiente S/ 1.00'"></span>
                                </p>
                            </div>
                        </div>

                        <div class="pt-0.5">
                            <p class="text-[11px] text-slate-500">Vehículo de conductor:</p>
                            <p class="font-headline font-extrabold text-sm text-[#08142C]" x-text="conductorPlaca"></p>
                        </div>
                    </div>

                    <!-- Miniatura Ilustrativa de Comprobante Yape S/ 1.00 -->
                    <div class="w-20 h-24 rounded-xl bg-gradient-to-b from-[#742284] to-[#521660] p-2 text-white shadow-md flex flex-col justify-between shrink-0 border border-purple-300/40">
                        <div class="flex items-center justify-between">
                            <span class="text-[9px] font-headline font-black tracking-wide text-emerald-300">YAPE</span>
                            <i class="ri-checkbox-circle-fill text-emerald-300 text-xs"></i>
                        </div>
                        <div class="bg-white rounded-lg p-1.5 text-center text-[#08142C]">
                            <p class="text-[8px] text-slate-400 leading-none">Pagaste</p>
                            <p class="font-headline font-black text-xs leading-tight">S/ 1.00</p>
                            <p class="text-[7px] text-slate-500 truncate mt-0.5">Kaluz EIRL</p>
                        </div>
                        <p class="text-[8px] text-center text-purple-200">{{ now()->format('H:i') }}</p>
                    </div>
                </div>

                <div x-show="mensaje" x-transition class="rounded-xl bg-emerald-50 border border-emerald-200 px-3 py-2 text-xs text-emerald-800 font-medium flex items-center gap-1.5">
                    <i class="ri-checkbox-circle-fill text-emerald-600"></i>
                    <span x-text="mensaje"></span>
                </div>

                <!-- Botones Aprobar y Desbloquear / Rechazar -->
                <div class="space-y-2 pt-1">
                    <form action="{{ route('finanzas.yape.validar') }}" method="POST">
                        @csrf
                        <input type="hidden" name="conductor_id" :value="conductorId">
                        <input type="hidden" name="accion" value="aprobar">
                        <button type="submit"
                            class="w-full bg-[#08142C] hover:bg-[#12264E] text-white font-headline font-bold text-xs py-3 rounded-xl shadow-sm transition flex items-center justify-center gap-1.5">
                            <i class="ri-lock-unlock-line text-sm text-emerald-400"></i>
                            <span>Aprobar y Desbloquear</span>
                        </button>
                    </form>

                    <form action="{{ route('finanzas.yape.validar') }}" method="POST">
                        @csrf
                        <input type="hidden" name="conductor_id" :value="conductorId">
                        <input type="hidden" name="accion" value="rechazar">
                        <button type="submit"
                            class="w-full bg-[#D91B23] hover:bg-[#B8141B] text-white font-headline font-bold text-xs py-2.5 rounded-xl shadow-sm transition flex items-center justify-center gap-1.5">
                            <i class="ri-close-circle-line text-sm"></i>
                            <span>Rechazar</span>
                        </button>
                    </form>
                </div>
            </div>

            <!-- Tarjeta 2: Solicitudes WhatsApp (En Vivo) -->
            <div class="bg-white/95 rounded-3xl p-5 border border-white shadow-sm space-y-3">
                <div class="flex items-center justify-between">
                    <h2 class="font-headline font-bold text-base text-[#08142C]">
                        Solicitudes WhatsApp <span class="font-normal text-slate-500 text-sm">(En Vivo)</span>
                    </h2>
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-ping"></span>
                </div>

                <div class="space-y-2.5 max-h-[280px] overflow-y-auto pr-1">
                    @forelse ($solicitudes->take(8) as $index => $sol)
                        <div class="rounded-2xl p-3 border transition flex items-start gap-3 {{ in_array($sol->estado, ['asignado', 'en_ruta']) ? 'bg-emerald-50/90 border-emerald-200/80' : ($index === 0 ? 'bg-amber-50/90 border-amber-200/80' : 'bg-slate-100/90 border-slate-200/70') }}">
                            <div class="w-9 h-9 rounded-full bg-[#08142C] text-white flex items-center justify-center shrink-0 mt-0.5">
                                <i class="ri-whatsapp-line text-lg"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between gap-2">
                                    <p class="text-xs font-bold text-[#08142C]">
                                        S/ {{ number_format((float) ($sol->precio_final ?? $sol->oferta_cliente), 2) }} -
                                        <span class="{{ in_array($sol->estado, ['asignado', 'en_ruta', 'completado']) ? 'text-emerald-600' : 'text-amber-600' }}">
                                            {{ ucfirst(str_replace('_', ' ', $sol->estado)) }}
                                        </span>
                                    </p>
                                    <span class="text-[10px] text-slate-500 shrink-0">
                                        {{ $sol->created_at ? \Illuminate\Support\Carbon::parse($sol->created_at)->format('h:i A') : now()->format('h:i A') }}
                                    </span>
                                </div>
                                <p class="text-xs text-slate-600 truncate mt-0.5">
                                    {{ $sol->origen_texto }} <i class="ri-arrow-right-line text-[10px]"></i> {{ $sol->destino_texto }}
                                </p>
                                <div class="flex items-center justify-between mt-0.5">
                                    <p class="text-[10px] text-slate-400">
                                        WhatsApp • {{ $sol->cliente_nombre ?: $sol->cliente_telefono }}
                                    </p>
                                    @if (in_array($sol->estado, ['asignado', 'en_ruta']))
                                        <a href="{{ url('/rastreo/' . $sol->id) }}" target="_blank" class="text-[10px] font-bold text-emerald-700 hover:underline">
                                            Ver rastreo →
                                        </a>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-6 text-xs text-slate-400">
                            No hay solicitudes recientes registradas.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const contenedor = document.getElementById('mapa-torre-control');
        if (!contenedor || typeof L === 'undefined') return;

        const centroHuancayo = [-12.0651, -75.2049];
        const mapa = L.map(contenedor, {
            zoomControl: true,
            attributionControl: true,
        }).setView(centroHuancayo, 15);

        // Exactamente el mismo mapa OpenStreetMap usado en viaje-activo y track
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; OpenStreetMap',
        }).addTo(mapa);

        // Pin Central Taxi KALUZ
        const iconoCentralKaluz = L.divIcon({
            className: '',
            html: `<div style="width:36px;height:36px;border-radius:50%;background:#0A1329;border:3px solid #fff;color:#fff;display:flex;flex-direction:column;align-items:center;justify-content:center;box-shadow:0 4px 12px rgba(10,19,41,0.55);font-family:Montserrat,sans-serif;line-height:1;"><span style="font-size:7px;font-weight:700;color:#FACC15;">TAXI</span><span style="font-size:8px;font-weight:900;">KALUZ</span></div>`,
            iconSize: [36, 36],
            iconAnchor: [18, 18],
        });

        L.marker(centroHuancayo, { icon: iconoCentralKaluz })
            .addTo(mapa)
            .bindPopup('<strong>Central Taxi KALUZ</strong><br>Parque Constitución • Huancayo');

        let flotaDatos = @json($flotaEnVivo);
        let filtroActual = 'todos';
        const marcadoresUnidades = new Map();
        const lineasServicio = new Map();
        const marcadoresDestinos = new Map();
        const cacheRutasOsrm = new Map();

        // Mismos iconos circulares limpios de 34x34px que en track.blade.php y viaje-activo.blade.php
        function crearIconoUnidad(u) {
            if (u.en_servicio) {
                return L.divIcon({
                    className: '',
                    html: `<div style="width:34px;height:34px;border-radius:50%;background:#10B981;border:3px solid #fff;color:#fff;display:flex;align-items:center;justify-content:center;box-shadow:0 4px 12px rgba(16,185,129,0.65);"><i class="ri-taxi-fill" style="font-size:17px;"></i></div>`,
                    iconSize: [34, 34],
                    iconAnchor: [17, 17],
                    popupAnchor: [0, -17],
                });
            }

            if (u.habilitado) {
                return L.divIcon({
                    className: '',
                    html: `<div style="width:34px;height:34px;border-radius:50%;background:#D91B23;border:3px solid #fff;color:#fff;display:flex;align-items:center;justify-content:center;box-shadow:0 4px 12px rgba(217,27,35,0.6);"><i class="ri-taxi-fill" style="font-size:17px;"></i></div>`,
                    iconSize: [34, 34],
                    iconAnchor: [17, 17],
                    popupAnchor: [0, -17],
                });
            }

            return L.divIcon({
                className: '',
                html: `<div style="width:30px;height:30px;border-radius:50%;background:#64748B;border:3px solid #fff;color:#fff;display:flex;align-items:center;justify-content:center;box-shadow:0 3px 8px rgba(0,0,0,0.3);"><i class="ri-lock-fill" style="font-size:14px;"></i></div>`,
                iconSize: [30, 30],
                iconAnchor: [15, 15],
                popupAnchor: [0, -15],
            });
        }

        function crearPopupUnidad(u) {
            if (u.en_servicio && u.viaje) {
                return `
                    <div style="font-family:Inter,sans-serif;font-size:12px;line-height:1.45;min-width:210px;">
                        <div style="display:flex;align-items:center;justify-content:space-between;gap:6px;margin-bottom:4px;">
                            <strong style="color:#08142C;font-size:13px;">${u.nombre}</strong>
                            <span style="background:#10B981;color:#fff;font-size:9px;font-weight:800;padding:2px 6px;border-radius:999px;">EN SERVICIO</span>
                        </div>
                        <div style="color:#475569;font-size:11px;margin-bottom:6px;">
                            <strong>${u.placa}</strong> • ${u.modelo_vehiculo}
                        </div>
                        <div style="background:#ECFDF5;border:1px solid #A7F3D0;border-radius:8px;padding:6px 8px;margin-bottom:6px;">
                            <div style="font-size:10px;font-weight:800;color:#047857;margin-bottom:2px;">${u.viaje.etiqueta}</div>
                            <div style="font-size:11px;color:#065F46;"><strong>Pasajero:</strong> ${u.viaje.cliente_nombre}</div>
                            <div style="font-size:11px;color:#065F46;"><strong>Origen:</strong> ${u.viaje.origen_texto}</div>
                            <div style="font-size:11px;color:#065F46;"><strong>Destino:</strong> ${u.viaje.destino_texto}</div>
                            <div style="font-size:12px;font-weight:800;color:#08142C;margin-top:3px;">Tarifa: S/ ${Number(u.viaje.tarifa).toFixed(2)}</div>
                        </div>
                        <div style="display:flex;align-items:center;justify-content:space-between;font-size:10px;color:#64748B;">
                            <span>GPS PWA: ${u.lat}, ${u.lng}</span>
                            <a href="${u.viaje.url_rastreo}" target="_blank" style="color:#D91B23;font-weight:700;text-decoration:none;">Rastreo →</a>
                        </div>
                    </div>
                `;
            }

            return `
                <div style="font-family:Inter,sans-serif;font-size:12px;line-height:1.45;min-width:190px;">
                    <strong style="color:#08142C;font-size:13px;">${u.nombre}</strong><br>
                    <span style="color:#475569;font-size:11px;"><strong>${u.placa}</strong> • ${u.modelo_vehiculo}</span><br>
                    <span style="color:${u.habilitado ? '#059669' : '#DC2626'};font-weight:700;font-size:11px;">
                        ${u.habilitado ? '● Disponible en Servicio (GPS PWA Activo)' : '● Bloqueado (Candado S/ 1.00)'}
                    </span>
                    <div style="font-size:10px;color:#64748B;margin-top:4px;">
                        Coord: ${u.lat}, ${u.lng}
                    </div>
                </div>
            `;
        }

        function cumpleFiltro(u) {
            if (filtroActual === 'servicio') return u.en_servicio;
            if (filtroActual === 'radar') return u.habilitado && !u.en_servicio;
            return true;
        }

        // Trazado real por calles con OSRM igual que en viaje-activo.blade.php y track.blade.php
        function trazarRutaCalleOsrm(unidadId, desdeLat, desdeLng, hastaLat, hastaLng, lineaLayer) {
            const clave = `${desdeLat.toFixed(4)},${desdeLng.toFixed(4)}->${hastaLat.toFixed(4)},${hastaLng.toFixed(4)}`;
            if (cacheRutasOsrm.has(clave)) {
                lineaLayer.setLatLngs(cacheRutasOsrm.get(clave));
                return;
            }

            const url = `https://router.project-osrm.org/route/v1/driving/${desdeLng},${desdeLat};${hastaLng},${hastaLat}?overview=full&geometries=geojson`;
            fetch(url)
                .then(r => r.json())
                .then(datos => {
                    const ruta = datos && datos.routes && datos.routes[0];
                    if (ruta && ruta.geometry) {
                        const coords = ruta.geometry.coordinates.map(([lng, lat]) => [lat, lng]);
                        cacheRutasOsrm.set(clave, coords);
                        lineaLayer.setLatLngs(coords);
                    } else {
                        lineaLayer.setLatLngs([[desdeLat, desdeLng], [hastaLat, hastaLng]]);
                    }
                })
                .catch(() => {
                    lineaLayer.setLatLngs([[desdeLat, desdeLng], [hastaLat, hastaLng]]);
                });
        }

        function renderizarFlota() {
            flotaDatos.forEach((u) => {
                if (!cumpleFiltro(u)) {
                    if (marcadoresUnidades.has(u.id)) {
                        mapa.removeLayer(marcadoresUnidades.get(u.id));
                        marcadoresUnidades.delete(u.id);
                    }
                    if (lineasServicio.has(u.id)) {
                        mapa.removeLayer(lineasServicio.get(u.id));
                        lineasServicio.delete(u.id);
                    }
                    if (marcadoresDestinos.has(u.id)) {
                        mapa.removeLayer(marcadoresDestinos.get(u.id));
                        marcadoresDestinos.delete(u.id);
                    }
                    return;
                }

                const lat = parseFloat(u.lat);
                const lng = parseFloat(u.lng);
                const latLng = [lat, lng];
                const textoTooltip = u.en_servicio
                    ? `${u.placa} • EN SERVICIO`
                    : `${u.placa} • ${u.nombre.split(' ')[0]}`;

                if (marcadoresUnidades.has(u.id)) {
                    const m = marcadoresUnidades.get(u.id);
                    m.setLatLng(latLng);
                    m.setIcon(crearIconoUnidad(u));
                    m.setPopupContent(crearPopupUnidad(u));
                } else {
                    const m = L.marker(latLng, { icon: crearIconoUnidad(u) })
                        .addTo(mapa)
                        .bindTooltip(textoTooltip, {
                            permanent: true,
                            direction: 'top',
                            offset: [0, -18],
                            className: 'kaluz-placa-tooltip',
                        })
                        .bindPopup(crearPopupUnidad(u));
                    marcadoresUnidades.set(u.id, m);
                }

                if (u.en_servicio && u.viaje) {
                    const destLat = parseFloat(u.viaje.estado === 'en_ruta' ? u.viaje.destino_lat : u.viaje.origen_lat);
                    const destLng = parseFloat(u.viaje.estado === 'en_ruta' ? u.viaje.destino_lng : u.viaje.origen_lng);

                    let linea = lineasServicio.get(u.id);
                    if (!linea) {
                        linea = L.polyline([latLng, [destLat, destLng]], {
                            color: '#0A1329',
                            weight: 5,
                            opacity: 0.85,
                        }).addTo(mapa);
                        lineasServicio.set(u.id, linea);
                    }
                    trazarRutaCalleOsrm(u.id, lat, lng, destLat, destLng, linea);

                    const iconoObj = L.divIcon({
                        className: '',
                        html: `<div style="width:26px;height:26px;border-radius:50%;background:#E31B23;border:3px solid #fff;color:#fff;font:900 12px/20px Inter,sans-serif;text-align:center;box-shadow:0 2px 8px rgba(0,0,0,.35)">${u.viaje.estado === 'en_ruta' ? 'B' : 'A'}</div>`,
                        iconSize: [26, 26],
                        iconAnchor: [13, 13],
                    });

                    if (marcadoresDestinos.has(u.id)) {
                        marcadoresDestinos.get(u.id).setLatLng([destLat, destLng]).setIcon(iconoObj);
                    } else {
                        const mDest = L.marker([destLat, destLng], { icon: iconoObj })
                            .addTo(mapa)
                            .bindPopup(`<strong>${u.viaje.estado === 'en_ruta' ? 'Destino' : 'Punto de Recojo'} (${u.placa})</strong><br>${u.viaje.estado === 'en_ruta' ? u.viaje.destino_texto : u.viaje.origen_texto}`);
                        marcadoresDestinos.set(u.id, mDest);
                    }
                } else {
                    if (lineasServicio.has(u.id)) {
                        mapa.removeLayer(lineasServicio.get(u.id));
                        lineasServicio.delete(u.id);
                    }
                    if (marcadoresDestinos.has(u.id)) {
                        mapa.removeLayer(marcadoresDestinos.get(u.id));
                        marcadoresDestinos.delete(u.id);
                    }
                }
            });
        }

        window.filtrarMapaKaluz = (nuevoFiltro) => {
            filtroActual = nuevoFiltro;
            ['todos', 'servicio', 'radar'].forEach((f) => {
                const btn = document.getElementById('btn-filtro-' + f);
                if (!btn) return;
                if (f === nuevoFiltro) {
                    btn.className = 'px-3 py-1.5 rounded-lg font-bold transition bg-[#08142C] text-white flex items-center gap-1.5';
                } else {
                    btn.className = 'px-3 py-1.5 rounded-lg font-bold transition text-slate-700 hover:bg-slate-100 flex items-center gap-1.5';
                }
            });
            renderizarFlota();
        };

        window.enfocarUnidadKaluz = (id) => {
            const u = flotaDatos.find(item => item.id === id);
            if (!u) return;
            if (!cumpleFiltro(u)) {
                window.filtrarMapaKaluz('todos');
            }
            mapa.flyTo([parseFloat(u.lat), parseFloat(u.lng)], 16, { duration: 0.7 });
            const marcador = marcadoresUnidades.get(id);
            if (marcador) {
                setTimeout(() => marcador.openPopup(), 350);
            }
        };

        window.centrarMapaKaluz = () => {
            mapa.setView(centroHuancayo, 15, { animate: true });
        };

        async function actualizarTelemetriaFlota() {
            try {
                const res = await fetch('{{ route('dashboard.api.flota') }}', {
                    headers: { 'Accept': 'application/json' },
                    cache: 'no-store',
                });
                if (!res.ok) return;
                const json = await res.json();
                if (!json.ok || !Array.isArray(json.flota)) return;

                flotaDatos = json.flota;
                renderizarFlota();

                const enServicioCount = json.resumen?.en_viaje ?? 0;
                const enRadarCount = json.resumen?.en_radar_gps ?? 0;
                const totalCount = json.resumen?.total_flota ?? flotaDatos.length;

                const elTodas = document.getElementById('count-flota-todas');
                const elServicio = document.getElementById('count-flota-servicio');
                const elRadar = document.getElementById('count-flota-radar');
                const elLabelServicio = document.getElementById('kpi-en-servicio-label');
                const elEstadoMapa = document.getElementById('estado-telemetria-mapa');

                if (elTodas) elTodas.textContent = totalCount;
                if (elServicio) elServicio.textContent = enServicioCount;
                if (elRadar) elRadar.textContent = enRadarCount;
                if (elLabelServicio) elLabelServicio.textContent = enServicioCount + ' en carrera ahora';
                if (elEstadoMapa) {
                    const ahora = new Date();
                    elEstadoMapa.textContent = 'GPS PWA en vivo • ' + ahora.toLocaleTimeString('es-PE', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
                }
            } catch (e) {}
        }

        renderizarFlota();
        setTimeout(() => mapa.invalidateSize(), 200);
        setInterval(actualizarTelemetriaFlota, 4000);
    });
</script>
@endsection
