@extends('layouts.torre-control')

@section('titulo', 'Padrón de Conductores - Taxi Kaluz')
@section('encabezado', 'Padrón y Candado Financiero')

@section('content')
    <div class="space-y-6">
        @php
            $estadoActual = $filtros['estado'] ?? '';
            $porcentajeHabilitados = $estadisticas['total'] > 0
                ? (int) round(($estadisticas['habilitados'] / $estadisticas['total']) * 100)
                : 0;
            $filtrosBase = array_filter(
                ['buscar' => $filtros['buscar'] ?? null],
                fn ($valor) => filled($valor)
            );
        @endphp

        <header class="flex flex-col gap-4 border-b border-slate-200 pb-5 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="mb-1 text-xs font-bold uppercase tracking-[0.14em] text-[#E31B23]">Gestión operativa <span class="px-1 text-slate-300">/</span> Flota y afiliados</p>
                <h2 class="font-headline text-2xl font-bold tracking-tight text-[#08183D] sm:text-[28px]">Padrón de conductores</h2>
                <p class="mt-1 text-sm text-slate-500">Administra socios, unidades, comisión y documentación.</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                @if ($modoDemo)
                    <a href="{{ route('finanzas.padron') }}"
                        class="inline-flex min-h-10 items-center gap-2 rounded-lg border border-slate-200 bg-white px-3.5 text-sm font-semibold text-[#08183D] shadow-sm transition hover:bg-slate-50">
                        Salir de demostración
                    </a>
                @else
                    @if (app()->environment('local'))
                        <a href="{{ route('finanzas.padron', ['demo' => 1]) }}"
                            class="inline-flex min-h-10 items-center gap-2 rounded-lg border border-slate-200 bg-white px-3.5 text-sm font-semibold text-[#08183D] shadow-sm transition hover:bg-slate-50">
                            Probar demo
                        </a>
                    @endif
                    <a href="#registro-conductor"
                        class="inline-flex min-h-10 items-center gap-2 rounded-lg bg-[#E31B23] px-4 text-sm font-bold text-white shadow-sm transition hover:bg-red-700 focus:outline-none focus:ring-4 focus:ring-[#E31B23]/20">
                        <span aria-hidden="true" class="text-lg leading-none">+</span>
                        Añadir conductor
                    </a>
                @endif
            </div>
        </header>

        @if ($modoDemo)
            <div role="status" class="flex flex-col gap-2 border-l-4 border-[#E31B23] bg-white px-4 py-3 text-sm text-[#08183D] shadow-sm sm:flex-row sm:items-center sm:justify-between">
                <p><strong>Vista de demostración.</strong> Datos ficticios y solo lectura; no se guardan cambios en Supabase.</p>
                <span class="shrink-0 text-xs font-bold uppercase tracking-wide text-[#E31B23]">Entorno local</span>
            </div>
        @endif

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

        <div class="grid grid-cols-1 gap-3 min-[520px]:grid-cols-2 xl:grid-cols-4">
            <article class="flex items-center justify-between gap-3 rounded-xl border border-slate-200/80 bg-white p-4 shadow-sm">
                <div>
                    <p class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Conductores registrados</p>
                    <p class="mt-1 font-headline text-2xl font-extrabold text-[#08183D]">{{ number_format($estadisticas['total']) }}</p>
                    <p class="mt-0.5 text-xs text-slate-500">{{ $porcentajeHabilitados }}% habilitados</p>
                </div>
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-[#08183D]/[0.07] text-[#08183D]">
                    <svg aria-hidden="true" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <circle cx="9" cy="8" r="3.5"></circle><path d="M2.8 20v-1.5A4.5 4.5 0 0 1 7.3 14h3.4a4.5 4.5 0 0 1 4.5 4.5V20"></path><path d="M16 4.8a3.5 3.5 0 0 1 0 6.5m2 2.8a4.5 4.5 0 0 1 3.2 4.3V20"></path>
                    </svg>
                </span>
            </article>
            <article class="flex items-center justify-between gap-3 rounded-xl border border-slate-200/80 bg-white p-4 shadow-sm">
                <div>
                    <p class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Habilitados</p>
                    <p class="mt-1 font-headline text-2xl font-extrabold text-emerald-700">{{ number_format($estadisticas['habilitados']) }}</p>
                    <p class="mt-0.5 text-xs text-slate-500">Comisión y documentos vigentes</p>
                </div>
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-emerald-50 text-emerald-700">
                    <svg aria-hidden="true" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path d="M5 12.5 9.2 17 19 7"></path><circle cx="12" cy="12" r="9"></circle>
                    </svg>
                </span>
            </article>
            <article class="flex items-center justify-between gap-3 rounded-xl border border-slate-200/80 bg-white p-4 shadow-sm">
                <div>
                    <p class="text-[11px] font-bold uppercase tracking-wider text-slate-500">En observación / candado</p>
                    <p class="mt-1 font-headline text-2xl font-extrabold text-[#E31B23]">{{ number_format($estadisticas['bloqueados']) }}</p>
                    <p class="mt-0.5 text-xs text-slate-500">No pueden recibir viajes</p>
                </div>
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-[#E31B23]/[0.08] text-[#E31B23]">
                    <svg aria-hidden="true" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <rect x="4" y="10" width="16" height="11" rx="2"></rect><path d="M8 10V7a4 4 0 1 1 8 0v3m-4 4v3"></path>
                    </svg>
                </span>
            </article>
            <article class="flex items-center justify-between gap-3 rounded-xl border border-slate-200/80 bg-white p-4 shadow-sm">
                <div>
                    <p class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Permisos por vencer</p>
                    <p class="mt-1 font-headline text-2xl font-extrabold text-amber-600">{{ number_format($estadisticas['permisosPorVencer']) }}</p>
                    <p class="mt-0.5 text-xs text-slate-500">Próximos 30 días</p>
                </div>
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-amber-50 text-amber-600">
                    <svg aria-hidden="true" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <rect x="3" y="5" width="18" height="16" rx="2"></rect><path d="M16 3v4M8 3v4M3 10h18"></path>
                    </svg>
                </span>
            </article>
        </div>

        @unless ($modoDemo)
        <x-tarjeta-kaluz titulo="Nuevo conductor" subtitulo="El nuevo registro inicia bloqueado hasta aprobar su primer pago diario." id="registro-conductor" class="scroll-mt-6">
            <details>
                <summary class="inline-flex cursor-pointer items-center gap-2 rounded-lg bg-[#E31B23] px-4 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-red-700">
                    <span aria-hidden="true" class="text-lg leading-none">+</span>
                    Registrar socio o unidad propia
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
        @endunless

        <x-tarjeta-kaluz titulo="Flota y afiliados" subtitulo="Identidad, unidad, estado de comisión y vigencia documentaria.">
            <div class="mb-5 rounded-xl border border-slate-200 bg-slate-50 p-4">
                <form method="GET" action="{{ route('finanzas.padron') }}"
                    class="flex flex-col gap-2 sm:flex-row sm:items-center">
                    @if ($modoDemo)
                        <input type="hidden" name="demo" value="1">
                    @endif
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
                            placeholder="Buscar por nombre, DNI o placa..."
                            class="h-11 w-full rounded-lg border border-slate-200 bg-white py-2.5 pl-11 pr-4 text-sm text-[#08183D] placeholder:text-slate-400 transition focus:border-[#08183D]/40 focus:outline-none focus:ring-4 focus:ring-[#08183D]/[0.06]">
                    </div>
                    <button type="submit"
                        class="inline-flex h-11 items-center justify-center gap-2 rounded-lg bg-[#08183D] px-4 text-sm font-bold text-white shadow-sm transition hover:bg-[#142d5b] focus:outline-none focus:ring-4 focus:ring-[#08183D]/20">
                        Buscar
                    </button>
                    @unless ($modoDemo)
                        <a href="{{ route('finanzas.padron.exportar', request()->query()) }}"
                            class="inline-flex h-11 items-center justify-center gap-2 rounded-lg border border-slate-200 bg-white px-3.5 text-sm font-semibold text-[#08183D] transition hover:border-slate-300 hover:bg-slate-100">
                            <svg aria-hidden="true" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                <path d="M12 3v12m0 0 4-4m-4 4-4-4"></path>
                                <path d="M4 16v4h16v-4"></path>
                            </svg>
                            Exportar CSV
                        </a>
                    @endunless
                </form>

                <div class="mt-4 flex flex-col gap-2 border-t border-slate-200 pt-3 sm:flex-row sm:items-center sm:justify-between">
                    <p class="text-xs font-semibold text-slate-500">Filtrar por habilitación</p>
                    <nav aria-label="Filtrar conductores por estado" class="inline-flex max-w-full flex-wrap gap-1 rounded-lg bg-slate-200/70 p-1">
                        <a href="{{ route('finanzas.padron', $filtrosBase + ($modoDemo ? ['demo' => 1] : [])) }}"
                            @if ($estadoActual === '') aria-current="page" @endif
                            class="inline-flex min-h-9 items-center justify-center gap-2 rounded-md px-3 text-xs font-semibold transition {{ $estadoActual === '' ? 'bg-white text-[#08183D] shadow-sm' : 'text-slate-600 hover:bg-white/70 hover:text-[#08183D]' }}">
                            Todos
                            <span class="rounded-full px-1.5 py-0.5 text-[10px] {{ $estadoActual === '' ? 'bg-[#08183D]/10 text-[#08183D]' : 'bg-white/70 text-slate-500' }}">
                                {{ number_format($estadisticas['total']) }}
                            </span>
                        </a>
                        <a href="{{ route('finanzas.padron', $filtrosBase + ['estado' => 'habilitado'] + ($modoDemo ? ['demo' => 1] : [])) }}"
                            @if ($estadoActual === 'habilitado') aria-current="page" @endif
                            class="inline-flex min-h-9 items-center justify-center gap-2 rounded-md px-3 text-xs font-semibold transition {{ $estadoActual === 'habilitado' ? 'bg-white text-emerald-800 shadow-sm' : 'text-slate-600 hover:bg-white/70 hover:text-emerald-800' }}">
                            <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                            Habilitados
                            <span class="rounded-full px-1.5 py-0.5 text-[10px] {{ $estadoActual === 'habilitado' ? 'bg-emerald-100 text-emerald-800' : 'bg-white/70 text-slate-500' }}">
                                {{ number_format($estadisticas['habilitados']) }}
                            </span>
                        </a>
                        <a href="{{ route('finanzas.padron', $filtrosBase + ['estado' => 'bloqueado'] + ($modoDemo ? ['demo' => 1] : [])) }}"
                            @if ($estadoActual === 'bloqueado') aria-current="page" @endif
                            class="inline-flex min-h-9 items-center justify-center gap-2 rounded-md px-3 text-xs font-semibold transition {{ $estadoActual === 'bloqueado' ? 'bg-white text-[#E31B23] shadow-sm' : 'text-slate-600 hover:bg-white/70 hover:text-[#E31B23]' }}">
                            <span class="h-2 w-2 rounded-full bg-[#E31B23]"></span>
                            Con candado
                            <span class="rounded-full px-1.5 py-0.5 text-[10px] {{ $estadoActual === 'bloqueado' ? 'bg-red-100 text-[#E31B23]' : 'bg-white/70 text-slate-500' }}">
                                {{ number_format($estadisticas['bloqueados']) }}
                            </span>
                        </a>
                    </nav>
                </div>
            </div>
            @if ($modoDemo)
                <p class="mb-3 flex items-center gap-2 text-xs font-medium text-slate-500">
                    <span class="h-1.5 w-1.5 rounded-full bg-[#E31B23]"></span>
                    Datos ficticios para probar búsqueda, filtros, semáforo y paginación. Las acciones de escritura y exportación están desactivadas.
                </p>
            @endif

            <div class="overflow-x-auto rounded-lg border border-slate-200">
                <table class="min-w-[980px] w-full border-collapse text-left text-sm">
                    <thead class="bg-slate-100">
                        <tr class="text-[11px] uppercase tracking-wider text-slate-500">
                            <th scope="col" class="px-4 py-3 font-bold">Conductor e identidad</th>
                            <th scope="col" class="px-4 py-3 font-bold">Placa y vehículo</th>
                            <th scope="col" class="px-4 py-3 font-bold">Estado financiero</th>
                            <th scope="col" class="px-4 py-3 font-bold">Vigencia permiso / SOAT</th>
                            <th scope="col" class="px-4 py-3 font-bold">Acceso</th>
                            <th scope="col" class="px-4 py-3 text-right font-bold">Acciones rápidas</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse ($conductores as $conductor)
                            @php
                                $iniciales = collect(explode(' ', trim($conductor->nombre)))
                                    ->filter()
                                    ->take(2)
                                    ->map(fn ($nombre) => mb_substr($nombre, 0, 1))
                                    ->implode('');
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
                            <tr class="transition-colors {{ $habilitado ? 'hover:bg-slate-50' : 'bg-[#E31B23]/[0.025] hover:bg-[#E31B23]/[0.05]' }}">
                                <td class="px-4 py-3.5">
                                    <div class="flex items-center gap-3">
                                        <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-[#08183D]/[0.08] font-headline text-xs font-extrabold uppercase text-[#08183D] ring-1 ring-[#08183D]/10">
                                            {{ $iniciales }}
                                        </span>
                                        <div>
                                            <p class="max-w-64 truncate font-semibold text-slate-900">{{ $conductor->nombre }}</p>
                                            <p class="mt-0.5 text-xs text-slate-500">DNI {{ $conductor->dni ?: 'No registrado' }} <span class="px-0.5 text-slate-300">•</span> {{ $conductor->telefono_whatsapp ?: 'Sin teléfono' }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3.5">
                                    <div class="flex items-center gap-1.5">
                                        <span class="rounded bg-slate-100 px-2 py-1 font-mono text-xs font-bold tracking-wide text-slate-800">{{ $conductor->placa ?: 'Sin placa' }}</span>
                                        <span class="rounded bg-[#08183D]/[0.06] px-1.5 py-0.5 text-[10px] font-bold uppercase text-[#08183D]">
                                            {{ match ($conductor->tipo_flota) { 'propia_auto' => 'Flota propia', 'propia_combi' => 'Combi', default => 'Afiliado' } }}
                                        </span>
                                    </div>
                                    <p class="mt-1 text-xs text-slate-500">{{ $conductor->modelo_vehiculo ?: 'Vehículo no registrado' }}</p>
                                </td>
                                <td class="px-4 py-3.5">
                                    <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[11px] font-bold {{ (float) $conductor->deuda_comision > 0 ? 'bg-red-50 text-[#E31B23]' : 'bg-emerald-50 text-emerald-700' }}">
                                        <span class="h-1.5 w-1.5 rounded-full {{ (float) $conductor->deuda_comision > 0 ? 'bg-[#E31B23]' : 'bg-emerald-500' }}"></span>
                                        {{ (float) $conductor->deuda_comision > 0 ? 'Debe' : 'Al día' }} (S/ {{ number_format((float) $conductor->deuda_comision, 2) }})
                                    </span>
                                    @if ($pagoReciente?->estado === 'pendiente')
                                        <span class="mt-1 block text-[11px] font-medium text-amber-700">Pago en revisión</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3.5">
                                    @if ($conductor->fecha_vencimiento_permiso)
                                        <span class="font-semibold text-slate-800">
                                            {{ $conductor->fecha_vencimiento_permiso->format('d/m/Y') }}
                                        </span>
                                        @if ($porVencer)
                                            <span class="mt-0.5 block text-[11px] font-medium text-amber-700">{{ $permisoVigente ? 'Por vencer pronto' : 'Vencido' }}</span>
                                        @elseif (! $permisoVigente)
                                            <span class="mt-0.5 block text-[11px] font-medium text-[#E31B23]">Vencido</span>
                                        @else
                                            <span class="mt-0.5 block text-[11px] font-medium text-emerald-700">Vigente</span>
                                        @endif
                                    @else
                                        <span class="inline-flex rounded bg-red-50 px-2 py-1 text-[11px] font-bold text-[#E31B23]">No registrado</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3.5">
                                    @if ($porVencer && $conductor->estado_comision === 'al_dia')
                                        <x-badge-semaforo estado="alerta" texto="Permiso por vencer" />
                                    @elseif ($habilitado)
                                        <x-badge-semaforo estado="habilitado" texto="Habilitado" />
                                    @else
                                        <x-badge-semaforo estado="bloqueado" texto="Candado activo" />
                                    @endif
                                </td>
                                <td class="px-4 py-3.5 text-right">
                                    @if ($modoDemo)
                                        <span class="inline-flex items-center gap-1 text-[11px] font-semibold text-slate-400">
                                            <svg aria-hidden="true" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                                <rect x="4" y="10" width="16" height="11" rx="2"></rect><path d="M8 10V7a4 4 0 1 1 8 0v3"></path>
                                            </svg>
                                            Solo lectura
                                        </span>
                                    @else
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
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-12 text-center">
                                    <span class="mx-auto mb-3 flex h-11 w-11 items-center justify-center rounded-full bg-slate-100 text-slate-500">
                                        <svg aria-hidden="true" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                            <circle cx="10.8" cy="10.8" r="6.8"></circle><path d="m16 16 4.5 4.5"></path>
                                        </svg>
                                    </span>
                                    <span class="font-semibold text-[#08183D]">No encontramos conductores</span>
                                    <span class="mt-1 block text-xs text-slate-500">Prueba con otro nombre, DNI o placa.</span>
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
                                        @if ($modoDemo)
                                            <span class="text-xs font-semibold text-slate-400">Solo lectura</span>
                                        @else
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
                                        @endif
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
