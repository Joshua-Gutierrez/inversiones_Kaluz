@extends('layouts.pwa-mobile')

@section('titulo', 'Seguimiento en Vivo - Taxi KALUZ')
@section('tema', 'oscuro')
@section('ocultar_nav', '1')

@section('header_centro')
    <span class="inline-flex items-center gap-1.5 text-[11px] font-headline font-bold uppercase tracking-wide px-3 py-1 rounded-full bg-emerald-500 text-white shadow-[0_0_16px_rgba(16,185,129,0.55)]">
        <i class="ri-gps-line text-xs"></i>
        <span>RASTREO EN VIVO</span>
    </span>
@endsection

@section('head')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
@endsection

@section('content')
<div
    x-data="rastreoPasajero({
        viajeId: {{ (int) $viaje->id }},
        estadoInicial: @js($viaje->estado),
        ofertaCliente: {{ (float) ($viaje->oferta_cliente ?? 6.00) }},
        precioFinal: {{ $viaje->precio_final !== null ? (float) $viaje->precio_final : 'null' }},
        conductorInicial: @js($viaje->conductor_id ? [
            'id' => $viaje->conductor_id,
            'nombre' => $viaje->nombre,
            'placa' => $viaje->placa,
            'modelo' => $viaje->modelo_vehiculo,
            'telefono' => $viaje->telefono_whatsapp,
        ] : null),
        ofertasIniciales: @js($ofertasIniciales ?? []),
        origen: { lat: {{ (float) $viaje->origen_lat }}, lng: {{ (float) $viaje->origen_lng }}, texto: @js($viaje->origen_texto) },
        destino: { lat: {{ (float) $viaje->destino_lat }}, lng: {{ (float) $viaje->destino_lng }}, texto: @js($viaje->destino_texto) },
        taxiLat: {{ $viaje->lat_actual !== null ? (float) $viaje->lat_actual : ((float) $viaje->origen_lat - 0.0032) }},
        taxiLng: {{ $viaje->lng_actual !== null ? (float) $viaje->lng_actual : ((float) $viaje->origen_lng - 0.0038) }},
        posicionUrl: @js(route('turismo.posicion', ['id' => $viaje->id], false)),
        simularUrl: @js(route('turismo.simular', ['id' => $viaje->id], false)),
        simularOfertasUrl: @js(route('turismo.simular-ofertas', ['id' => $viaje->id], false)),
        csrfToken: @js(csrf_token())
    })"
    x-init="iniciar()"
    class="space-y-3.5"
>
    <!-- PANTALLA DE VIAJE FINALIZADO (Se muestra automáticamente cuando el taxista presiona "Finalizar viaje") -->
    <div x-show="estado === 'completado'" x-cloak x-transition>
        <div x-transition class="bg-[#111E3B] rounded-3xl border border-emerald-500/40 p-6 text-center shadow-2xl space-y-5 my-4">
            <div class="w-20 h-20 rounded-full bg-emerald-500/20 border-2 border-emerald-400 flex items-center justify-center mx-auto shadow-[0_0_28px_rgba(16,185,129,0.45)]">
                <i class="ri-checkbox-circle-fill text-5xl text-emerald-400"></i>
            </div>

            <div class="space-y-2">
                <span class="inline-flex items-center gap-1 bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 text-[11px] font-bold uppercase tracking-wider px-3 py-1 rounded-full">
                    <i class="ri-flag-2-fill"></i> Servicio Completado
                </span>
                <h1 class="font-headline font-black text-2xl text-white tracking-tight">
                    ¡Viaje Finalizado!
                </h1>
                <p class="text-sm text-slate-300 leading-relaxed max-w-xs mx-auto">
                    Gracias por su preferencia, no dude en volver a contactarnos.
                </p>
            </div>

            <!-- Resumen del recibo del viaje -->
            <div class="bg-[#081124] rounded-2xl border border-white/10 p-4 text-left space-y-3">
                <div class="flex items-center justify-between border-b border-white/10 pb-2.5">
                    <span class="text-xs text-slate-400 font-medium">Total del servicio</span>
                    <span class="font-headline font-black text-2xl text-emerald-400">
                        S/ <span x-text="Number(precioFinal || ofertaCliente).toFixed(2)"></span>
                    </span>
                </div>

                <div class="space-y-1.5 text-xs">
                    <div class="flex items-start gap-2 text-slate-200">
                        <i class="ri-map-pin-2-fill text-[#E31B23] shrink-0 mt-0.5"></i>
                        <span><strong class="text-slate-400">Origen:</strong> {{ $viaje->origen_texto }}</span>
                    </div>
                    <div class="flex items-start gap-2 text-slate-200">
                        <i class="ri-flag-2-fill text-emerald-400 shrink-0 mt-0.5"></i>
                        <span><strong class="text-slate-400">Destino:</strong> {{ $viaje->destino_texto }}</span>
                    </div>
                </div>

                <template x-if="conductor">
                    <div class="border-t border-white/10 pt-2.5 flex items-center gap-2.5 text-xs text-slate-300">
                        <i class="ri-taxi-fill text-amber-400 text-base"></i>
                        <span class="truncate">
                            <strong class="text-white" x-text="conductor.nombre"></strong> •
                            <span x-text="conductor.modelo"></span> (<span class="text-amber-300 font-bold" x-text="conductor.placa"></span>)
                        </span>
                    </div>
                </template>
            </div>

            <!-- Estrellas decorativas -->
            <div class="flex items-center justify-center gap-1.5 text-amber-400 text-xl">
                <i class="ri-star-fill"></i>
                <i class="ri-star-fill"></i>
                <i class="ri-star-fill"></i>
                <i class="ri-star-fill"></i>
                <i class="ri-star-fill"></i>
            </div>

            <div class="space-y-2.5 pt-1">
                <a href="https://wa.me/15552023552?text=Hola"
                   class="w-full bg-emerald-600 hover:bg-emerald-500 text-white font-headline font-bold text-sm py-3.5 px-4 rounded-xl shadow-lg transition flex items-center justify-center gap-2">
                    <i class="ri-whatsapp-line text-lg"></i>
                    <span>Volver al Chat de WhatsApp</span>
                </a>

                <button type="button" @click="cerrarVentana()"
                    class="w-full bg-[#081124] hover:bg-white/10 border border-white/15 text-slate-300 font-semibold text-xs py-3 px-4 rounded-xl transition flex items-center justify-center gap-1.5">
                    <i class="ri-close-circle-line text-sm"></i>
                    <span>Cerrar ventana</span>
                </button>
            </div>
        </div>
    </div>

    <!-- PANTALLA DE VIAJE CANCELADO -->
    <div x-show="estado === 'cancelado'" x-cloak x-transition>
        <div x-transition class="bg-[#111E3B] rounded-3xl border border-red-500/40 p-6 text-center shadow-2xl space-y-4 my-4">
            <div class="w-16 h-16 rounded-full bg-red-500/20 border border-red-400/50 flex items-center justify-center mx-auto text-red-400">
                <i class="ri-error-warning-fill text-4xl"></i>
            </div>
            <h2 class="font-headline font-bold text-xl text-white">Viaje Cancelado</h2>
            <p class="text-xs text-slate-300">Este servicio fue cancelado. Puedes solicitar otra unidad inmediatamente desde nuestro WhatsApp.</p>
            <a href="https://wa.me/15552023552?text=Hola"
               class="w-full bg-[#E31B23] hover:bg-red-700 text-white font-headline font-bold text-sm py-3 px-4 rounded-xl shadow-lg transition flex items-center justify-center gap-2">
                <i class="ri-whatsapp-line text-lg"></i>
                <span>Solicitar otro Taxi</span>
            </a>
        </div>
    </div>

    <!-- VISTA ACTIVA MIENTRAS EL VIAJE ESTÁ EN SUBASTA / ASIGNADO / EN RUTA -->
    <div x-show="estado !== 'completado' && estado !== 'cancelado'" class="space-y-3.5">
        <!-- Encabezado de ruta del pasajero -->
        <div class="flex items-center justify-between gap-2">
            <div class="min-w-0">
                <h1 class="font-headline font-extrabold text-lg text-white leading-tight">
                    Tu Viaje en Tiempo Real
                </h1>
                <p class="text-xs text-slate-300 truncate mt-0.5">
                    {{ $viaje->origen_texto }} <i class="ri-arrow-right-line text-[#E31B23]"></i> {{ $viaje->destino_texto }}
                </p>
            </div>
        </div>

        <!-- Aviso de estado -->
        <div x-show="aviso" x-transition
            class="rounded-2xl bg-emerald-500/20 border border-emerald-500/40 text-emerald-200 px-3.5 py-2.5 text-xs font-medium flex items-center justify-between">
            <span class="flex items-center gap-1.5">
                <i class="ri-checkbox-circle-fill text-emerald-400"></i>
                <span x-text="aviso"></span>
            </span>
            <button type="button" @click="aviso = ''"><i class="ri-close-line"></i></button>
        </div>

        <!-- 1. BANDEJA DE CONTRAOFERTAS DE CONDUCTORES (Cuando varios choferes contraofertan) -->
        <template x-if="!conductor && ofertas.length > 0">
            <div class="space-y-2.5">
                <div class="flex items-center justify-between px-1">
                    <span class="text-xs font-headline font-bold text-amber-300 flex items-center gap-1.5">
                        <i class="ri-Notification-badge-line animate-bounce"></i>
                        <span x-text="ofertas.length + ' conductores enviaron su propuesta • Elige el más conveniente:'"></span>
                    </span>
                </div>

                <template x-for="of in ofertas" :key="of.id">
                    <div class="bg-[#111E3B] rounded-2xl border border-amber-400/40 p-3.5 shadow-lg space-y-2.5">
                        <div class="flex items-center justify-between gap-3">
                            <div class="flex items-center gap-2.5 min-w-0">
                                <div class="w-10 h-10 rounded-full bg-gradient-to-br from-blue-500 to-indigo-700 text-white font-headline font-bold text-sm flex items-center justify-center shrink-0">
                                    <span x-text="of.conductor_nombre.charAt(0)"></span>
                                </div>
                                <div class="min-w-0">
                                    <p class="font-headline font-bold text-sm text-white truncate" x-text="of.conductor_nombre"></p>
                                    <p class="text-[11px] text-slate-300 truncate">
                                        <span x-text="of.modelo"></span> • <strong class="text-amber-300" x-text="of.placa"></strong>
                                    </p>
                                    <p class="text-[11px] text-emerald-400 font-semibold flex items-center gap-1 mt-0.5">
                                        <i class="ri-time-line"></i>
                                        <span>A <span x-text="of.tiempo_min"></span> min de tu ubicación</span>
                                    </p>
                                </div>
                            </div>

                            <div class="text-right shrink-0">
                                <span class="text-[10px] text-slate-400 uppercase font-bold block">Contraoferta</span>
                                <span class="font-headline font-black text-2xl text-white">
                                    S/ <span x-text="Number(of.monto).toFixed(2)"></span>
                                </span>
                            </div>
                        </div>

                        <button type="button"
                            @click="elegirContraoferta(of)"
                            :disabled="eligiendo === of.id"
                            class="w-full bg-[#D91B23] hover:bg-[#B8141B] disabled:opacity-50 text-white font-headline font-bold text-xs py-2.5 rounded-xl shadow transition flex items-center justify-center gap-1.5">
                            <i class="ri-check-double-line text-sm"></i>
                            <span x-text="eligiendo === of.id ? 'Confirmando unidad...' : 'Elegir este conductor por S/ ' + Number(of.monto).toFixed(2)"></span>
                        </button>
                    </div>
                </template>
            </div>
        </template>

        <!-- 2. MAPA GPS EN TIEMPO REAL ESTILO INDRIVE (Ubicación Cliente + Ubicación Chofer) -->
        <div class="relative rounded-2xl overflow-hidden border border-white/15 shadow-xl">
            <div id="mapa-rastreo" class="h-[44vh] min-h-72 w-full bg-slate-800 z-0"></div>

            <!-- Píldora superior de distancia y acercamiento/alejamiento en tiempo real -->
            <div class="absolute top-3 left-3 right-3 z-[400] flex items-center justify-between gap-2 pointer-events-none">
                <div class="bg-[#0A1329]/95 backdrop-blur-xs border border-white/15 text-white px-3 py-1.5 rounded-xl shadow-md text-xs font-semibold flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-emerald-400 animate-ping"></span>
                    <span x-text="textoDistancia">Calculando distancia al taxi...</span>
                </div>

                <span class="bg-[#111E3B]/95 border border-white/15 text-[11px] font-bold px-2.5 py-1.5 rounded-xl text-amber-300 shadow-md"
                      x-text="tendenciaMovimiento">
                </span>
            </div>

            <!-- Leyenda inferior en el mapa -->
            <div class="absolute bottom-3 left-3 z-[400] bg-[#0A1329]/90 backdrop-blur-xs border border-white/15 rounded-xl px-3 py-1.5 text-[10px] text-slate-200 flex items-center gap-3">
                <span class="flex items-center gap-1">
                    <span class="w-2.5 h-2.5 rounded-full bg-blue-500 inline-block"></span> Tu GPS
                </span>
                <span class="flex items-center gap-1">
                    <span class="w-2.5 h-2.5 rounded-full bg-[#E31B23] inline-block"></span> Taxi KALUZ
                </span>
                <span class="flex items-center gap-1">
                    <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 inline-block"></span> Destino
                </span>
            </div>
        </div>

        <!-- 3. PANEL DEL CONDUCTOR ASIGNADO Y SIMULADOR DE MOVIMIENTO GPS -->
        <section class="rounded-2xl border border-white/10 bg-[#111E3B] p-4 shadow-lg space-y-3.5">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Estado de la Carrera</p>
                    <p class="font-headline font-bold text-sm text-white mt-0.5"
                       x-text="estado === 'en_subasta' ? 'Recibiendo ofertas de conductores' : (estado === 'asignado' ? 'El conductor va en camino a recogerte' : (estado === 'en_ruta' ? 'Viaje en curso hacia tu destino' : ucfirst(estado)))">
                    </p>
                </div>
                <div class="text-right">
                    <span class="text-[10px] text-slate-400 uppercase font-bold block">Tarifa</span>
                    <span class="font-headline font-black text-xl text-emerald-400">
                        S/ <span x-text="Number(precioFinal || ofertaCliente).toFixed(2)"></span>
                    </span>
                </div>
            </div>

            <template x-if="conductor">
                <div class="border-t border-white/10 pt-3.5 space-y-3">
                    <div class="flex items-center justify-between gap-3">
                        <div class="flex items-center gap-3 min-w-0">
                            <div class="w-11 h-11 rounded-full bg-emerald-500/20 border border-emerald-500/40 text-emerald-300 flex items-center justify-center shrink-0">
                                <i class="ri-taxi-fill text-xl"></i>
                            </div>
                            <div class="min-w-0">
                                <p class="font-headline font-bold text-sm text-white truncate" x-text="conductor.nombre"></p>
                                <p class="text-xs text-slate-300 truncate">
                                    <span x-text="conductor.modelo"></span> • <strong class="text-amber-300" x-text="conductor.placa"></strong>
                                </p>
                            </div>
                        </div>

                        <a :href="'https://wa.me/51' + String(conductor.telefono || '964111222').replace(/\D/g, '')"
                           target="_blank"
                           class="shrink-0 inline-flex items-center gap-1.5 bg-emerald-600 hover:bg-emerald-500 rounded-xl px-3.5 py-2.5 text-xs font-bold text-white shadow-md transition">
                            <i class="ri-whatsapp-line text-base"></i>
                            <span>WhatsApp</span>
                        </a>
                    </div>

                    <!-- Botón para simular el movimiento GPS en vivo (acercamiento del chofer al cliente y luego al destino) -->
                    <button type="button" @click="avanzarTaxi()"
                        class="flex w-full items-center justify-center gap-2 rounded-xl border border-white/20 bg-white/5 hover:bg-white/10 px-4 py-2.5 text-xs font-bold text-white transition">
                        <i class="ri-navigation-fill text-emerald-400"></i>
                        <span>Simular movimiento GPS del Taxi (Acercarse)</span>
                    </button>
                </div>
            </template>

            <template x-if="!conductor && ofertas.length === 0">
                <div class="rounded-xl border border-amber-500/30 bg-amber-500/15 p-3 text-xs text-amber-200 flex items-center justify-between gap-2">
                    <div class="flex items-center gap-2">
                        <i class="ri-radar-line text-base animate-spin"></i>
                        <span>Esperando contraofertas de taxistas cercanos...</span>
                    </div>
                    <button type="button" @click="simularContraofertas()" class="underline font-bold shrink-0">
                        Generar ofertas
                    </button>
                </div>
            </template>
        </section>
    </div>
</div>
@endsection

@section('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
    function rastreoPasajero(cfg) {
        return {
            estado: cfg.estadoInicial,
            ofertaCliente: cfg.ofertaCliente,
            precioFinal: cfg.precioFinal,
            conductor: cfg.conductorInicial,
            ofertas: cfg.ofertasIniciales || [],
            eligiendo: null,
            aviso: '',
            clienteLat: cfg.origen.lat,
            clienteLng: cfg.origen.lng,
            taxiLat: cfg.taxiLat,
            taxiLng: cfg.taxiLng,
            distanciaPreviaMetros: null,
            textoDistancia: 'Calculando ruta GPS...',
            tendenciaMovimiento: 'En vivo',
            mapa: null,
            marcadorCliente: null,
            marcadorTaxi: null,
            marcadorDestino: null,
            lineaRuta: null,
            timer: null,

            iniciar() {
                this.iniciarMapa();
                this.obtenerGpsCliente();
                this.actualizarRutaYDistancia();
                this.timer = setInterval(() => this.consultarPosicion(), 2000);
            },

            ucfirst(t) {
                return t ? t.charAt(0).toUpperCase() + t.slice(1).replace('_', ' ') : '';
            },

            iniciarMapa() {
                const el = document.getElementById('mapa-rastreo');
                if (!el || typeof L === 'undefined') return;

                this.mapa = L.map(el, { zoomControl: true, attributionControl: true })
                    .setView([this.clienteLat, this.clienteLng], 15);

                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    maxZoom: 19,
                    attribution: '&copy; OpenStreetMap',
                }).addTo(this.mapa);

                // 1. Marcador del Cliente (Tu ubicación en azul con pulso)
                const iconoCliente = L.divIcon({
                    className: '',
                    html: `<div style="width:28px;height:28px;border-radius:50%;background:#2563EB;border:3px solid #fff;color:#fff;display:flex;align-items:center;justify-content:center;box-shadow:0 0 12px rgba(37,99,235,0.7);"><i class="ri-user-location-fill" style="font-size:14px;"></i></div>`,
                    iconSize: [28, 28],
                    iconAnchor: [14, 14],
                });
                this.marcadorCliente = L.marker([this.clienteLat, this.clienteLng], { icon: iconoCliente })
                    .addTo(this.mapa)
                    .bindPopup('<strong>Tu ubicación (Pasajero)</strong><br>' + cfg.origen.texto);

                // 2. Marcador del Destino (Verde)
                const iconoDestino = L.divIcon({
                    className: '',
                    html: `<div style="width:28px;height:28px;border-radius:50%;background:#10B981;border:3px solid #fff;color:#fff;display:flex;align-items:center;justify-content:center;box-shadow:0 3px 8px rgba(0,0,0,0.3);"><i class="ri-flag-2-fill" style="font-size:14px;"></i></div>`,
                    iconSize: [28, 28],
                    iconAnchor: [14, 14],
                });
                this.marcadorDestino = L.marker([cfg.destino.lat, cfg.destino.lng], { icon: iconoDestino })
                    .addTo(this.mapa)
                    .bindPopup('<strong>Destino</strong><br>' + cfg.destino.texto);

                // 3. Marcador del Taxi KALUZ en tiempo real (Rojo)
                const iconoTaxi = L.divIcon({
                    className: '',
                    html: `<div style="width:34px;height:34px;border-radius:50%;background:#D91B23;border:3px solid #fff;color:#fff;display:flex;align-items:center;justify-content:center;box-shadow:0 4px 12px rgba(217,27,35,0.6);"><i class="ri-taxi-fill" style="font-size:17px;"></i></div>`,
                    iconSize: [34, 34],
                    iconAnchor: [17, 17],
                });
                this.marcadorTaxi = L.marker([this.taxiLat, this.taxiLng], { icon: iconoTaxi })
                    .addTo(this.mapa)
                    .bindPopup('<strong>Unidad Taxi KALUZ</strong>');

                // Línea de ruta entre el Taxi y el Objetivo (Cliente o Destino)
                this.lineaRuta = L.polyline([], {
                    color: '#0A1329',
                    weight: 5,
                    opacity: 0.85,
                }).addTo(this.mapa);

                setTimeout(() => {
                    this.mapa.invalidateSize();
                    const bounds = L.latLngBounds([
                        [this.clienteLat, this.clienteLng],
                        [this.taxiLat, this.taxiLng],
                        [cfg.destino.lat, cfg.destino.lng],
                    ]);
                    if (bounds.isValid()) {
                        this.mapa.fitBounds(bounds, { padding: [36, 36], maxZoom: 16 });
                    }
                }, 200);
            },

            obtenerGpsCliente() {
                if (!navigator.geolocation) return;
                navigator.geolocation.getCurrentPosition(
                    (pos) => {
                        // Si el usuario está dentro de Huancayo (~25 km), usamos su GPS real;
                        // si está probando desde otra ciudad, mantenemos las coordenadas de Huancayo para que el mapa luzca coherente.
                        const distHuancayo = Math.hypot(pos.coords.latitude - (-12.0651), pos.coords.longitude - (-75.2049));
                        if (distHuancayo < 0.25) {
                            this.clienteLat = pos.coords.latitude;
                            this.clienteLng = pos.coords.longitude;
                            if (this.marcadorCliente) {
                                this.marcadorCliente.setLatLng([this.clienteLat, this.clienteLng]);
                            }
                            this.actualizarRutaYDistancia();
                        }
                    },
                    () => {},
                    { enableHighAccuracy: true, timeout: 5000 }
                );
            },

            actualizarRutaYDistancia() {
                if (!this.mapa || !this.marcadorTaxi) return;

                this.marcadorTaxi.setLatLng([this.taxiLat, this.taxiLng]);

                const haciaDestino = this.estado === 'en_ruta';
                const objLat = haciaDestino ? cfg.destino.lat : this.clienteLat;
                const objLng = haciaDestino ? cfg.destino.lng : this.clienteLng;

                const metrosDirectos = L.latLng(this.taxiLat, this.taxiLng).distanceTo(L.latLng(objLat, objLng));

                if (this.distanciaPreviaMetros !== null) {
                    const delta = this.distanciaPreviaMetros - metrosDirectos;
                    if (Math.abs(delta) > 8) {
                        this.tendenciaMovimiento = delta > 0 ? 'Acercándose ↓' : 'Alejándose ↑';
                    }
                }
                this.distanciaPreviaMetros = metrosDirectos;

                const urlOsrm = `https://router.project-osrm.org/route/v1/driving/${this.taxiLng},${this.taxiLat};${objLng},${objLat}?overview=full&geometries=geojson`;

                fetch(urlOsrm)
                    .then(r => r.json())
                    .then(d => {
                        const ruta = d?.routes?.[0];
                        if (!ruta) throw new Error('sin ruta');
                        const coords = ruta.geometry.coordinates.map(([lng, lat]) => [lat, lng]);
                        this.lineaRuta.setLatLngs(coords);

                        const distTexto = ruta.distance < 1000
                            ? Math.round(ruta.distance) + ' m'
                            : (ruta.distance / 1000).toFixed(1) + ' km';
                        const minTexto = Math.max(1, Math.round(ruta.duration / 60)) + ' min';

                        this.textoDistancia = (haciaDestino ? 'A destino: ' : 'Taxi a ') + distTexto + ' • ' + minTexto;
                    })
                    .catch(() => {
                        this.lineaRuta.setLatLngs([[this.taxiLat, this.taxiLng], [objLat, objLng]]);
                        const distTexto = metrosDirectos < 1000
                            ? Math.round(metrosDirectos) + ' m'
                            : (metrosDirectos / 1000).toFixed(1) + ' km';
                        const minTexto = Math.max(1, Math.round(metrosDirectos / 280)) + ' min';
                        this.textoDistancia = (haciaDestino ? 'A destino: ' : 'Taxi a ') + distTexto + ' • ' + minTexto;
                    });
            },

            async consultarPosicion() {
                try {
                    const sep = cfg.posicionUrl.includes('?') ? '&' : '?';
                    const url = cfg.posicionUrl + sep + '_t=' + Date.now();
                    const r = await fetch(url, {
                        cache: 'no-store',
                        headers: {
                            'Accept': 'application/json',
                            'Cache-Control': 'no-cache, no-store',
                        }
                    });
                    if (!r.ok) return;
                    const d = await r.json();

                    const estadoAnterior = this.estado;
                    this.estado = d.estado;
                    this.precioFinal = d.precio_final;
                    this.conductor = d.conductor;
                    this.ofertas = d.ofertas || [];

                    if (d.estado === 'completado' || d.estado === 'cancelado') {
                        if (this.timer) {
                            clearInterval(this.timer);
                            this.timer = null;
                        }
                        if (estadoAnterior !== d.estado) {
                            window.scrollTo({ top: 0, behavior: 'smooth' });
                            if (navigator.vibrate) {
                                navigator.vibrate([150, 80, 150]);
                            }
                        }
                        return;
                    }

                    if (d.lat !== null && d.lng !== null) {
                        this.taxiLat = d.lat;
                        this.taxiLng = d.lng;
                        this.actualizarRutaYDistancia();
                    }
                } catch (e) {}
            },

            cerrarVentana() {
                window.close();
                setTimeout(() => {
                    window.location.href = 'https://wa.me/15552023552?text=Hola';
                }, 300);
            },

            async elegirContraoferta(oferta) {
                this.eligiendo = oferta.id;
                try {
                    const r = await fetch(`/conductor/api/ofertas/${oferta.id}/aceptar`, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': cfg.csrfToken,
                        },
                    });
                    const json = await r.json();
                    if (json.ok) {
                        this.aviso = `¡Elegiste a ${oferta.conductor_nombre} (${oferta.placa}) por S/ ${Number(oferta.monto).toFixed(2)}!`;
                        await this.consultarPosicion();
                    } else {
                        this.aviso = json.mensaje || 'No se pudo aceptar la oferta.';
                    }
                } catch (e) {
                    this.aviso = 'Error de conexión al aceptar la oferta.';
                } finally {
                    this.eligiendo = null;
                }
            },

            async simularContraofertas() {
                try {
                    const r = await fetch(cfg.simularOfertasUrl, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': cfg.csrfToken,
                        },
                    });
                    if (r.ok) {
                        const d = await r.json();
                        this.estado = d.estado;
                        this.conductor = d.conductor;
                        this.ofertas = d.ofertas || [];
                        this.aviso = 'Se recibieron ' + this.ofertas.length + ' contraofertas de conductores cercanos.';
                    }
                } catch (e) {}
            },

            async avanzarTaxi() {
                try {
                    const r = await fetch(cfg.simularUrl, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': cfg.csrfToken,
                        },
                    });
                    if (r.ok) {
                        const d = await r.json();
                        this.estado = d.estado;
                        if (d.lat !== null && d.lng !== null) {
                            this.taxiLat = d.lat;
                            this.taxiLng = d.lng;
                            this.actualizarRutaYDistancia();
                            this.mapa.panTo([this.taxiLat, this.taxiLng], { animate: true });
                        }
                    }
                } catch (e) {}
            },
        };
    }
</script>
@endsection
