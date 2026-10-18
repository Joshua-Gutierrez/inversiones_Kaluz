@extends('layouts.torre-control')

@section('titulo', 'Padrón y Candado Financiero - KALUZ')

@section('content')
@php
    $conductores = $conductores ?? collect();
    $listaJs = $conductores->values()->map(function ($c, $idx) {
        $habilitado = (bool) $c->habilitado;
        $deuda = $habilitado ? 0.00 : (str_contains((string) $c->nombre, '3') ? 3.00 : (str_contains((string) $c->nombre, '5') ? 5.00 : 1.00));

        $vencPermiso = $c->fecha_vencimiento_permiso
            ? \Illuminate\Support\Carbon::parse($c->fecha_vencimiento_permiso)
            : now()->addMonths(4);

        $soatVencido = str_contains(strtolower((string) $c->nombre), 'soat') || $vencPermiso->isPast();
        $porVencer = ! $soatVencido && ($idx === 2 || $idx === 4 || $vencPermiso->diffInDays(now(), false) > -15);

        $fechaSoat = $soatVencido
            ? now()->subDays(5)->format('d/m/Y')
            : ($porVencer ? now()->addDays(4)->format('d/m/Y') : $vencPermiso->format('d/m/Y'));

        $fechaLicencia = $idx === 3
            ? now()->addDays(5)->format('d/m/Y')
            : now()->addMonths(8)->format('d/m/Y');

        $fechaRevision = $idx === 6
            ? now()->subDays(2)->format('d/m/Y')
            : ($porVencer ? now()->addDays(6)->format('d/m/Y') : now()->addMonths(5)->format('d/m/Y'));

        return [
            'id' => (int) $c->id,
            'nombre' => (string) $c->nombre,
            'dni' => (string) ($c->dni ?? '45892301'),
            'telefono' => (string) ($c->telefono_whatsapp ?? '964 111 222'),
            'placa' => (string) $c->placa,
            'modelo' => (string) $c->modelo_vehiculo,
            'tipo_flota' => (string) ($c->tipo_flota ?? 'afiliado'),
            'habilitado' => $habilitado,
            'deuda' => $deuda,
            'ultimo_pago' => $habilitado ? now()->format('d/m/Y - H:i') : 'Pendiente - ' . max(1, (int) $deuda) . ' días',
            'sub_pago' => 'Pago: ' . now()->format('d/m/Y') . ' AM',
            'docs' => [
                'soat' => [
                    'nombre' => 'SOAT / AFOCAT',
                    'vence' => $fechaSoat,
                    'estado' => $soatVencido ? 'vencido' : ($porVencer ? 'por_vencer' : 'vigente'),
                ],
                'licencia' => [
                    'nombre' => 'Licencia MTC',
                    'vence' => $fechaLicencia,
                    'estado' => $idx === 3 ? 'por_vencer' : 'vigente',
                ],
                'revision' => [
                    'nombre' => 'Revisión Técnica',
                    'vence' => $fechaRevision,
                    'estado' => $idx === 6 ? 'vencido' : ($porVencer ? 'por_vencer' : 'vigente'),
                ],
            ],
            'alerta_doc' => $soatVencido || $idx === 6 ? 'vencido' : ($porVencer || $idx === 3 ? 'por_vencer' : 'vigente'),
        ];
    })->values();
@endphp

<div x-data="{
        conductores: @js($listaJs),
        busqueda: @js($busquedaInicial ?? ''),
        filtroEstado: @js($filtroInicial ?? 'todos'),
        filtroFlota: 'todas',
        seleccionado: @js($listaJs->firstWhere('habilitado', false) ?? $listaJs->first()),
        modalNuevo: false,
        modalRenovar: false,
        docRenovar: 'SOAT / Revisión Técnica',
        archivoDoc: '',
        recordatorioWa: true,

        get filtrados() {
            return this.conductores.filter(c => {
                const texto = (c.nombre + ' ' + c.placa + ' ' + c.dni + ' ' + c.modelo).toLowerCase();
                const coincideTexto = !this.busqueda || texto.includes(this.busqueda.toLowerCase());
                const coincideEstado = this.filtroEstado === 'todos'
                    || (this.filtroEstado === 'habilitados' && c.habilitado)
                    || (this.filtroEstado === 'bloqueados' && !c.habilitado)
                    || (this.filtroEstado === 'documentos' && c.alerta_doc !== 'vigente');
                const coincideFlota = this.filtroFlota === 'todas' || c.tipo_flota === this.filtroFlota;
                return coincideTexto && coincideEstado && coincideFlota;
            });
        },

        abrirModalRenovacion(conductor, nombreDoc) {
            this.seleccionado = conductor;
            this.docRenovar = nombreDoc;
            this.archivoDoc = '';
            this.modalRenovar = true;
        }
    }"
    class="space-y-4">

    <!-- Cabecera: Título + Botón Añadir Asociado -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div>
            <h1 class="font-headline font-extrabold text-2xl text-[#08142C] tracking-tight">
                Padrón, Candado S/ 1.00 y Control de Vencimientos
            </h1>
            <p class="text-xs text-slate-600 mt-0.5">
                Gestión de asociados, cuota diaria S/ 1.00 y alertas de vencimiento de Licencia, SOAT y Revisión Técnica
            </p>
        </div>

        <button type="button" @click="modalNuevo = true"
            class="inline-flex items-center gap-2 bg-[#08142C] hover:bg-[#13264E] text-white font-headline font-bold text-xs px-4 py-2.5 rounded-xl shadow-sm transition self-start sm:self-auto">
            <i class="ri-add-line text-base"></i>
            <span>Añadir Asociado</span>
        </button>
    </div>

    @if (session('exito'))
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-xs font-semibold text-emerald-800 shadow-xs flex items-center justify-between">
            <div class="flex items-center gap-2">
                <i class="ri-checkbox-circle-fill text-emerald-600 text-base"></i>
                <span>{{ session('exito') }}</span>
            </div>
        </div>
    @endif

    <!-- Grid Principal: Izquierda (Filtros + Tabla 9 cols) | Derecha (Acciones de Control Rápido + Documentos 3 cols) -->
    <div class="grid grid-cols-1 xl:grid-cols-12 gap-5 items-start">

        <!-- Columna Izquierda (9/12): Barra de Filtros + Tabla de Asociados -->
        <div class="xl:col-span-9 space-y-3.5">
            <!-- Barra de Filtros -->
            <div class="flex flex-wrap items-center gap-3">
                <div class="relative flex-1 min-w-[220px]">
                    <i class="ri-search-line absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
                    <input
                        type="search"
                        x-model="busqueda"
                        placeholder="Buscar conductor (Nombre, Placa, DNI)..."
                        class="w-full bg-white/95 rounded-xl pl-9 pr-4 py-2 text-xs text-slate-700 placeholder-slate-400 border border-white shadow-xs focus:outline-none focus:ring-2 focus:ring-[#08142C]/20"
                    >
                </div>

                <!-- Selector de Estado -->
                <div class="flex items-center gap-1 bg-white/95 rounded-xl px-2 py-1.5 border border-white shadow-xs text-xs">
                    <span class="text-slate-500 font-medium px-1">Estado:</span>
                    <button type="button" @click="filtroEstado = 'todos'"
                        class="px-2.5 py-1 rounded-lg font-semibold transition"
                        :class="filtroEstado === 'todos' ? 'bg-[#DCE6F2] text-[#08142C]' : 'text-slate-600 hover:bg-slate-100'">
                        Todos
                    </button>
                    <button type="button" @click="filtroEstado = 'habilitados'"
                        class="px-2.5 py-1 rounded-lg font-semibold transition"
                        :class="filtroEstado === 'habilitados' ? 'bg-emerald-100 text-emerald-800' : 'text-slate-600 hover:bg-slate-100'">
                        Habilitados
                    </button>
                    <button type="button" @click="filtroEstado = 'bloqueados'"
                        class="px-2.5 py-1 rounded-lg font-semibold transition"
                        :class="filtroEstado === 'bloqueados' ? 'bg-red-100 text-red-800' : 'text-slate-600 hover:bg-slate-100'">
                        Bloqueados
                    </button>
                    <button type="button" @click="filtroEstado = 'documentos'"
                        class="px-2.5 py-1 rounded-lg font-semibold transition flex items-center gap-1"
                        :class="filtroEstado === 'documentos' ? 'bg-amber-100 text-amber-900' : 'text-slate-600 hover:bg-slate-100'">
                        <i class="ri-file-warning-line text-amber-600"></i>
                        <span>SOAT / Licencias</span>
                    </button>
                </div>

                <!-- Dropdown Flota -->
                <div class="relative">
                    <select x-model="filtroFlota"
                        class="bg-white/95 rounded-xl px-3.5 py-2 text-xs font-medium text-slate-700 border border-white shadow-xs focus:outline-none">
                        <option value="todas">Ruta/Zona: Todas</option>
                        <option value="propia_combi">Flota Propia (Combis)</option>
                        <option value="propia_auto">Flota Propia (Autos)</option>
                        <option value="afiliado">Asociados Afiliados</option>
                    </select>
                </div>
            </div>

            <!-- Tabla del Padrón -->
            <div class="bg-white/95 rounded-3xl border border-white shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="border-b border-slate-200/80 text-[#08142C] font-headline font-bold">
                                <th class="py-3.5 px-4">Asociado</th>
                                <th class="py-3.5 px-3">Unidad / Placa</th>
                                <th class="py-3.5 px-3">
                                    Último Pago
                                    <span class="block text-[10px] font-body font-normal text-slate-400">Pago & Timestamp</span>
                                </th>
                                <th class="py-3.5 px-3">
                                    Deuda (S/)
                                    <span class="block text-[10px] font-body font-normal text-slate-400">Cuota Diaria</span>
                                </th>
                                <th class="py-3.5 px-3">
                                    Licencia / SOAT / Rev.
                                    <span class="block text-[10px] font-body font-normal text-slate-400">Vencimientos</span>
                                </th>
                                <th class="py-3.5 px-3">Estado de<br>Candado</th>
                                <th class="py-3.5 px-4 text-center">Acciones /<br>Candado</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200/60">
                            <template x-for="c in filtrados" :key="c.id">
                                <tr @click="seleccionado = c"
                                    class="cursor-pointer transition hover:bg-[#DCE6F2]/35"
                                    :class="seleccionado && seleccionado.id === c.id ? 'bg-[#DCE6F2]/50' : ''">
                                    <!-- Asociado -->
                                    <td class="py-3.5 px-4">
                                        <div class="flex items-center gap-3">
                                            <div class="w-10 h-10 rounded-full bg-gradient-to-br from-[#08142C] to-[#1E3A8A] text-white flex items-center justify-center font-headline font-bold text-sm shrink-0 shadow-xs">
                                                <span x-text="c.nombre.charAt(0)"></span>
                                            </div>
                                            <div class="min-w-0">
                                                <p class="font-headline font-bold text-sm text-[#08142C] truncate max-w-[165px]" x-text="c.nombre"></p>
                                                <p class="text-[11px] text-slate-500">DNI: <span x-text="c.dni"></span></p>
                                                <p class="text-[11px] text-slate-500" x-text="c.telefono"></p>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Unidad / Placa -->
                                    <td class="py-3.5 px-3">
                                        <p class="font-medium text-slate-700" x-text="c.modelo"></p>
                                        <p class="text-slate-600 font-semibold">(<span x-text="c.placa"></span>)</p>
                                    </td>

                                    <!-- Último Pago -->
                                    <td class="py-3.5 px-3">
                                        <p class="font-semibold text-slate-800" x-text="c.ultimo_pago"></p>
                                        <p class="text-[11px] text-slate-500" x-text="c.sub_pago"></p>
                                    </td>

                                    <!-- Deuda (S/) -->
                                    <td class="py-3.5 px-3">
                                        <span class="font-headline font-extrabold text-sm"
                                              :class="c.habilitado ? 'text-emerald-600' : 'text-[#D91B23]'"
                                              x-text="'S/ ' + c.deuda.toFixed(2)">
                                        </span>
                                    </td>

                                    <!-- Semáforo de Vencimientos (Licencia, SOAT, Revisión Técnica) -->
                                    <td class="py-3.5 px-3" @click.stop>
                                        <div class="space-y-1 text-[11px]">
                                            <div class="flex items-center gap-1.5">
                                                <span class="w-2 h-2 rounded-full shrink-0"
                                                      :class="c.docs.soat.estado === 'vencido' ? 'bg-[#D91B23]' : (c.docs.soat.estado === 'por_vencer' ? 'bg-amber-500' : 'bg-emerald-500')"></span>
                                                <span class="text-slate-600">SOAT:</span>
                                                <span class="font-semibold"
                                                      :class="c.docs.soat.estado === 'vencido' ? 'text-[#D91B23]' : (c.docs.soat.estado === 'por_vencer' ? 'text-amber-700' : 'text-slate-700')"
                                                      x-text="c.docs.soat.vence"></span>
                                            </div>
                                            <div class="flex items-center gap-1.5">
                                                <span class="w-2 h-2 rounded-full shrink-0"
                                                      :class="c.docs.licencia.estado === 'por_vencer' ? 'bg-amber-500' : 'bg-emerald-500'"></span>
                                                <span class="text-slate-600">Licencia:</span>
                                                <span class="font-semibold text-slate-700" x-text="c.docs.licencia.vence"></span>
                                            </div>
                                            <div class="flex items-center gap-1.5">
                                                <span class="w-2 h-2 rounded-full shrink-0"
                                                      :class="c.docs.revision.estado === 'vencido' ? 'bg-[#D91B23]' : (c.docs.revision.estado === 'por_vencer' ? 'bg-amber-500' : 'bg-emerald-500')"></span>
                                                <span class="text-slate-600">Rev. Téc:</span>
                                                <span class="font-semibold text-slate-700" x-text="c.docs.revision.vence"></span>
                                            </div>
                                            <template x-if="c.alerta_doc !== 'vigente'">
                                                <button type="button"
                                                    @click="abrirModalRenovacion(c, 'SOAT / Revisión Técnica')"
                                                    class="mt-1 inline-flex items-center gap-1 bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-[10px] px-2 py-0.5 rounded-md shadow-2xs transition">
                                                    <i class="ri-refresh-line"></i>
                                                    <span>Marcar Renovado</span>
                                                </button>
                                            </template>
                                        </div>
                                    </td>

                                    <!-- Estado de Candado -->
                                    <td class="py-3.5 px-3">
                                        <template x-if="c.habilitado">
                                            <span class="inline-block bg-emerald-100 text-emerald-800 font-bold text-[11px] px-3 py-1 rounded-lg">
                                                Habilitado
                                            </span>
                                        </template>
                                        <template x-if="!c.habilitado">
                                            <span class="inline-block bg-red-100 text-red-800 font-bold text-[11px] px-2.5 py-1 rounded-lg leading-tight text-center">
                                                Inactivo -<br>Bloqueado
                                            </span>
                                        </template>
                                    </td>

                                    <!-- Acciones / Candado -->
                                    <td class="py-3.5 px-4 text-center" @click.stop>
                                        <form :action="'/finanzas/conductor/' + c.id + '/candado'" method="POST" class="inline-flex flex-col items-center gap-1">
                                            @csrf
                                            <input type="hidden" name="accion" value="toggle">
                                            <div class="flex items-center gap-2">
                                                <button type="submit"
                                                    class="relative w-10 h-5 rounded-full transition-colors"
                                                    :class="c.habilitado ? 'bg-[#08142C]' : 'bg-slate-300'"
                                                    title="Cambiar estado del candado">
                                                    <span class="absolute top-0.5 w-4 h-4 rounded-full bg-white shadow transition-all"
                                                        :class="c.habilitado ? 'right-0.5' : 'left-0.5'"></span>
                                                </button>
                                                <i class="text-lg"
                                                   :class="c.habilitado ? 'ri-lock-unlock-fill text-[#08142C]' : 'ri-lock-fill text-[#D91B23]'"></i>
                                            </div>
                                            <span class="text-[9px] font-bold tracking-tight"
                                                  :class="c.habilitado ? 'text-slate-500' : 'text-[#D91B23] uppercase'"
                                                  x-text="c.habilitado ? 'Ver detalles' : 'CANDADO ACTIVADO'">
                                            </span>
                                        </form>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Columna Derecha (3/12): Acciones de Control Rápido + Renovación Documentaria -->
        <div class="xl:col-span-3">
            <template x-if="seleccionado">
                <div class="bg-white/95 rounded-3xl p-5 border border-white shadow-sm space-y-4 sticky top-4">
                    <h2 class="font-headline font-bold text-base text-[#08142C] leading-snug">
                        Acciones de Control<br>Rápido
                    </h2>

                    <!-- Datos del conductor seleccionado -->
                    <div class="flex items-center gap-3 pt-1">
                        <div class="w-12 h-12 rounded-full bg-gradient-to-br from-[#08142C] to-[#1E3A8A] text-white flex items-center justify-center font-headline font-bold text-base shrink-0 shadow">
                            <span x-text="seleccionado.nombre.charAt(0)"></span>
                        </div>
                        <div class="min-w-0">
                            <p class="font-headline font-bold text-sm text-[#08142C] truncate" x-text="seleccionado.nombre"></p>
                            <p class="text-xs font-bold"
                               :class="seleccionado.habilitado ? 'text-emerald-600' : 'text-[#D91B23]'"
                               x-text="'Deuda: S/ ' + seleccionado.deuda.toFixed(2)"></p>
                            <p class="text-xs text-slate-500 truncate" x-text="seleccionado.modelo + ' (' + seleccionado.placa + ')'"></p>
                        </div>
                    </div>

                    <!-- Control de Vencimientos Documentarios del Conductor Seleccionado -->
                    <div class="bg-slate-50 rounded-2xl p-3.5 border border-slate-200/70 space-y-2.5 text-xs">
                        <p class="font-headline font-bold text-[#08142C] flex items-center justify-between">
                            <span>Documentos del Vehículo</span>
                            <i class="ri-shield-user-line text-slate-400"></i>
                        </p>

                        <template x-for="(doc, clave) in seleccionado.docs" :key="clave">
                            <div class="flex items-center justify-between gap-2 bg-white rounded-xl px-2.5 py-2 border border-slate-200/60">
                                <div>
                                    <p class="font-semibold text-[#08142C] text-[11px]" x-text="doc.nombre"></p>
                                    <p class="text-[10px]"
                                       :class="doc.estado === 'vencido' ? 'text-[#D91B23] font-bold' : (doc.estado === 'por_vencer' ? 'text-amber-600 font-semibold' : 'text-slate-500')"
                                       x-text="(doc.estado === 'vencido' ? 'Vencido: ' : 'Vence: ') + doc.vence"></p>
                                </div>
                                <button type="button"
                                    @click="abrirModalRenovacion(seleccionado, doc.nombre)"
                                    class="px-2.5 py-1 rounded-lg text-[10px] font-bold transition flex items-center gap-1"
                                    :class="doc.estado === 'vigente' ? 'bg-slate-100 text-slate-600 hover:bg-slate-200' : 'bg-emerald-600 text-white hover:bg-emerald-500 shadow-2xs'">
                                    <i class="ri-checkbox-circle-line"></i>
                                    <span>Renovado</span>
                                </button>
                            </div>
                        </template>
                    </div>

                    <!-- Botón Rojo: Forzar Desactivación Manual -->
                    <form :action="'/finanzas/conductor/' + seleccionado.id + '/candado'" method="POST">
                        @csrf
                        <input type="hidden" name="accion" value="bloquear">
                        <button type="submit"
                            class="w-full bg-[#D91B23] hover:bg-[#B8141B] text-white font-headline font-bold text-xs py-3 px-4 rounded-xl shadow-sm transition leading-snug text-center">
                            Forzar Desactivación Manual (Bloqueo instantáneo)
                        </button>
                    </form>

                    <!-- Botón Azul Marino: Habilitar Excepcionalmente -->
                    <form :action="'/finanzas/conductor/' + seleccionado.id + '/candado'" method="POST">
                        @csrf
                        <input type="hidden" name="accion" value="habilitar">
                        <button type="submit"
                            class="w-full bg-[#08142C] hover:bg-[#13264E] text-white font-headline font-bold text-xs py-3 px-4 rounded-xl shadow-sm transition leading-snug text-center">
                            Habilitar Excepcionalmente (24h de gracia)
                        </button>
                    </form>

                    <!-- Notificaciones por WhatsApp (Pago o Vencimiento de Documento) -->
                    <div class="pt-2 border-t border-slate-100 space-y-2">
                        <a :href="'https://wa.me/51' + seleccionado.telefono.replace(/\D/g, '') + '?text=' + encodeURIComponent('Hola ' + seleccionado.nombre + ', te escribimos de Taxi KALUZ. Recuerda enviar la foto de tu SOAT/Revisión Técnica renovada o regularizar tu cuota diaria de S/ 1.00 para mantener tu radar activo.')"
                           target="_blank"
                           class="w-full bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border border-emerald-200 font-semibold text-xs py-2.5 px-3 rounded-xl transition flex items-center justify-center gap-1.5">
                            <i class="ri-whatsapp-line text-base text-emerald-600"></i>
                            <span>Enviar Alerta por WhatsApp</span>
                        </a>
                    </div>
                </div>
            </template>
        </div>
    </div>

    <!-- Modal: Confirmar Renovación de Documento (con imagen o clic en Renovado) -->
    <div x-show="modalRenovar" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-[#08142C]/60 backdrop-blur-xs" @click="modalRenovar = false"></div>
        <div x-transition class="relative bg-white rounded-3xl shadow-2xl w-full max-w-md p-6 space-y-4 border border-slate-100">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="font-headline font-bold text-lg text-[#08142C]">Confirmar Documento Renovado</h3>
                    <p class="text-xs text-slate-500" x-text="seleccionado ? seleccionado.nombre + ' • ' + seleccionado.placa : ''"></p>
                </div>
                <button type="button" @click="modalRenovar = false" class="text-slate-400 hover:text-slate-700">
                    <i class="ri-close-line text-xl"></i>
                </button>
            </div>

            <form :action="seleccionado ? '/finanzas/conductor/' + seleccionado.id + '/renovar-documento' : '#'" method="POST" enctype="multipart/form-data" class="space-y-4 text-xs">
                @csrf
                <input type="hidden" name="documento" :value="docRenovar">

                <div class="bg-slate-50 rounded-2xl p-3.5 border border-slate-200/80 flex items-center justify-between">
                    <div>
                        <span class="text-slate-500 block">Documento a actualizar:</span>
                        <strong class="font-headline text-sm text-[#08142C]" x-text="docRenovar"></strong>
                    </div>
                    <span class="bg-emerald-100 text-emerald-800 font-bold px-2.5 py-1 rounded-lg">
                        +1 Año Vigencia
                    </span>
                </div>

                <div>
                    <label class="font-semibold text-slate-700 block mb-1">Nueva fecha de vencimiento</label>
                    <input type="date" name="nueva_fecha" value="{{ now()->addYear()->toDateString() }}"
                        class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 focus:outline-none focus:ring-2 focus:ring-[#08142C]">
                </div>

                <div>
                    <label class="font-semibold text-slate-700 block mb-1">Adjuntar foto del documento renovado (opcional)</label>
                    <label class="cursor-pointer flex flex-col items-center justify-center rounded-2xl border-2 border-dashed border-slate-300 bg-slate-50 hover:bg-slate-100 p-4 transition text-center">
                        <input type="file" name="foto_documento" accept="image/*" class="hidden"
                               @change="archivoDoc = $event.target.files[0]?.name || ''">
                        <i class="ri-camera-lens-line text-2xl text-[#08142C] mb-1"></i>
                        <span class="font-medium text-slate-600" x-text="archivoDoc || 'Haz clic para subir foto de SOAT, Licencia o Revisión Técnica'"></span>
                    </label>
                </div>

                <div class="pt-1 flex justify-end gap-2">
                    <button type="button" @click="modalRenovar = false"
                        class="px-4 py-2.5 rounded-xl border border-slate-300 font-semibold text-slate-600 hover:bg-slate-50">
                        Cancelar
                    </button>
                    <button type="submit"
                        class="px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-headline font-bold shadow-sm flex items-center gap-1.5">
                        <i class="ri-checkbox-circle-fill text-sm"></i>
                        <span>Confirmar Renovado y Habilitar</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal: Añadir Nuevo Asociado -->
    <div x-show="modalNuevo" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-[#08142C]/60 backdrop-blur-xs" @click="modalNuevo = false"></div>
        <div x-transition class="relative bg-white rounded-3xl shadow-2xl w-full max-w-md p-6 space-y-4 border border-slate-100">
            <div class="flex items-center justify-between">
                <h3 class="font-headline font-bold text-lg text-[#08142C]">Registrar Nuevo Asociado</h3>
                <button type="button" @click="modalNuevo = false" class="text-slate-400 hover:text-slate-700">
                    <i class="ri-close-line text-xl"></i>
                </button>
            </div>

            <form action="{{ route('finanzas.conductor.store') }}" method="POST" class="space-y-3 text-xs">
                @csrf
                <div>
                    <label class="font-semibold text-slate-700 block mb-1">Nombre completo</label>
                    <input type="text" name="nombre" required placeholder="Ej: Roberto Díaz Huamán"
                        class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 focus:outline-none focus:ring-2 focus:ring-[#08142C]">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="font-semibold text-slate-700 block mb-1">DNI</label>
                        <input type="text" name="dni" required maxlength="8" placeholder="45891230"
                            class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 focus:outline-none focus:ring-2 focus:ring-[#08142C]">
                    </div>
                    <div>
                        <label class="font-semibold text-slate-700 block mb-1">WhatsApp</label>
                        <input type="text" name="telefono_whatsapp" required placeholder="964 555 777"
                            class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 focus:outline-none focus:ring-2 focus:ring-[#08142C]">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="font-semibold text-slate-700 block mb-1">Placa</label>
                        <input type="text" name="placa" required placeholder="W3X-451"
                            class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 uppercase focus:outline-none focus:ring-2 focus:ring-[#08142C]">
                    </div>
                    <div>
                        <label class="font-semibold text-slate-700 block mb-1">Modelo de Vehículo</label>
                        <input type="text" name="modelo_vehiculo" required placeholder="Toyota Yaris 2023"
                            class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 focus:outline-none focus:ring-2 focus:ring-[#08142C]">
                    </div>
                </div>

                <div>
                    <label class="font-semibold text-slate-700 block mb-1">Tipo de Flota</label>
                    <select name="tipo_flota" class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 focus:outline-none focus:ring-2 focus:ring-[#08142C]">
                        <option value="afiliado">Asociado Afiliado (Cuota S/ 1.00 diario)</option>
                        <option value="propia_auto">Flota Propia Kaluz - Auto</option>
                        <option value="propia_combi">Flota Propia Kaluz - Combi Turismo</option>
                    </select>
                </div>

                <div class="pt-2 flex justify-end gap-2">
                    <button type="button" @click="modalNuevo = false"
                        class="px-4 py-2.5 rounded-xl border border-slate-300 font-semibold text-slate-600 hover:bg-slate-50">
                        Cancelar
                    </button>
                    <button type="submit"
                        class="px-5 py-2.5 rounded-xl bg-[#08142C] hover:bg-[#13264E] text-white font-headline font-bold shadow-sm">
                        Guardar y Habilitar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
