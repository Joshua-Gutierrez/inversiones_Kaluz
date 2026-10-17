@extends('layouts.pwa-mobile')

@section('titulo', 'Radar de Viajes - Taxi KALUZ')
@section('header_titulo', $conductor->nombre)
@section('header_sub', $conductor->placa . ' • ' . $conductor->modelo_vehiculo)

@section('header_derecha')
    <span class="text-[10px] font-bold px-2.5 py-1 rounded-lg border
        {{ $conductor->estado_comision === 'al_dia'
            ? 'bg-emerald-100 text-emerald-800 border-emerald-300'
            : 'bg-amber-100 text-amber-800 border-amber-300' }}">
        {{ $conductor->estado_comision === 'al_dia' ? 'AL DÍA' : 'REVISAR' }}
    </span>
@endsection

@section('content')
<div
    x-data="radarConductor('{{ route('despacho.api.solicitudes') }}', '{{ csrf_token() }}')"
    x-init="iniciar()"
    x-cloak
    class="space-y-4"

    {{-- Polling solo cuando hay red y la pestaña está visible --}}
    x-on:visibilitychange.window="if (document.hidden) { detener(); } else { iniciar(); }"
>

    {{-- Aviso suave: viaje tomado por otro conductor --}}
    <div x-show="aviso" x-transition.opacity
        class="bg-amber-100 text-amber-800 border border-amber-300 rounded-2xl px-4 py-3 text-sm font-body flex items-center justify-between gap-2">
        <span x-text="aviso"></span>
        <button type="button" @click="aviso = ''" class="font-bold shrink-0">✕</button>
    </div>

    {{-- Interruptor En línea / Fuera de línea --}}
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 px-4 py-3 flex items-center justify-between">
        <div>
            <p class="font-headline font-bold text-sm">Estado del radar</p>
            <p class="text-[11px] text-slate-500" x-text="enLinea ? 'Recibiendo viajes en vivo...' : 'Pausado. No recibirás pedidos.'"></p>
        </div>
        <button
            type="button"
            @click="enLinea = !enLinea; if (enLinea) iniciar(); else detener();"
            class="relative w-14 h-8 rounded-full transition-colors shrink-0"
            :class="enLinea ? 'bg-emerald-500' : 'bg-slate-300'"
            role="switch"
            :aria-checked="enLinea"
            aria-label="En línea o fuera de línea"
        >
            <span class="absolute top-1 w-6 h-6 rounded-full bg-white shadow transition-all"
                :class="enLinea ? 'left-7' : 'left-1'"></span>
        </button>
    </div>

    {{-- Indicador de conexión del polling --}}
    <div class="flex items-center gap-2 text-[11px] text-slate-400 px-1">
        <span class="w-2 h-2 rounded-full" :class="conectado ? 'bg-emerald-400 animate-pulse' : 'bg-red-400'"></span>
        <span x-text="conectado ? 'Actualizando cada 3 s' : 'Sin conexión, reintentando...'"></span>
    </div>

    {{-- Lista de solicitudes en vivo --}}
    <template x-if="!viajeActivo">
        <div class="space-y-3">

            <template x-for="s in solicitudes" :key="s.id">
                <div
                    x-transition
                    class="bg-white rounded-2xl shadow-sm border border-slate-200 p-4 space-y-3"
                >
                    <div class="flex justify-between items-start gap-3">
                        <div class="min-w-0">
                            <span class="text-[10px] font-bold uppercase tracking-wide px-2 py-0.5 rounded-full border"
                                :class="s.estado === 'en_subasta'
                                    ? 'bg-amber-100 text-amber-800 border-amber-300'
                                    : 'bg-emerald-100 text-emerald-800 border-emerald-300'">
                                <span x-text="s.tipo_servicio === 'turismo' ? 'TURISMO' : (s.estado === 'en_subasta' ? 'EN SUBASTA' : 'NUEVO')"></span>
                            </span>
                            <h3 class="font-headline font-bold text-sm mt-1.5 leading-snug">
                                <span x-text="s.origen_texto"></span>
                                <span class="text-[#E31B23]">→</span>
                                <span x-text="s.destino_texto"></span>
                            </h3>
                            <p class="text-[11px] text-slate-500 mt-0.5">
                                <span x-text="s.cliente_nombre || 'Pasajero'"></span> • hace <span x-text="minutos(s.hace_segundos)"></span>
                            </p>
                        </div>
                        <div class="text-right shrink-0">
                            <span class="text-[10px] text-slate-400 uppercase font-bold block">Oferta</span>
                            <span class="font-headline font-black text-xl text-[#0B1B3D]">S/ <span x-text="s.oferta_cliente.toFixed(2)"></span></span>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-2">
                        <button
                            type="button"
                            @click="aceptar(s)"
                            :disabled="enviando === s.id"
                            class="bg-[#E31B23] hover:bg-red-700 disabled:opacity-60 text-white font-bold rounded-xl shadow-md py-3 text-sm"
                        >
                            <span x-show="enviando !== s.id">Aceptar S/ <span x-text="s.oferta_cliente.toFixed(2)"></span></span>
                            <span x-show="enviando === s.id">Enviando...</span>
                        </button>
                        <button
                            type="button"
                            @click="abrirContraoferta(s)"
                            class="bg-white border border-[#0B1B3D] text-[#0B1B3D] font-bold rounded-xl py-3 text-sm hover:bg-[#F4F5F7]"
                        >
                            Ofrecer más
                        </button>
                    </div>
                </div>
            </template>

            {{-- Estado vacío --}}
            <div x-show="solicitudes.length === 0 && conectado"
                class="bg-white rounded-2xl shadow-sm border border-slate-200 p-8 text-center space-y-2">
                <span class="text-3xl block">📡</span>
                <p class="font-headline font-bold text-sm">Radar activo, sin pedidos ahora</p>
                <p class="text-xs text-slate-500">Te avisaremos con vibración apenas alguien pida taxi.</p>
            </div>

            <div x-show="solicitudes.length === 0 && !conectado"
                class="bg-white rounded-2xl shadow-sm border border-slate-200 p-8 text-center">
                <p class="font-headline font-bold text-sm text-red-600">Sin conexión al radar</p>
                <p class="text-xs text-slate-500 mt-1">Reintentando automáticamente...</p>
            </div>
        </div>
    </template>

    {{-- Ya tiene viaje activo: botonera para ir a la gestión del viaje --}}
    <template x-if="viajeActivo">
        <div class="bg-[#0B1B3D] rounded-2xl shadow-md p-5 text-white space-y-3">
            <span class="text-[10px] font-bold uppercase tracking-wide bg-[#E31B23] px-2 py-0.5 rounded-full">Viaje en curso</span>
            <h3 class="font-headline font-bold text-base">
                <span x-text="viajeActivo.origen_texto"></span>
                <span class="text-[#E31B23]">→</span>
                <span x-text="viajeActivo.destino_texto"></span>
            </h3>
            <p class="text-sm text-slate-300">
                Precio final: <strong class="text-white">S/ <span x-text="Number(viajeActivo.precio_final || viajeActivo.oferta_cliente).toFixed(2)"></span></strong>
            </p>
            <a href="{{ route('despacho.viaje') }}"
                class="block text-center bg-[#E31B23] hover:bg-red-700 text-white font-bold rounded-xl shadow-md py-3 text-sm">
                Gestionar viaje →
            </a>
        </div>
    </template>

    {{-- ===== Modal de contraoferta ===== --}}
    <div x-show="modal" class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-4" style="display:none">
        <div class="absolute inset-0 bg-black/60" @click="modal = false"></div>
        <div x-transition class="relative bg-white rounded-2xl shadow-xl w-full max-w-md p-5 space-y-4">
            <div class="flex items-start justify-between">
                <div>
                    <h3 class="font-headline font-bold text-lg">Tu contraoferta</h3>
                    <p class="text-xs text-slate-500">
                        Cliente ofreció S/ <span x-text="objetivo ? objetivo.oferta_cliente.toFixed(2) : ''"></span>
                    </p>
                </div>
                <button type="button" @click="modal = false" class="text-slate-400 text-xl leading-none px-2">✕</button>
            </div>

            {{-- Monto --}}
            <div class="text-center">
                <span class="font-headline font-black text-4xl text-[#0B1B3D]">S/ <span x-text="monto.toFixed(2)"></span></span>
            </div>
            <div class="grid grid-cols-2 gap-2">
                <button type="button" @click="monto = Math.max(1, monto - 0.5)"
                    class="border border-slate-300 rounded-xl py-2.5 font-bold text-sm hover:bg-[#F4F5F7]">− S/ 0.50</button>
                <button type="button" @click="monto += 0.5"
                    class="border border-slate-300 rounded-xl py-2.5 font-bold text-sm hover:bg-[#F4F5F7]">+ S/ 0.50</button>
                <button type="button" @click="monto += 1"
                    class="border border-slate-300 rounded-xl py-2.5 font-bold text-sm hover:bg-[#F4F5F7]">+ S/ 1.00</button>
                <input type="number" x-model.number="monto" min="1" step="0.5"
                    class="border border-slate-300 rounded-xl py-2.5 text-sm text-center focus:outline-none focus:ring-2 focus:ring-[#0B1B3D]"
                    placeholder="Monto">
            </div>

            {{-- Tiempo de llegada --}}
            <div>
                <p class="text-xs font-bold text-slate-600 mb-2">¿En cuántos minutos llegas?</p>
                <div class="grid grid-cols-4 gap-2">
                    <template x-for="t in [3, 5, 8, 10]" :key="t">
                        <button type="button" @click="tiempo = t"
                            class="rounded-xl py-2.5 text-sm font-bold border transition"
                            :class="tiempo === t
                                ? 'bg-[#0B1B3D] text-white border-[#0B1B3D]'
                                : 'border-slate-300 hover:bg-[#F4F5F7]'">
                            <span x-text="t"></span> min
                        </button>
                    </template>
                </div>
            </div>

            <button type="button" @click="enviarContraoferta()"
                :disabled="enviando !== null"
                class="w-full bg-[#E31B23] hover:bg-red-700 disabled:opacity-60 text-white font-bold rounded-xl shadow-md py-3.5 text-sm">
                Enviar contraoferta
            </button>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    function radarConductor(urlSolicitudes, csrfToken) {
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
                    const r = await fetch(url, { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' });
                    if (r.status === 403) {
                        // Candado financiero activo: mandar a la pantalla de bloqueo.
                        window.location = '/conductor/bloqueado';
                        return;
                    }
                    if (!r.ok) throw new Error('HTTP ' + r.status);
                    const json = await r.json();
                    if (!json.ok) throw new Error(json.mensaje);

                    this.conectado = true;
                    const nuevas = json.data.solicitudes || [];

                    // Viaje activo detectado (nos asignaron uno): cambiar de pantalla.
                    if (json.data.viaje_activo && !this.viajeActivo) {
                        this.viajeActivo = json.data.viaje_activo;
                        this.alertar(2);
                        setTimeout(() => window.location = '/conductor/viaje', 600);
                        return;
                    }

                    // Viajes que desaparecieron (alguien más los tomó).
                    const idsNuevos = new Set(nuevas.map(s => s.id));
                    if (this.idsPrevios.size > 0) {
                        for (const id of this.idsPrevios) {
                            if (!idsNuevos.has(id)) {
                                this.aviso = 'Este viaje ya fue tomado por otro conductor.';
                                setTimeout(() => { this.aviso = ''; }, 5000);
                            }
                        }
                    }

                    // Viajes nuevos: vibrar + sonido.
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
                } catch (e) { /* sin audio, no pasa nada */ }
            },

            minutos(seg) {
                if (seg == null) return '';
                if (seg < 60) return '1 min';
                return Math.floor(seg / 60) + ' min';
            },

            async aceptar(s) {
                this.enviando = s.id;
                try {
                    const r = await fetch(`/conductor/api/solicitudes/${s.id}/aceptar`, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                        },
                        credentials: 'same-origin',
                    });
                    const json = await r.json();
                    if (json.ok) {
                        this.alertar(2);
                        window.location = '/conductor/viaje';
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
                    const r = await fetch(`/conductor/api/solicitudes/${this.objetivo.id}/contraoferta`, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                        },
                        credentials: 'same-origin',
                        body: JSON.stringify({ monto: this.monto, tiempo_llegada_min: this.tiempo }),
                    });
                    const json = await r.json();
                    this.modal = false;
                    if (json.ok) {
                        this.aviso = '¡Contraoferta enviada! Espera la respuesta del pasajero.';
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
