@extends('layouts.pwa-mobile')

@section('titulo', 'Viaje en Curso - Taxi KALUZ')
@section('header_titulo', 'Viaje en curso')
@section('header_sub', $conductor->placa . ' • ' . $conductor->nombre)

@section('head')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
@endsection

@section('content')
<div
    x-data="viajeActivo('{{ route('despacho.api.viaje.estado', $viajeActivo->id) }}', '{{ route('despacho.api.ubicacion') }}', {{ $conductor->lat_actual ?? 'null' }}, {{ $conductor->lng_actual ?? 'null' }})"
    x-init="iniciarGps()"
    x-cloak
    class="space-y-4"
>
    {{-- Aviso de resultado de acciones --}}
    <div x-show="aviso" x-transition.opacity
        class="rounded-2xl px-4 py-3 text-sm font-body border"
        :class="avisoOk ? 'bg-emerald-100 text-emerald-800 border-emerald-300' : 'bg-red-100 text-red-800 border-red-300'">
        <span x-text="aviso"></span>
    </div>

    {{-- Datos del viaje --}}
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-4 space-y-3">
        <div class="flex justify-between items-start">
            <div class="min-w-0">
                <span class="text-[10px] font-bold uppercase tracking-wide px-2 py-0.5 rounded-full border"
                    :class="estado === 'en_ruta'
                        ? 'bg-amber-100 text-amber-800 border-amber-300'
                        : 'bg-emerald-100 text-emerald-800 border-emerald-300'">
                    <span x-text="estado === 'en_ruta' ? 'EN RUTA' : 'ASIGNADO'"></span>
                </span>
                <h3 class="font-headline font-bold text-base mt-2 leading-snug">
                    {{ $viajeActivo->origen_texto }}
                    <span class="text-[#E31B23]">→</span>
                    {{ $viajeActivo->destino_texto }}
                </h3>
                <p class="text-xs text-slate-500 mt-1">{{ ucfirst($viajeActivo->tipo_servicio) }}</p>
            </div>
            <div class="text-right shrink-0">
                <span class="text-[10px] text-slate-400 uppercase font-bold block">Final</span>
                <span class="font-headline font-black text-2xl text-[#0B1B3D]">S/ {{ number_format($viajeActivo->precio_final ?? $viajeActivo->oferta_cliente, 2) }}</span>
            </div>
        </div>
    </div>

    {{-- Mapa libre (Leaflet + OpenStreetMap): recojo (asignado) o destino (en ruta) --}}
    <div class="relative z-0 bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div id="mapa-viaje" class="w-full h-60 bg-[#F4F5F7]"></div>

        <div id="mapa-eta"
            class="absolute left-3 bottom-3 z-[500] bg-[#0B1B3D]/90 text-white text-[11px] font-bold px-3 py-1.5 rounded-full shadow-md">
            Calculando ruta…
        </div>

        <span class="absolute top-3 right-3 z-[500] bg-white/90 text-[10px] font-bold uppercase tracking-wide text-[#0B1B3D] px-2 py-1 rounded-full shadow-sm">
            En vivo
        </span>

        <a href="https://www.google.com/maps/dir/?api=1&destination={{ $viajeActivo->estado === 'en_ruta' ? $ubicacionDestino['lat'].','.$ubicacionDestino['lng'] : $ubicacionOrigen['lat'].','.$ubicacionOrigen['lng'] }}"
            target="_blank" rel="noopener"
            class="absolute right-3 bottom-3 z-[500] bg-white/95 text-[#0B1B3D] text-[10px] font-bold px-2.5 py-1.5 rounded-full shadow-sm border border-slate-200">
            Google Maps ↗
        </a>
    </div>

    {{-- Datos del cliente --}}
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-4">
        <h4 class="font-headline font-bold text-sm mb-2">Pasajero</h4>
        <div class="flex items-center justify-between gap-3">
            <div class="min-w-0">
                <p class="font-bold text-sm truncate">{{ $viajeActivo->cliente_nombre ?: 'Pasajero' }}</p>
                <p class="text-xs text-slate-500">{{ $viajeActivo->cliente_telefono }}</p>
            </div>
            <a href="https://wa.me/{{ preg_replace('/\D/', '', $viajeActivo->cliente_telefono) }}"
                class="shrink-0 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl px-4 py-2.5 text-xs shadow-sm">
                WhatsApp
            </a>
        </div>
    </div>

    {{-- Botonera de estados --}}
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-4 space-y-2.5">
        <button
            x-show="estado === 'asignado'"
            type="button"
            @click="cambiarEstado('en_ruta')"
            :disabled="enviando"
            class="w-full bg-[#E31B23] hover:bg-red-700 disabled:opacity-60 text-white font-bold rounded-xl shadow-md py-3.5 text-sm"
        >
            🚕 Voy en camino
        </button>

        <button
            x-show="estado === 'en_ruta'"
            type="button"
            @click="cambiarEstado('completado')"
            :disabled="enviando"
            class="w-full bg-emerald-600 hover:bg-emerald-700 disabled:opacity-60 text-white font-bold rounded-xl shadow-md py-3.5 text-sm"
        >
            ✔ Finalizar viaje
        </button>

        <button
            x-show="estado === 'asignado' || estado === 'en_ruta'"
            type="button"
            @click="if (confirm('¿Cancelar este viaje? El pasajero será notificado.')) cambiarEstado('cancelado')"
            :disabled="enviando"
            class="w-full bg-white border border-red-300 text-red-600 font-bold rounded-xl py-3 text-sm hover:bg-red-50"
        >
            ✕ Cancelar viaje
        </button>

        <p class="text-[11px] text-slate-400 text-center pt-1">
            Tu GPS se comparte cada 5 s mientras el viaje esté activo.
        </p>
    </div>

    <a href="{{ route('despacho.pwa') }}"
        class="block text-center text-sm font-bold text-[#0B1B3D] py-2">
        ← Volver al radar
    </a>
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

    function viajeActivo(urlEstado, urlUbicacion, latDefault, lngDefault) {
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
                        body: JSON.stringify({ lat: this.lat, lng: this.lng }),
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
                        body: JSON.stringify({ estado: nuevo }),
                    });
                    const json = await r.json();
                    if (json.ok) {
                        this.estado = nuevo;
                        this.empujarMapa();
                        this.mostrar('¡Listo! Estado actualizado.', true);
                        if (nuevo === 'completado' || nuevo === 'cancelado') {
                            clearInterval(this.gpsTimer);
                            setTimeout(() => window.location = '/conductor/radar', 1200);
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
