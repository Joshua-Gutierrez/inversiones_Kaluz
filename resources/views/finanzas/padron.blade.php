@extends('layouts.torre-control')

@section('titulo', 'Padrón de Conductores - Taxi Kaluz')
@section('encabezado', 'Padrón y Candado Financiero')

@section('content')
    <div class="space-y-6">
        @php
            $estadoActual = $filtros['estado'] ?? '';
            $filtrosBase = array_filter(
                ['buscar' => $filtros['buscar'] ?? null],
                fn ($valor) => filled($valor)
            );
        @endphp

        @if (session('estado'))
            <div role="status" class="rounded-xl border border-emerald-300 bg-emerald-100 px-4 py-3 text-sm font-semibold text-emerald-800">
                {{ session('estado') }}
            </div>
        @endif
        @if ($errors->any())
            <div role="alert" class="rounded-xl border border-red-300 bg-red-100 px-4 py-3 text-sm font-semibold text-red-800">
                <ul class="list-inside list-disc">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
            <x-tarjeta-kaluz titulo="Conductores en padrón" subtitulo="Afiliados y flota propia">
                <p class="text-3xl font-extrabold text-[#0B1B3D]">{{ number_format($estadisticas['total']) }}</p>
            </x-tarjeta-kaluz>
            <x-tarjeta-kaluz titulo="Habilitados" subtitulo="Comisión y permiso vigentes">
                <p class="text-3xl font-extrabold text-emerald-700">{{ number_format($estadisticas['habilitados']) }}</p>
            </x-tarjeta-kaluz>
            <x-tarjeta-kaluz titulo="Con candado activo" subtitulo="Sin acceso a recibir viajes">
                <p class="text-3xl font-extrabold text-[#E31B23]">{{ number_format($estadisticas['bloqueados']) }}</p>
            </x-tarjeta-kaluz>
            <x-tarjeta-kaluz titulo="Permiso por vencer" subtitulo="Vence dentro de los próximos 30 días">
                <p class="text-3xl font-extrabold text-amber-600">{{ number_format($estadisticas['permisosPorVencer']) }}</p>
            </x-tarjeta-kaluz>
        </div>

        <x-tarjeta-kaluz titulo="Registrar conductor" subtitulo="El nuevo registro inicia bloqueado hasta aprobar su primer pago diario.">
            <details>
                <summary class="inline-flex cursor-pointer items-center rounded-xl bg-[#E31B23] px-4 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-red-700">
                    + Registrar socio o unidad propia
                </summary>
                <form method="POST" action="{{ route('finanzas.padron.conductores.store') }}"
                    class="mt-5 grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
                    @csrf
                    <label class="text-sm font-semibold text-[#0B1B3D]">
                        Nombre completo
                        <input name="nombre" value="{{ old('nombre') }}" required maxlength="120"
                            class="mt-1 min-h-11 w-full rounded-xl border border-slate-300 px-3 font-normal">
                    </label>
                    <label class="text-sm font-semibold text-[#0B1B3D]">
                        DNI
                        <input name="dni" value="{{ old('dni') }}" required minlength="8" maxlength="8" pattern="[0-9]{8}"
                            class="mt-1 min-h-11 w-full rounded-xl border border-slate-300 px-3 font-normal">
                    </label>
                    <label class="text-sm font-semibold text-[#0B1B3D]">
                        Teléfono WhatsApp
                        <input name="telefono_whatsapp" value="{{ old('telefono_whatsapp') }}" required maxlength="20"
                            class="mt-1 min-h-11 w-full rounded-xl border border-slate-300 px-3 font-normal">
                    </label>
                    <label class="text-sm font-semibold text-[#0B1B3D]">
                        Placa
                        <input name="placa" value="{{ old('placa') }}" maxlength="15"
                            class="mt-1 min-h-11 w-full rounded-xl border border-slate-300 px-3 font-normal">
                    </label>
                    <label class="text-sm font-semibold text-[#0B1B3D]">
                        Modelo del vehículo
                        <input name="modelo_vehiculo" value="{{ old('modelo_vehiculo') }}" maxlength="80"
                            class="mt-1 min-h-11 w-full rounded-xl border border-slate-300 px-3 font-normal">
                    </label>
                    <label class="text-sm font-semibold text-[#0B1B3D]">
                        Tipo de flota
                        <select name="tipo_flota" required class="mt-1 min-h-11 w-full rounded-xl border border-slate-300 bg-white px-3 font-normal">
                            <option value="afiliado">Afiliado</option>
                            <option value="propia_auto">Auto propio</option>
                            <option value="propia_combi">Combi propia</option>
                        </select>
                    </label>
                    <label class="text-sm font-semibold text-[#0B1B3D]">
                        Vencimiento del permiso / SOAT
                        <input type="date" name="fecha_vencimiento_permiso" value="{{ old('fecha_vencimiento_permiso') }}" required
                            class="mt-1 min-h-11 w-full rounded-xl border border-slate-300 px-3 font-normal">
                    </label>
                    <div class="flex items-end">
                        <button type="submit" class="rounded-xl bg-[#0B1B3D] px-5 py-2.5 text-sm font-bold text-white hover:bg-[#142d5b]">
                            Guardar conductor
                        </button>
                    </div>
                </form>
            </details>
        </x-tarjeta-kaluz>

        <x-tarjeta-kaluz titulo="Padrón General" subtitulo="Busca conductores y revisa su estado financiero y documentario.">
            <div class="mb-6 rounded-2xl border border-slate-200 bg-slate-50/80 p-4 md:p-5">
                <form method="GET" action="{{ route('finanzas.padron') }}"
                    class="flex flex-col gap-3 lg:flex-row lg:items-center">
                    @if ($estadoActual)
                        <input type="hidden" name="estado" value="{{ $estadoActual }}">
                    @endif
                    <label class="sr-only" for="buscar">Buscar por nombre, DNI o placa</label>
                    <div class="relative min-w-0 flex-1">
                        <svg aria-hidden="true" class="pointer-events-none absolute left-3.5 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400"
                            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <circle cx="10.8" cy="10.8" r="6.8"></circle>
                            <path d="m16 16 4.5 4.5"></path>
                        </svg>
                        <input id="buscar" name="buscar" value="{{ $filtros['buscar'] ?? '' }}"
                            placeholder="Buscar conductor por nombre, DNI o placa..."
                            class="min-h-12 w-full rounded-xl border border-slate-300 bg-white py-3 pl-11 pr-4 text-sm text-[#0B1B3D] shadow-sm placeholder:text-slate-400 focus:border-[#0B1B3D] focus:outline-none focus:ring-4 focus:ring-[#0B1B3D]/10">
                    </div>
                    <button type="submit"
                        class="inline-flex min-h-12 items-center justify-center gap-2 rounded-xl bg-[#0B1B3D] px-5 text-sm font-bold text-white shadow-sm transition hover:bg-[#142d5b] focus:outline-none focus:ring-4 focus:ring-[#0B1B3D]/20">
                        Buscar
                    </button>
                    <a href="{{ route('finanzas.padron.exportar', request()->query()) }}"
                        class="inline-flex min-h-12 items-center justify-center gap-2 rounded-xl border border-slate-300 bg-white px-4 text-sm font-bold text-[#0B1B3D] shadow-sm transition hover:border-slate-400 hover:bg-slate-100">
                        <svg aria-hidden="true" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path d="M12 3v12m0 0 4-4m-4 4-4-4"></path>
                            <path d="M4 16v4h16v-4"></path>
                        </svg>
                        Exportar CSV
                    </a>
                </form>

                <div class="mt-4 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wider text-slate-500">Estado del padrón</p>
                        <p class="mt-1 text-xs text-slate-500">Selecciona una categoría para filtrar la tabla.</p>
                    </div>
                    <nav aria-label="Filtrar conductores por estado" class="grid grid-cols-3 gap-2 rounded-xl bg-slate-200/70 p-1">
                        <a href="{{ route('finanzas.padron', $filtrosBase) }}"
                            @if ($estadoActual === '') aria-current="page" @endif
                            class="inline-flex min-h-10 items-center justify-center gap-2 rounded-lg px-3 text-sm font-semibold transition {{ $estadoActual === '' ? 'bg-white text-[#0B1B3D] shadow-sm ring-1 ring-slate-200' : 'text-slate-600 hover:bg-white/70 hover:text-[#0B1B3D]' }}">
                            Todos
                            <span class="rounded-full px-2 py-0.5 text-xs {{ $estadoActual === '' ? 'bg-[#0B1B3D]/10 text-[#0B1B3D]' : 'bg-white/70 text-slate-500' }}">
                                {{ number_format($estadisticas['total']) }}
                            </span>
                        </a>
                        <a href="{{ route('finanzas.padron', $filtrosBase + ['estado' => 'habilitado']) }}"
                            @if ($estadoActual === 'habilitado') aria-current="page" @endif
                            class="inline-flex min-h-10 items-center justify-center gap-2 rounded-lg px-3 text-sm font-semibold transition {{ $estadoActual === 'habilitado' ? 'bg-white text-emerald-800 shadow-sm ring-1 ring-emerald-200' : 'text-slate-600 hover:bg-white/70 hover:text-emerald-800' }}">
                            <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                            Habilitados
                            <span class="rounded-full px-2 py-0.5 text-xs {{ $estadoActual === 'habilitado' ? 'bg-emerald-100 text-emerald-800' : 'bg-white/70 text-slate-500' }}">
                                {{ number_format($estadisticas['habilitados']) }}
                            </span>
                        </a>
                        <a href="{{ route('finanzas.padron', $filtrosBase + ['estado' => 'bloqueado']) }}"
                            @if ($estadoActual === 'bloqueado') aria-current="page" @endif
                            class="inline-flex min-h-10 items-center justify-center gap-2 rounded-lg px-3 text-sm font-semibold transition {{ $estadoActual === 'bloqueado' ? 'bg-white text-[#E31B23] shadow-sm ring-1 ring-red-200' : 'text-slate-600 hover:bg-white/70 hover:text-[#E31B23]' }}">
                            <span class="h-2 w-2 rounded-full bg-[#E31B23]"></span>
                            Con candado
                            <span class="rounded-full px-2 py-0.5 text-xs {{ $estadoActual === 'bloqueado' ? 'bg-red-100 text-[#E31B23]' : 'bg-white/70 text-slate-500' }}">
                                {{ number_format($estadisticas['bloqueados']) }}
                            </span>
                        </a>
                    </nav>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full border-collapse text-left text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 text-xs uppercase text-[#64748B]">
                            <th scope="col" class="px-4 py-3">Conductor</th>
                            <th scope="col" class="px-4 py-3">Contacto</th>
                            <th scope="col" class="px-4 py-3">Unidad</th>
                            <th scope="col" class="px-4 py-3">Comisión pendiente</th>
                            <th scope="col" class="px-4 py-3">Permiso / SOAT</th>
                            <th scope="col" class="px-4 py-3">Semáforo</th>
                            <th scope="col" class="px-4 py-3">Gestión</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($conductores as $conductor)
                            @php
                                $permisoVigente = $conductor->fecha_vencimiento_permiso
                                    && ! $conductor->fecha_vencimiento_permiso->isBefore(today());
                                $porVencer = $permisoVigente
                                    && $conductor->fecha_vencimiento_permiso->lte(today()->addDays(30));
                                $habilitado = $conductor->habilitado
                                    && $conductor->estado_comision === 'al_dia'
                                    && (float) $conductor->deuda_comision <= 0
                                    && $permisoVigente;
                                $pagoReciente = $conductor->pagosComision->first();
                            @endphp
                            <tr class="transition hover:bg-slate-50">
                                <td class="px-4 py-3">
                                    <p class="font-semibold text-[#0B1B3D]">{{ $conductor->nombre }}</p>
                                    <p class="text-xs text-slate-500">DNI {{ $conductor->dni ?: 'No registrado' }}</p>
                                </td>
                                <td class="px-4 py-3 text-slate-600">{{ $conductor->telefono_whatsapp ?: '—' }}</td>
                                <td class="px-4 py-3">
                                    <p class="font-semibold text-slate-700">{{ $conductor->placa ?: 'Sin placa' }}</p>
                                    <p class="text-xs text-slate-500">{{ $conductor->modelo_vehiculo ?: 'Vehículo no registrado' }}</p>
                                </td>
                                <td class="px-4 py-3 font-semibold {{ (float) $conductor->deuda_comision > 0 ? 'text-[#E31B23]' : 'text-emerald-700' }}">
                                    S/ {{ number_format((float) $conductor->deuda_comision, 2) }}
                                    @if ($pagoReciente?->estado === 'pendiente')
                                        <span class="mt-1 block text-xs font-medium text-amber-700">Pago en revisión</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    @if ($conductor->fecha_vencimiento_permiso)
                                        <span class="{{ $porVencer ? 'text-amber-700' : ($permisoVigente ? 'text-emerald-700' : 'text-[#E31B23]') }}">
                                            {{ $conductor->fecha_vencimiento_permiso->format('d/m/Y') }}
                                        </span>
                                        @if ($porVencer)
                                            <span class="block text-xs text-amber-700">Por vencer</span>
                                        @elseif (! $permisoVigente)
                                            <span class="block text-xs text-[#E31B23]">Vencido</span>
                                        @endif
                                    @else
                                        <span class="text-[#E31B23]">No registrado</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    @if ($porVencer && $conductor->estado_comision === 'al_dia')
                                        <x-badge-semaforo estado="alerta" texto="Permiso por vencer" />
                                    @elseif ($habilitado)
                                        <x-badge-semaforo estado="habilitado" texto="Habilitado" />
                                    @else
                                        <x-badge-semaforo estado="bloqueado" texto="Candado activo" />
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    <details class="min-w-48">
                                        <summary class="cursor-pointer font-semibold text-[#0B1B3D] underline decoration-[#E31B23] underline-offset-2">
                                            Editar datos
                                        </summary>
                                        <form method="POST" action="{{ route('finanzas.padron.conductores.actualizar', $conductor) }}"
                                            class="mt-3 space-y-2 rounded-xl border border-slate-200 bg-white p-3">
                                            @csrf
                                            @method('PATCH')
                                            <label class="block text-xs font-semibold text-slate-600">
                                                Nombre
                                                <input name="nombre" value="{{ $conductor->nombre }}" required maxlength="120"
                                                    class="mt-1 min-h-9 w-full rounded-lg border border-slate-300 px-2 font-normal">
                                            </label>
                                            <label class="block text-xs font-semibold text-slate-600">
                                                DNI
                                                <input name="dni" value="{{ $conductor->dni }}" required minlength="8" maxlength="8" pattern="[0-9]{8}"
                                                    class="mt-1 min-h-9 w-full rounded-lg border border-slate-300 px-2 font-normal">
                                            </label>
                                            <label class="block text-xs font-semibold text-slate-600">
                                                Teléfono WhatsApp
                                                <input name="telefono_whatsapp" value="{{ $conductor->telefono_whatsapp }}" required maxlength="20"
                                                    class="mt-1 min-h-9 w-full rounded-lg border border-slate-300 px-2 font-normal">
                                            </label>
                                            <label class="block text-xs font-semibold text-slate-600">
                                                Placa
                                                <input name="placa" value="{{ $conductor->placa }}" maxlength="15"
                                                    class="mt-1 min-h-9 w-full rounded-lg border border-slate-300 px-2 font-normal">
                                            </label>
                                            <label class="block text-xs font-semibold text-slate-600">
                                                Modelo
                                                <input name="modelo_vehiculo" value="{{ $conductor->modelo_vehiculo }}" maxlength="80"
                                                    class="mt-1 min-h-9 w-full rounded-lg border border-slate-300 px-2 font-normal">
                                            </label>
                                            <label class="block text-xs font-semibold text-slate-600">
                                                Tipo de flota
                                                <select name="tipo_flota" required class="mt-1 min-h-9 w-full rounded-lg border border-slate-300 bg-white px-2 font-normal">
                                                    <option value="afiliado" @selected($conductor->tipo_flota === 'afiliado')>Afiliado</option>
                                                    <option value="propia_auto" @selected($conductor->tipo_flota === 'propia_auto')>Auto propio</option>
                                                    <option value="propia_combi" @selected($conductor->tipo_flota === 'propia_combi')>Combi propia</option>
                                                </select>
                                            </label>
                                            <label class="block text-xs font-semibold text-slate-600">
                                                Vencimiento del permiso / SOAT
                                                <input type="date" name="fecha_vencimiento_permiso"
                                                    value="{{ $conductor->fecha_vencimiento_permiso?->format('Y-m-d') }}" required
                                                    class="mt-1 min-h-9 w-full rounded-lg border border-slate-300 px-2 font-normal">
                                            </label>
                                            <button type="submit" class="w-full rounded-lg bg-[#0B1B3D] px-3 py-2 text-xs font-bold text-white hover:bg-[#142d5b]">
                                                Guardar cambios
                                            </button>
                                        </form>
                                    </details>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-10 text-center text-slate-500">
                                    No hay conductores que coincidan con la búsqueda.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-5">{{ $conductores->links() }}</div>
        </x-tarjeta-kaluz>

        <x-tarjeta-kaluz titulo="Comprobantes pendientes de revisión" subtitulo="Aprueba solo pagos cuyo comprobante y monto hayas verificado.">
            @if ($pagosPendientes->isEmpty())
                <p class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-5 text-sm text-emerald-800">
                    No hay comprobantes pendientes.
                </p>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full border-collapse text-left text-sm">
                        <thead>
                            <tr class="border-b border-slate-200 text-xs uppercase text-[#64748B]">
                                <th scope="col" class="px-4 py-3">Conductor</th>
                                <th scope="col" class="px-4 py-3">Monto / método</th>
                                <th scope="col" class="px-4 py-3">Comprobante</th>
                                <th scope="col" class="px-4 py-3">Enviado</th>
                                <th scope="col" class="px-4 py-3 text-right">Acción</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($pagosPendientes as $pago)
                                <tr class="hover:bg-slate-50">
                                    <td class="px-4 py-3 font-semibold text-[#0B1B3D]">
                                        {{ $pago->conductor?->nombre ?? 'Conductor no disponible' }}
                                    </td>
                                    <td class="px-4 py-3 text-slate-700">
                                        <span class="font-bold">S/ {{ number_format((float) $pago->monto, 2) }}</span>
                                        <span class="block text-xs text-slate-500">{{ ucfirst($pago->metodo_pago ?: 'No especificado') }}</span>
                                    </td>
                                    <td class="px-4 py-3">
                                        @if (is_string($pago->comprobante_url) && filter_var($pago->comprobante_url, FILTER_VALIDATE_URL) && in_array(parse_url($pago->comprobante_url, PHP_URL_SCHEME), ['http', 'https'], true))
                                            <a href="{{ $pago->comprobante_url }}" target="_blank" rel="noopener noreferrer"
                                                class="font-semibold text-[#0B1B3D] underline decoration-[#E31B23] underline-offset-2">
                                                Ver comprobante
                                            </a>
                                        @else
                                            <span class="text-amber-700">Sin enlace válido</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-slate-600">{{ $pago->fecha_registro?->format('d/m/Y H:i') ?? '—' }}</td>
                                    <td class="px-4 py-3">
                                        <div class="flex justify-end gap-2">
                                            <form method="POST" action="{{ route('finanzas.yape.validar', $pago) }}">
                                                @csrf
                                                <button type="submit"
                                                    class="rounded-xl bg-[#E31B23] px-3 py-2 text-xs font-bold text-white shadow-sm hover:bg-red-700">
                                                    Aprobar
                                                </button>
                                            </form>
                                            <form method="POST" action="{{ route('finanzas.yape.rechazar', $pago) }}">
                                                @csrf
                                                <button type="submit"
                                                    class="rounded-xl border border-slate-300 px-3 py-2 text-xs font-bold text-slate-700 hover:bg-slate-100">
                                                    Rechazar
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="mt-5">{{ $pagosPendientes->links() }}</div>
            @endif
        </x-tarjeta-kaluz>
    </div>
@endsection
