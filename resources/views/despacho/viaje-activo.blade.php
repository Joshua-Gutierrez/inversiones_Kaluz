@extends('layouts.pwa-mobile')

@section('titulo', 'Viaje en Curso - Taxi KALUZ')
@section('tema', 'oscuro')

@section('header_centro')
    <span class="inline-flex items-center gap-1.5 text-[11px] font-headline font-bold uppercase tracking-wide px-3 py-1 rounded-full bg-emerald-500 text-white shadow-[0_0_16px_rgba(16,185,129,0.55)]">
        <i class="ri-navigation-fill text-xs"></i>
        EN RUTA GPS
    </span>
@endsection

@section('header_derecha')
    <span class="text-xs font-bold text-slate-200">{{ $conductor->placa }}</span>
@endsection

@section('head')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
@endsection

@section('content')
<div
    x-data="viajeActivo('{{ route('despacho.api.viaje.estado', ['id' => $viajeActivo->id, 'conductor_id' => $conductor->id]) }}', '{{ route('despacho.api.ubicacion', ['conductor_id' => $conductor->id]) }}', {{ $conductor->lat_actual ?? 'null' }}, {{ $conductor->lng_actual ?? 'null' }}, {{ (int) $conductor->id }})"
    x-init="iniciarGps()"
    class="space-y-4"
>
    {{-- Aviso de resultado de acciones --}}
    <div x-show="aviso" x-transition.opacity
        class="rounded-2xl px-4 py-3 text-xs font-medium border flex items-center gap-2"
        :class="avisoOk ? 'bg-emerald-500/20 text-emerald-200 border-emerald-500/40' : 'bg-red-500/20 text-red-200 border-red-500/40'">
        <i :class="avisoOk ? 'ri-checkbox-circle-fill text-emerald-400' : 'ri-error-warning-fill text-red-400'"></i>
        <span x-text="aviso"></span>
    </div>

    {{-- Datos del viaje --}}
    <div class="bg-[#111E3B] rounded-2xl border border-white/10 p-4 space-y-3 shadow-lg">
        <div class="flex justify-between items-start gap-3">
            <div class="min-w-0 space-y-1.5">
                <span class="inline-flex items-center gap-1 text-[10px] font-bold uppercase tracking-wide px-2.5 py-0.5 rounded-full border"
                    :class="estado === 'en_ruta'
                        ? 'bg-amber-500/20 text-amber-300 border-amber-500/40'
                        : 'bg-emerald-500/20 text-emerald-300 border-emerald-500/40'">
                    <i class="ri-taxi-fill"></i>
                    <span x-text="estado === 'en_ruta' ? 'EN RUTA AL DESTINO' : 'ASIGNADO - IR A RECOGER'"></span>
                </span>
                <div class="text-sm space-y-1 pt-1">
                    <p class="flex items-start gap-1.5 text-white font-semibold">
                        <i class="ri-map-pin-2-fill text-[#E31B23] shrink-0 mt-0.5"></i>
                        <span>{{ $viajeActivo->origen_texto }}</span>
                    </p>
                    <p class="flex items-start gap-1.5 text-slate-200 font-semibold">
                        <i class="ri-flag-2-fill text-emerald-400 shrink-0 mt-0.5"></i>
                        <span>{{ $viajeActivo->destino_texto }}</span>
                    </p>
                </div>
            </div>
            <div class="text-right shrink-0">
                <span class="text-[10px] text-slate-400 uppercase font-bold block">Tarifa acordada</span>
                <span class="font-headline font-black text-2xl text-white">S/ {{ number_format($viajeActivo->precio_final ?? $viajeActivo->oferta_cliente, 2) }}</span>
            </div>
        </div>
    </div>

    {{-- Mapa libre (Leaflet + OpenStreetMap): recojo (asignado) o destino (en ruta) --}}
    <div class="relative z-0 bg-[#111E3B] rounded-2xl shadow-lg border border-white/10 overflow-hidden">
        <div id="mapa-viaje" class="w-full h-64 bg-slate-800"></div>

        <div id="mapa-eta"
            class="absolute left-3 bottom-3 z-[500] bg-[#0A1329]/95 text-white text-[11px] font-bold px-3 py-1.5 rounded-full shadow-md border border-white/15">
            Calculando ruta…
        </div>

        <span class="absolute top-3 right-3 z-[500] bg-[#0A1329]/90 text-emerald-400 text-[10px] font-bold uppercase tracking-wide px-2.5 py-1 rounded-full shadow-sm border border-emerald-500/30 flex items-center gap-1">
            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span> GPS En vivo
        </span>

        <a href="https://www.google.com/maps/dir/?api=1&destination={{ $viajeActivo->estado === 'en_ruta' ? $ubicacionDestino['lat'].','.$ubicacionDestino['lng'] : $ubicacionOrigen['lat'].','.$ubicacionOrigen['lng'] }}"
            target="_blank" rel="noopener"
            class="absolute right-3 bottom-3 z-[500] bg-white text-[#0A1329] text-[10px] font-bold px-2.5 py-1.5 rounded-full shadow-sm flex items-center gap-1">
            <i class="ri-compass-3-line"></i>
            <span>Navegar</span>
        </a>
    </div>

    {{-- Datos del cliente --}}
    <div class="bg-[#111E3B] rounded-2xl border border-white/10 p-4 flex items-center justify-between gap-3 shadow-lg">
        <div class="flex items-center gap-3 min-w-0">
            <div class="w-10 h-10 rounded-full bg-white/10 flex items-center justify-center text-white shrink-0">
                <i class="ri-user-smile-line text-lg"></i>
            </div>
            <div class="min-w-0">
                <p class="font-bold text-sm text-white truncate">{{ $viajeActivo->cliente_nombre ?: 'Pasajero WhatsApp' }}</p>
                <p class="text-xs text-slate-400">{{ $viajeActivo->cliente_telefono }}</p>
            </div>
        </div>
        <a href="https://wa.me/{{ preg_replace('/\D/', '', $viajeActivo->cliente_telefono) }}"
            target="_blank"
            class="shrink-0 bg-emerald-600 hover:bg-emerald-500 text-white font-bold rounded-xl px-3.5 py-2.5 text-xs shadow-sm flex items-center gap-1.5">
            <i class="ri-whatsapp-line text-base"></i>
            <span>WhatsApp</span>
        </a>
    </div>

    {{-- Botonera de estados --}}
    <div class="bg-[#111E3B] rounded-2xl border border-white/10 p-4 space-y-2.5 shadow-lg">
        <button
            x-show="estado === 'asignado'"
            type="button"
            @click="cambiarEstado('en_ruta')"
            :disabled="enviando"
            class="w-full bg-[#D91B23] hover:bg-[#B9151C] disabled:opacity-60 text-white font-bold rounded-xl shadow-md py-3.5 text-sm flex items-center justify-center gap-2"
        >
            <i class="ri-taxi-fill text-base"></i>
            <span>Pasajero a bordo • Iniciar viaje</span>
        </button>

        <button
            x-show="estado === 'en_ruta'"
            type="button"
            @click="cambiarEstado('completado')"
            :disabled="enviando"
            class="w-full bg-emerald-600 hover:bg-emerald-500 disabled:opacity-60 text-white font-bold rounded-xl shadow-md py-3.5 text-sm flex items-center justify-center gap-2"
        >
            <i class="ri-checkbox-circle-fill text-base"></i>
            <span>Finalizar viaje</span>
        </button>

        <button
            x-show="estado === 'asignado' || estado === 'en_ruta'"
            type="button"
            @click="if (confirm('¿Cancelar este viaje? El pasajero será notificado.')) cambiarEstado('cancelado')"
            :disabled="enviando"
            class="w-full bg-[#081124] border border-red-500/40 text-red-400 font-bold rounded-xl py-3 text-xs hover:bg-red-500/10 flex items-center justify-center gap-1.5"
        >
            <i class="ri-close-circle-line text-sm"></i>
            <span>Cancelar viaje</span>
        </button>

        <p class="text-[11px] text-slate-400 text-center pt-1 flex items-center justify-center gap-1">
            <i class="ri-gps-line text-emerald-400"></i>
            <span>Tu ubicación GPS se actualiza cada 5 s para el enlace del pasajero.</span>
        </p>
    </div>
</div>
@endsection

@section('scripts')
<script>
    window.mapaViajeConfig = {
        origen: {
            lat: {{ $ubicacionOrigen['lat'] }},
            lng: {{ $ubicacionOrigen['lng'] }},
            texto: @json($viajeActivo->origen_texto),
        },
        destino: {
            lat: {{ $ubicacionDestino['lat'] }},
            lng: {{ $ubicacionDestino['lng'] }},
            texto: @json($viajeActivo->destino_texto),
        },
        conductor: {
            lat: {{ $conductor->lat_actual !== null ? (float) $conductor->lat_actual : 'null' }},
            lng: {{ $conductor->lng_actual !== null ? (float) $conductor->lng_actual : 'null' }},
        },
        estado: @json($viajeActivo->estado),
    };

    window.ultimaUbicacionConductor = {
        lat: window.mapaViajeConfig.conductor.lat ?? window.mapaViajeConfig.origen.lat,
        lng: window.mapaViajeConfig.conductor.lng ?? window.mapaViajeConfig.origen.lng,
        estado: window.mapaViajeConfig.estado,
    };

    window.actualizarMapaViaje = function (lat, lng, estado) {
        window.ultimaUbicacionConductor = { lat: lat, lng: lng, estado: estado };
        if (window.viajeMapa) window.viajeMapa.actualizar(lat, lng, estado);
    };

    function iconoObjetivo(letra) {
        return L.divIcon({
            className: '',
            html: '<div style="width:26px;height:26px;border-radius:50%;background:#E31B23;border:3px solid #fff;color:#fff;font:900 12px/20px Inter,sans-serif;text-align:center;box-shadow:0 2px 8px rgba(0,0,0,.35)">'+letra+'</div>',
            iconSize: [26, 26],
            iconAnchor: [13, 13],
        });
    }

    function initViajeMapa() {
        const cfg = window.mapaViajeConfig || {};
        const contenedor = document.getElementById('mapa-viaje');

        if (!contenedor || typeof L === 'undefined') return;

        const inicio = window.ultimaUbicacionConductor || cfg.conductor || cfg.origen;

        const mapa = L.map(contenedor, {
            zoomControl: true,
            attributionControl: true,
        }).setView([inicio.lat, inicio.lng], 15);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; OpenStreetMap',
        }).addTo(mapa);

        const marcadorConductor = L.circleMarker([inicio.lat, inicio.lng], {
            radius: 8,
            color: '#FFFFFF',
            weight: 3,
            fillColor: '#0B1B3D',
            fillOpacity: 1,
        }).addTo(mapa);

        const marcadorObjetivo = L.marker([cfg.origen.lat, cfg.origen.lng], {
            icon: iconoObjetivo('A'),
            title: cfg.origen.texto,
        }).addTo(mapa);

        const linea = L.polyline([], {
            color: '#0B1B3D',
            weight: 5,
            opacity: 0.55,
        }).addTo(mapa);

        const eta = document.getElementById('mapa-eta');

        let estado = cfg.estado;
        let objetivo = null;
        let ultimaPosicion = null;
        let ultimaRuta = 0;
        let primerTrazado = true;
        let peticionRuta = 0;

        const objetivoActual = () => (estado === 'en_ruta' ? cfg.destino : cfg.origen);

        function textoEta(texto) {
            if (!eta) return;
            eta.textContent = (estado === 'en_ruta' ? 'Al destino · ' : 'Al recojo · ') + texto;
        }

        function formatearDistancia(metros) {
            return metros < 1000 ? Math.round(metros) + ' m' : (metros / 1000).toFixed(1) + ' km';
        }

        function formatearDuracion(segundos) {
            const minutos = Math.max(1, Math.round(segundos / 60));
            return minutos + ' min';
        }

        function distanciaMetros(a, b) {
            return L.latLng(a[0], a[1]).distanceTo(L.latLng(b[0], b[1]));
        }

        function dibujarRuta(reajustar) {
            const desde = marcadorConductor.getLatLng();
            const hasta = L.latLng(objetivo.lat, objetivo.lng);
            const idPeticion = ++peticionRuta;

            ultimaRuta = Date.now();

            const url = 'https://router.project-osrm.org/route/v1/driving/'
                + desde.lng + ',' + desde.lat + ';' + hasta.lng + ',' + hasta.lat
                + '?overview=full&geometries=geojson';

            fetch(url)
                .then((respuesta) => respuesta.json())
                .then((datos) => {
                    if (idPeticion !== peticionRuta) return;

                    const ruta = datos && datos.routes && datos.routes[0];

                    if (!ruta || !ruta.geometry) throw new Error('sin ruta');

                    const puntos = ruta.geometry.coordinates.map(([lng, lat]) => [lat, lng]);
                    linea.setLatLngs(puntos);
                    textoEta(formatearDistancia(ruta.distance) + ' · ' + formatearDuracion(ruta.duration));

                    if (reajustar) mapa.fitBounds(linea.getBounds(), { padding: [30, 30] });
                })
                .catch(() => {
                    if (idPeticion !== peticionRuta) return;

                    linea.setLatLngs([desde, hasta]);
                    textoEta(formatearDistancia(distanciaMetros([desde.lat, desde.lng], [hasta.lat, hasta.lng])) + ' en línea recta');

                    if (reajustar) mapa.fitBounds(linea.getBounds(), { padding: [30, 30] });
                });
        }

        function fijarObjetivo() {
            objetivo = objetivoActual();
            marcadorObjetivo.setLatLng([objetivo.lat, objetivo.lng]);
            marcadorObjetivo.setIcon(iconoObjetivo(estado === 'en_ruta' ? 'B' : 'A'));
            marcadorObjetivo.bindTooltip(
                (estado === 'en_ruta' ? 'Destino: ' : 'Recoger en: ') + objetivo.texto,
                { direction: 'top', offset: [0, -14] }
            );
        }

        function actualizar(lat, lng, nuevoEstado) {
            const posicion = L.latLng(lat, lng);
            marcadorConductor.setLatLng(posicion);

            if (nuevoEstado && nuevoEstado !== estado) {
                estado = nuevoEstado;
                primerTrazado = true;
                fijarObjetivo();
                dibujarRuta(true);
            }

            const movido = ultimaPosicion ? posicion.distanceTo(ultimaPosicion) : Infinity;

            if (movido > 60 || Date.now() - ultimaRuta > 20000) {
                dibujarRuta(primerTrazado);
                primerTrazado = false;
            }

            ultimaPosicion = posicion;
            mapa.panTo(posicion);
        }

        window.viajeMapa = { actualizar: actualizar };

        fijarObjetivo();
        actualizar(inicio.lat, inicio.lng, estado);

        setTimeout(() => mapa.invalidateSize(), 200);
    }

    function viajeActivo(urlEstado, urlUbicacion, latDefault, lngDefault, conductorId) {
        return {
            estado: @json($viajeActivo->estado),
            aviso: '',
            avisoOk: true,
            enviando: false,
            gpsTimer: null,
            lat: latDefault,
            lng: lngDefault,

            iniciarGps() {
                // Primera posición lo antes posible y luego cada ~5 s.
                this.obtenerPosicion();
                this.gpsTimer = setInterval(() => this.obtenerPosicion(), 5000);
            },

            obtenerPosicion() {
                const fallback = () => {
                    // Sin GPS real: avanzamos hacia el objetivo (recojo o destino)
                    // para que la demo muestre el acercamiento en el mapa.
                    const cfg = window.mapaViajeConfig || {};
                    const objetivo = this.estado === 'en_ruta' ? cfg.destino : cfg.origen;
                    const baseLat = this.lat ?? -12.0651;
                    const baseLng = this.lng ?? -75.2049;

                    if (objetivo) {
                        this.lat = baseLat + (objetivo.lat - baseLat) * 0.12;
                        this.lng = baseLng + (objetivo.lng - baseLng) * 0.12;

                        if (Math.abs(objetivo.lat - this.lat) < 0.0002 && Math.abs(objetivo.lng - this.lng) < 0.0002) {
                            this.lat = objetivo.lat;
                            this.lng = objetivo.lng;
                        }
                    } else {
                        this.lat = baseLat + (Math.random() - 0.5) * 0.004;
                        this.lng = baseLng + (Math.random() - 0.5) * 0.004;
                    }

                    this.enviarUbicacion();
                    this.empujarMapa();
                };

                if (!navigator.geolocation) { fallback(); return; }

                navigator.geolocation.getCurrentPosition(
                    pos => {
                        this.lat = pos.coords.latitude;
                        this.lng = pos.coords.longitude;
                        this.enviarUbicacion();
                        this.empujarMapa();
                    },
                    () => fallback(),
                    { enableHighAccuracy: true, timeout: 8000, maximumAge: 3000 }
                );
            },

            async enviarUbicacion() {
                if (this.lat == null || this.lng == null) return;
                try {
                    await fetch(urlUbicacion, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        },
                        credentials: 'same-origin',
                        body: JSON.stringify({ lat: this.lat, lng: this.lng, conductor_id: conductorId }),
                    });
                } catch (e) { /* reintenta en el próximo tick */ }
            },

            empujarMapa() {
                if (this.lat == null || this.lng == null) return;
                if (typeof window.actualizarMapaViaje === 'function') {
                    window.actualizarMapaViaje(this.lat, this.lng, this.estado);
                }
            },

            async cambiarEstado(nuevo) {
                this.enviando = true;
                try {
                    const r = await fetch(urlEstado, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        },
                        credentials: 'same-origin',
                        body: JSON.stringify({ estado: nuevo, conductor_id: conductorId }),
                    });
                    const json = await r.json();
                    if (json.ok) {
                        this.estado = nuevo;
                        this.empujarMapa();
                        this.mostrar('¡Listo! Estado actualizado y notificado al pasajero.', true);
                        if (nuevo === 'completado' || nuevo === 'cancelado') {
                            clearInterval(this.gpsTimer);
                            setTimeout(() => window.location = '/conductor/radar?conductor_id=' + conductorId, 1200);
                        }
                    } else {
                        this.mostrar(json.mensaje || 'No se pudo cambiar el estado.', false);
                    }
                } catch (e) {
                    this.mostrar('Error de conexión. Intenta de nuevo.', false);
                } finally {
                    this.enviando = false;
                }
            },

            mostrar(msg, ok) {
                this.aviso = msg;
                this.avisoOk = ok;
                setTimeout(() => { this.aviso = ''; }, 5000);
            },
        };
    }
</script>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>initViajeMapa();</script>
@endsection
