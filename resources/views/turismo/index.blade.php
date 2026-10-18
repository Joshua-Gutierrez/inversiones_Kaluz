@extends('layouts.admin-tower')

@section('titulo', 'Planes de turismo - Taxi Kaluz')
@section('encabezado', 'Planes de turismo')

@section('content')
    <div class="space-y-6">
        <!-- Barra Superior de Acciones y Búsqueda -->
        <div class="flex flex-col md:flex-row items-center justify-between gap-4 bg-white p-4 rounded-2xl border border-slate-200/80 shadow-xs">
            <div>
                <h2 class="text-xl font-headline font-bold text-[#08183D]">Catálogo de Paquetes Turísticos</h2>
                <p class="text-xs font-body text-[#64748B]">Prioridad para combis y flota propia de Inversiones Kaluz</p>
            </div>
            <div class="flex items-center gap-3 w-full md:w-auto justify-end">
                <a href="{{ route('turismo.create', [], false) }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-[#E31B23] hover:bg-[#c9141c] text-white font-label font-bold text-sm shadow-md transition-all">
                    <span>+ Crear Paquete Turístico</span>
                </a>
            </div>
        </div>

        @if (session('exito'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-semibold text-emerald-800 shadow-xs flex items-center gap-2">
                <span>✅</span>
                <span>{{ session('exito') }}</span>
            </div>
        @endif

        <!-- Grid de Paquetes Dinámicos -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            @forelse ($paquetes as $paquete)
                <article class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-xs hover:shadow-md transition-all flex flex-col justify-between">
                    @if ($paquete->imagen_url)
                        <div class="relative h-48 w-full overflow-hidden bg-slate-100">
                            <img src="{{ $paquete->imagen_url }}" alt="Portada de {{ $paquete->titulo }}" class="h-full w-full object-cover">
                            <div class="absolute top-3 right-3">
                                <span class="rounded-full px-3 py-1 text-xs font-bold font-label {{ $paquete->activo ? 'bg-emerald-500 text-white shadow-sm' : 'bg-slate-400 text-white' }}">
                                    {{ $paquete->activo ? '● Activo' : '○ Oculto' }}
                                </span>
                            </div>
                        </div>
                    @else
                        <div class="flex h-36 items-center justify-center bg-slate-100 text-sm font-semibold text-slate-500">
                            Sin imagen de portada
                        </div>
                    @endif

                    <div class="p-5 flex-1 flex flex-col justify-between space-y-4">
                        <div>
                            <div class="flex items-start justify-between gap-3">
                                <h3 class="text-lg font-headline font-bold text-[#08183D] leading-snug">{{ $paquete->titulo }}</h3>
                            </div>
                            <p class="mt-2 text-sm font-body text-[#64748B] line-clamp-2">{{ $paquete->descripcion }}</p>
                        </div>

                        <!-- Métricas / Badges del Paquete -->
                        <div class="flex flex-wrap items-center gap-2 border-y border-slate-100 py-3 text-xs font-label">
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-blue-50 text-[#08183D] font-bold">
                                ⏱️ {{ $paquete->duracion_horas }} horas
                            </span>
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-red-50 text-[#E31B23] font-bold">
                                🚐 {{ $paquete->vehiculo_etiqueta ?? 'Flota Kaluz' }}
                            </span>
                            @if(is_array($paquete->itinerario_paradas))
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-slate-100 text-[#64748B] font-medium">
                                    📍 {{ count($paquete->itinerario_paradas) }} paradas
                                </span>
                            @endif
                        </div>

                        <div class="pt-2 flex items-center justify-between">
                            <div>
                                <span class="text-[11px] font-label uppercase tracking-wider text-[#64748B]">Tarifa cerrada</span>
                                <p class="text-2xl font-headline font-black text-[#08183D]">S/ {{ number_format($paquete->precio_cerrado, 2) }}</p>
                            </div>

                            <div class="flex items-center gap-2">
                                <a href="{{ route('turismo.rastreo', ['id' => $paquete->id]) }}" class="px-3 py-2 rounded-xl border border-slate-200 text-xs font-label font-bold text-[#08183D] hover:bg-slate-50 transition-colors" title="Ver Rastreo GPS">
                                    📍 GPS
                                </a>
                                <a href="{{ route('turismo.edit', ['id' => $paquete->id], false) }}" class="px-3.5 py-2 rounded-xl bg-[#08183D] hover:bg-[#0c245c] text-white text-xs font-label font-bold transition-all shadow-xs">
                                    ✏️ Editar
                                </a>
                            </div>
                        </div>
                    </div>
                </article>
            @empty
                <div class="col-span-full rounded-2xl border border-amber-200 bg-amber-50 p-8 text-center text-amber-800">
                    <p class="font-bold text-base">Aún no hay paquetes turísticos registrados.</p>
                    <p class="text-xs text-amber-700 mt-1">Crea uno nuevo usando el botón superior o ejecuta el seeder de paquetes.</p>
                </div>
            @endforelse
        </div>
    </div>
@endsection
