@extends('layouts.pwa-mobile')

@section('titulo', 'Viaje en Curso - Taxi KALUZ')
@section('header_titulo', 'Viaje en curso')
@section('header_sub', $conductor->placa . ' • ' . $conductor->nombre)

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
                    // Simulación GPS en Huancayo: base -12.0651, -75.2049 con micro-drift.
                    this.lat = -12.0651 + (Math.random() - 0.5) * 0.004;
                    this.lng = -75.2049 + (Math.random() - 0.5) * 0.004;
                    this.enviarUbicacion();
                };

                if (!navigator.geolocation) { fallback(); return; }

                navigator.geolocation.getCurrentPosition(
                    pos => {
                        this.lat = pos.coords.latitude;
                        this.lng = pos.coords.longitude;
                        this.enviarUbicacion();
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
@endsection
