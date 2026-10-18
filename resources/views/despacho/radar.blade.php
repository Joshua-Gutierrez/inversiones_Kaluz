@extends('layouts.pwa-mobile')

@section('titulo', 'Radar de Viajes - Taxi KALUZ')
@section('tema', 'oscuro')

@section('header_centro')
    <span class="inline-flex items-center gap-1.5 text-[11px] font-headline font-bold uppercase tracking-wide px-3 py-1 rounded-full bg-emerald-500 text-white shadow-[0_0_16px_rgba(16,185,129,0.55)]">
        <span class="w-2 h-2 rounded-full bg-white animate-pulse"></span>
        HABILITADO
    </span>
@endsection

@section('header_derecha')
    <div class="flex items-center gap-2">
        <div class="w-9 h-9 rounded-full bg-gradient-to-br from-blue-500 to-indigo-700 border-2 border-white/30 flex items-center justify-center font-headline font-bold text-xs text-white shrink-0 shadow">
            {{ mb_strtoupper(mb_substr($conductor->nombre, 0, 1)) }}
        </div>
        <div class="leading-tight text-right hidden xs:block sm:block">
            <p class="font-headline font-bold text-white text-xs truncate max-w-[115px]">{{ $conductor->nombre }}</p>
            <p class="text-[11px] text-slate-300 font-medium">{{ $conductor->placa }}</p>
        </div>
    </div>
@endsection

@section('content')
<div
    x-data="radarConductor('{{ route('despacho.api.solicitudes', ['conductor_id' => $conductor->id]) }}', '{{ csrf_token() }}', {{ (int) $conductor->id }})"
    x-init="iniciar()"
    class="space-y-4"
    x-on:visibilitychange.window="if (document.hidden) { detener(); } else { iniciar(); }"
>
    {{-- Encabezado principal estilo Imagen 1 --}}
    <div class="pt-1 flex items-start justify-between gap-2">
        <div>
            <h1 class="font-headline font-extrabold text-2xl text-white tracking-tight">Radar de Viajes</h1>
            <p class="text-xs text-slate-400 mt-0.5">Solicitudes de pasajeros en Huancayo en vivo</p>
        </div>
        <a href="{{ route('despacho.demo.crear-solicitud', ['conductor_id' => $conductor->id]) }}"
           class="shrink-0 inline-flex items-center gap-1.5 bg-[#162647] hover:bg-[#1E325C] text-slate-200 border border-white/15 rounded-xl px-3 py-2 text-xs font-semibold transition"
           title="Simular nueva solicitud de WhatsApp">
            <i class="ri-add-circle-line text-sm text-emerald-400"></i>
            <span>Crear viaje demo</span>
        </a>
    </div>

    {{-- Toggle Recibir viajes + Estado de sincronización --}}
    <div class="flex items-center justify-between py-1">
        <div class="flex items-center gap-3">
            <button
                type="button"
                @click="enLinea = !enLinea; if (enLinea) iniciar(); else detener();"
                class="relative w-12 h-7 rounded-full transition-colors shrink-0 border border-white/15"
                :class="enLinea ? 'bg-emerald-500' : 'bg-slate-600'"
                role="switch"
                :aria-checked="enLinea"
                aria-label="Recibir viajes"
            >
                <span class="absolute top-0.5 w-5 h-5 rounded-full bg-white shadow transition-all"
                    :class="enLinea ? 'left-6' : 'left-0.5'"></span>
            </button>
            <span class="text-sm font-semibold text-white">Recibir viajes</span>
        </div>

        <div class="flex items-center gap-1.5 text-[11px] text-slate-400">
            <span class="w-2 h-2 rounded-full" :class="conectado ? 'bg-emerald-400 animate-pulse' : 'bg-red-400'"></span>
            <span x-text="conectado ? 'En vivo (3s)' : 'Reconectando...'"></span>
        </div>
    </div>

    @if (session('demo_ok'))
        <div class="bg-emerald-500/15 text-emerald-300 border border-emerald-500/30 rounded-2xl px-4 py-3 text-xs font-medium flex items-center gap-2">
            <i class="ri-checkbox-circle-fill text-base text-emerald-400"></i>
            <span>{{ session('demo_ok') }}</span>
        </div>
    @endif

    {{-- Aviso suave --}}
    <div x-show="aviso" x-transition.opacity
        class="bg-amber-500/15 text-amber-200 border border-amber-500/30 rounded-2xl px-4 py-3 text-xs font-medium flex items-center justify-between gap-2">
        <div class="flex items-center gap-2">
            <i class="ri-information-fill text-base text-amber-400"></i>
            <span x-text="aviso"></span>
        </div>
        <button type="button" @click="aviso = ''" class="text-amber-300 hover:text-white">
            <i class="ri-close-line text-base"></i>
        </button>
    </div>

    {{-- Lista de solicitudes en vivo (Tarjetas Oscuras idénticas a Imagen 1) --}}
    <template x-if="!viajeActivo">
        <div class="space-y-3.5">
            <template x-for="s in solicitudes" :key="s.id">
                <div
                    x-transition
                    class="bg-[#111E3B] rounded-2xl border border-white/10 p-4 space-y-3 shadow-lg"
                >
                    {{-- Badge superior Solicitud de WhatsApp + Distancia Haversine --}}
                    <div class="flex items-center justify-between gap-2">
                        <div class="flex items-center gap-1.5 flex-wrap">
                            <span class="inline-flex items-center gap-1.5 bg-slate-200/95 text-[#0A1329] text-xs font-bold px-3 py-1 rounded-full shadow-xs">
                                <i class="ri-whatsapp-line text-sm text-[#0A1329]" x-show="s.tipo_servicio !== 'turismo'"></i>
                                <i class="ri-bus-2-line text-sm text-[#0A1329]" x-show="s.tipo_servicio === 'turismo'"></i>
                                <span x-text="s.tipo_servicio === 'turismo' ? 'Tour Turístico KALUZ' : 'Solicitud de WhatsApp'"></span>
                            </span>
                            <span x-show="s.distancia_km" class="inline-flex items-center gap-1 bg-emerald-500/15 text-emerald-300 border border-emerald-500/30 text-[11px] font-semibold px-2.5 py-0.5 rounded-full">
                                <i class="ri-pin-distance-line text-xs"></i>
                                <span>A <span x-text="Number(s.distancia_km).toFixed(1)"></span> km</span>
                            </span>
                        </div>

                        <span class="text-[11px] text-slate-400 font-medium flex items-center gap-1 shrink-0">
                            <i class="ri-time-line text-xs"></i>
                            <span x-text="minutos(s.hace_segundos)"></span>
                        </span>
                    </div>

                    {{-- Origen y Destino con iconos vectoriales --}}
                    <div class="space-y-1.5 pt-0.5 text-sm">
                        <div class="flex items-start gap-2 text-slate-100">
                            <i class="ri-map-pin-2-fill text-[#E31B23] text-base leading-snug shrink-0"></i>
                            <p class="leading-snug">
                                <span class="text-slate-300 font-medium">Origen:</span>
                                <span class="font-semibold text-white" x-text="s.origen_texto"></span>
                            </p>
                        </div>

                        <div class="flex items-start gap-2 text-slate-100">
                            <i class="ri-flag-2-fill text-slate-300 text-base leading-snug shrink-0"></i>
                            <p class="leading-snug">
                                <span class="text-slate-300 font-medium">Destino:</span>
                                <span class="font-semibold text-white" x-text="s.destino_texto"></span>
                            </p>
                        </div>
                    </div>

                    {{-- Precio grande en blanco + Datos del pasajero --}}
                    <div class="pt-0.5 flex items-end justify-between gap-2">
                        <div>
                            <span class="text-[10px] uppercase tracking-wider text-emerald-400 font-bold block">Oferta del pasajero</span>
                            <p class="font-headline font-black text-3xl text-white tracking-tight">
                                S/ <span x-text="Number(s.oferta_cliente).toFixed(2)"></span>
                            </p>
                        </div>
                        <div class="text-right">
                            <span class="text-xs font-semibold text-slate-200 block" x-text="s.cliente_nombre || 'Pasajero WhatsApp'"></span>
                            <span class="text-[11px] text-slate-400 block" x-text="s.cliente_telefono || ''"></span>
                        </div>
                    </div>

                    {{-- Aviso si este conductor ya envió una contraoferta a esta solicitud --}}
                    <template x-if="s.mi_oferta">
                        <div class="bg-amber-500/15 border border-amber-500/30 rounded-xl px-3 py-2 text-xs text-amber-200 flex items-center justify-between gap-2">
                            <span class="flex items-center gap-1.5 font-medium">
                                <i class="ri-Hourglass-fill text-amber-400 animate-pulse"></i>
                                <span>Tu oferta: <strong class="text-white">S/ <span x-text="Number(s.mi_oferta).toFixed(2)"></span></strong> (esperando respuesta en WhatsApp)</span>
                            </span>
                        </div>
                    </template>

                    {{-- Botones de acción: Contraoferta (izquierda) y Aceptar Viaje (derecha) --}}
                    <div class="grid grid-cols-2 gap-2.5 pt-1">
                        <button
                            type="button"
                            @click="abrirContraoferta(s)"
                            class="bg-[#0B152B] hover:bg-[#16274C] border border-slate-400/50 text-white font-semibold rounded-xl py-2.5 px-3 text-xs transition flex items-center justify-center gap-1"
                        >
                            <i class="ri-scales-3-line text-sm text-amber-400"></i>
                            <span x-text="s.mi_oferta ? 'Cambiar oferta' : 'Contraofertar S/ ' + (Number(s.oferta_cliente) + 1).toFixed(2)"></span>
                        </button>

                        <button
                            type="button"
                            @click="aceptar(s)"
                            :disabled="enviando === s.id"
                            class="bg-[#D91B23] hover:bg-[#B9151C] disabled:opacity-60 text-white font-bold rounded-xl shadow-md py-2.5 px-3 text-xs transition flex items-center justify-center gap-1.5"
                        >
                            <i class="ri-taxi-fill text-base" x-show="enviando !== s.id"></i>
                            <span x-show="enviando !== s.id">Aceptar S/ <span x-text="Number(s.oferta_cliente).toFixed(2)"></span></span>
                            <span x-show="enviando === s.id">Asignando...</span>
                        </button>
                    </div>
                </div>
            </template>

            {{-- Estado vacío --}}
            <div x-show="solicitudes.length === 0 && conectado"
                class="bg-[#111E3B] rounded-2xl border border-white/10 p-8 text-center space-y-2.5">
                <div class="w-12 h-12 rounded-2xl bg-white/5 border border-white/10 flex items-center justify-center mx-auto text-emerald-400">
                    <i class="ri-radar-line text-2xl animate-spin" style="animation-duration: 5s;"></i>
                </div>
                <p class="font-headline font-bold text-sm text-white">Radar activo esperando pasajeros</p>
                <p class="text-xs text-slate-400">Cuando un pasajero pida taxi por WhatsApp en Huancayo aparecerá aquí al instante.</p>
                <div class="pt-2 flex items-center justify-center">
                    <span class="inline-flex items-center gap-2 bg-[#162647] border border-emerald-500/30 text-emerald-300 text-xs font-semibold px-4 py-2 rounded-full shadow-inner">
                        <span class="relative flex h-2.5 w-2.5">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-emerald-500"></span>
                        </span>
                        <span>Esperando viajes en tiempo real...</span>
                    </span>
                </div>
            </div>

            <div x-show="solicitudes.length === 0 && !conectado"
                class="bg-[#111E3B] rounded-2xl border border-red-500/30 p-8 text-center space-y-2">
                <i class="ri-wifi-off-line text-2xl text-red-400"></i>
                <p class="font-headline font-bold text-sm text-red-400">Reconectando al radar...</p>
                <p class="text-xs text-slate-400">Verificando conexión con la central de despacho.</p>
            </div>
        </div>
    </template>

    {{-- Banner cuando ya tiene viaje activo --}}
    <template x-if="viajeActivo">
        <div class="bg-[#111E3B] rounded-2xl border border-emerald-500/40 shadow-lg p-5 text-white space-y-3">
            <div class="flex items-center justify-between">
                <span class="inline-flex items-center gap-1.5 text-[10px] font-bold uppercase tracking-wide bg-emerald-500 text-white px-2.5 py-1 rounded-full">
                    <i class="ri-navigation-fill"></i> Viaje en curso
                </span>
                <span class="font-headline font-black text-xl text-white">
                    S/ <span x-text="Number(viajeActivo.precio_final || viajeActivo.oferta_cliente).toFixed(2)"></span>
                </span>
            </div>

            <div class="space-y-1 text-sm">
                <p class="flex items-center gap-2 text-slate-200">
                    <i class="ri-map-pin-2-fill text-[#E31B23]"></i>
                    <span x-text="viajeActivo.origen_texto"></span>
                </p>
                <p class="flex items-center gap-2 text-slate-200">
                    <i class="ri-flag-2-fill text-emerald-400"></i>
                    <span x-text="viajeActivo.destino_texto"></span>
                </p>
            </div>

            <a href="{{ route('despacho.viaje', ['conductor_id' => $conductor->id]) }}"
                class="flex items-center justify-center gap-2 bg-[#E31B23] hover:bg-red-700 text-white font-bold rounded-xl shadow-md py-3 text-sm transition">
                <i class="ri-road-map-line text-base"></i>
                <span>Abrir Mapa GPS del Viaje</span>
            </a>
        </div>
    </template>

    {{-- Modal de Contraoferta --}}
    <div x-show="modal" class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-4" style="display:none">
        <div class="absolute inset-0 bg-black/75 backdrop-blur-xs" @click="modal = false"></div>
        <div x-transition class="relative bg-[#111E3B] border border-white/15 text-white rounded-2xl shadow-2xl w-full max-w-md p-5 space-y-4">
            <div class="flex items-start justify-between">
                <div>
                    <h3 class="font-headline font-bold text-lg text-white">Enviar Contraoferta</h3>
                    <p class="text-xs text-slate-400">
                        Oferta inicial del pasajero: S/ <span x-text="objetivo ? objetivo.oferta_cliente.toFixed(2) : ''"></span>
                    </p>
                </div>
                <button type="button" @click="modal = false" class="text-slate-400 hover:text-white p-1">
                    <i class="ri-close-line text-xl"></i>
                </button>
            </div>

            <div class="text-center py-2 bg-[#081124] rounded-xl border border-white/10">
                <span class="text-[11px] uppercase tracking-wider text-slate-400 block">Tu propuesta</span>
                <span class="font-headline font-black text-4xl text-white">S/ <span x-text="monto.toFixed(2)"></span></span>
            </div>

            <div class="grid grid-cols-3 gap-2">
                <button type="button" @click="monto = Math.max(1, monto - 0.5)"
                    class="bg-[#081124] border border-white/15 rounded-xl py-2.5 font-bold text-xs text-slate-200 hover:bg-white/10">- S/ 0.50</button>
                <button type="button" @click="monto += 1"
                    class="bg-[#081124] border border-white/15 rounded-xl py-2.5 font-bold text-xs text-slate-200 hover:bg-white/10">+ S/ 1.00</button>
                <button type="button" @click="monto += 2"
                    class="bg-[#081124] border border-white/15 rounded-xl py-2.5 font-bold text-xs text-slate-200 hover:bg-white/10">+ S/ 2.00</button>
            </div>

            <div>
                <p class="text-xs font-semibold text-slate-300 mb-2">Tiempo estimado de llegada</p>
                <div class="grid grid-cols-4 gap-2">
                    <template x-for="t in [3, 5, 8, 10]" :key="t">
                        <button type="button" @click="tiempo = t"
                            class="rounded-xl py-2 text-xs font-bold border transition"
                            :class="tiempo === t
                                ? 'bg-[#E31B23] text-white border-[#E31B23]'
                                : 'bg-[#081124] border-white/15 text-slate-300 hover:bg-white/10'">
                            <span x-text="t"></span> min
                        </button>
                    </template>
                </div>
            </div>

            <button type="button" @click="enviarContraoferta()"
                :disabled="enviando !== null"
                class="w-full bg-[#E31B23] hover:bg-red-700 disabled:opacity-60 text-white font-bold rounded-xl shadow-md py-3.5 text-sm flex items-center justify-center gap-2">
                <i class="ri-send-plane-fill"></i>
                <span>Confirmar Contraoferta</span>
            </button>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    function radarConductor(urlSolicitudes, csrfToken, conductorId) {
        return {
            solicitudes: [],
            viajeActivo: {!! json_encode(
                $viajeActivo
                    ? [
                        'id' => $viajeActivo->id,
                        'origen_texto' => $viajeActivo->origen_texto,
                        'destino_texto' => $viajeActivo->destino_texto,
                        'precio_final' => $viajeActivo->precio_final,
                        'oferta_cliente' => $viajeActivo->oferta_cliente,
                    ]
                    : null,
                JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT
            ) !!},
            enLinea: true,
            conectado: false,
            aviso: '',
            enviando: null,
            modal: false,
            objetivo: null,
            monto: 0,
            tiempo: 5,
            timer: null,
            idsPrevios: new Set(),

            iniciar() {
                if (this.timer) return;
                this.consultar();
                this.timer = setInterval(() => this.consultar(), 3000);
            },

            detener() {
                clearInterval(this.timer);
                this.timer = null;
            },

            async consultar() {
                if (!this.enLinea) return;
                try {
                    const url = new URL(urlSolicitudes, window.location.origin);
                    url.searchParams.set('en_linea', this.enLinea ? '1' : '0');
                    url.searchParams.set('conductor_id', conductorId);
                    const r = await fetch(url, { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' });
                    if (r.status === 403) {
                        window.location = '/conductor/bloqueado?conductor_id=' + conductorId;
                        return;
                    }
                    if (!r.ok) throw new Error('HTTP ' + r.status);
                    const json = await r.json();
                    if (!json.ok) throw new Error(json.mensaje);

                    this.conectado = true;
                    const nuevas = json.data.solicitudes || [];

                    if (json.data.viaje_activo && !this.viajeActivo) {
                        this.viajeActivo = json.data.viaje_activo;
                        this.alertar(2);
                        setTimeout(() => window.location = '/conductor/viaje?conductor_id=' + conductorId, 600);
                        return;
                    }

                    const idsNuevos = new Set(nuevas.map(s => s.id));
                    if (this.idsPrevios.size > 0) {
                        for (const id of this.idsPrevios) {
                            if (!idsNuevos.has(id)) {
                                this.aviso = 'Este viaje ya fue tomado por otro conductor.';
                                setTimeout(() => { this.aviso = ''; }, 5000);
                            }
                        }
                    }

                    const hayNuevo = nuevas.some(s => !this.idsPrevios.has(s.id));
                    if (hayNuevo && this.idsPrevios.size > 0) this.alertar(1);
                    this.idsPrevios = idsNuevos;

                    this.solicitudes = nuevas;
                } catch (e) {
                    this.conectado = false;
                }
            },

            alertar(repeticiones) {
                if (navigator.vibrate) navigator.vibrate(Array(repeticiones).fill(200));
                try {
                    const ctx = new (window.AudioContext || window.webkitAudioContext)();
                    for (let i = 0; i < repeticiones; i++) {
                        const osc = ctx.createOscillator();
                        const gain = ctx.createGain();
                        osc.connect(gain); gain.connect(ctx.destination);
                        osc.frequency.value = 880;
                        gain.gain.setValueAtTime(0.15, ctx.currentTime + i * 0.35);
                        gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + i * 0.35 + 0.25);
                        osc.start(ctx.currentTime + i * 0.35);
                        osc.stop(ctx.currentTime + i * 0.35 + 0.25);
                    }
                } catch (e) {}
            },

            minutos(seg) {
                if (seg == null) return 'Ahora';
                if (seg < 60) return 'Hace instantes';
                return 'Hace ' + Math.floor(seg / 60) + ' min';
            },

            async aceptar(s) {
                this.enviando = s.id;
                try {
                    const r = await fetch(`/conductor/api/solicitudes/${s.id}/aceptar?conductor_id=${conductorId}`, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                        },
                        credentials: 'same-origin',
                        body: JSON.stringify({ conductor_id: conductorId }),
                    });
                    const json = await r.json();
                    if (json.ok) {
                        this.alertar(2);
                        window.location = '/conductor/viaje?conductor_id=' + conductorId;
                        return;
                    }
                    this.aviso = json.mensaje || 'No se pudo aceptar el viaje.';
                    setTimeout(() => { this.aviso = ''; }, 5000);
                    this.consultar();
                } catch (e) {
                    this.aviso = 'Error de conexión. Intenta de nuevo.';
                } finally {
                    this.enviando = null;
                }
            },

            abrirContraoferta(s) {
                this.objetivo = s;
                this.monto = Math.round((s.oferta_cliente + 1) * 2) / 2;
                this.tiempo = 5;
                this.modal = true;
            },

            async enviarContraoferta() {
                if (!this.objetivo || this.monto <= 0) return;
                this.enviando = this.objetivo.id;
                try {
                    const r = await fetch(`/conductor/api/solicitudes/${this.objetivo.id}/contraoferta?conductor_id=${conductorId}`, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                        },
                        credentials: 'same-origin',
                        body: JSON.stringify({ monto: this.monto, tiempo_llegada_min: this.tiempo, conductor_id: conductorId }),
                    });
                    const json = await r.json();
                    this.modal = false;
                    if (json.ok) {
                        this.aviso = 'Contraoferta de S/ ' + this.monto.toFixed(2) + ' enviada al pasajero.';
                        setTimeout(() => { this.aviso = ''; }, 5000);
                        this.consultar();
                    } else {
                        this.aviso = json.mensaje || 'No se pudo enviar la contraoferta.';
                        setTimeout(() => { this.aviso = ''; }, 5000);
                        this.consultar();
                    }
                } catch (e) {
                    this.aviso = 'Error de conexión. Intenta de nuevo.';
                } finally {
                    this.enviando = null;
                }
            },
        };
    }
</script>
@endsection
