@extends('layouts.torre-control')

@section('titulo', 'Turismo y Flota Propia - KALUZ')

@section('content')
    <div class="space-y-5">
        <!-- Cabecera y botón de creación -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="font-headline font-extrabold text-2xl text-[#08142C] tracking-tight">
                    Turismo y Prioridad de Flota Propia
                </h1>
                <p class="text-xs text-slate-600 mt-0.5">
                    Catálogo de rutas turísticas en el Valle del Mantaro con asignación prioritaria a Combis y Autos de Inversiones Kaluz
                </p>
            </div>
            <div class="flex items-center gap-2.5">
                <a href="{{ route('turismo.create', [], false) }}"
                   class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-[#08142C] hover:bg-[#13264E] text-white font-headline font-bold text-xs shadow-sm transition">
                    <i class="ri-add-line text-base"></i>
                    <span>Nuevo Paquete Turístico</span>
                </a>
            </div>
        </div>

        <!-- Resumen de Flota Propia y Prioridad -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bg-white/95 rounded-2xl p-4 border border-white shadow-sm flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-slate-500">Paquetes Activos</p>
                    <p class="font-headline font-black text-2xl text-[#08142C] mt-1">{{ $paquetes->count() }} Rutas</p>
                    <p class="text-[11px] text-emerald-600 font-semibold mt-0.5 flex items-center gap-1">
                        <i class="ri-whatsapp-line"></i> Disponibles en Bot WhatsApp
                    </p>
                </div>
                <div class="w-11 h-11 rounded-2xl bg-[#08142C]/5 text-[#08142C] flex items-center justify-center">
                    <i class="ri-compass-3-line text-2xl"></i>
                </div>
            </div>

            <div class="bg-white/95 rounded-2xl p-4 border border-white shadow-sm flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-slate-500">Regla de Asignación #3</p>
                    <p class="font-headline font-black text-xl text-[#08142C] mt-1">Prioridad Combis Kaluz</p>
                    <p class="text-[11px] text-slate-500 mt-0.5">100% margen directo para la empresa</p>
                </div>
                <div class="w-11 h-11 rounded-2xl bg-[#D91B23]/10 text-[#D91B23] flex items-center justify-center">
                    <i class="ri-bus-2-fill text-2xl"></i>
                </div>
            </div>

            <div class="bg-white/95 rounded-2xl p-4 border border-white shadow-sm flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-slate-500">Monitoreo de Pasajeros</p>
                    <p class="font-headline font-black text-xl text-[#08142C] mt-1">Rastreo GPS Web</p>
                    <p class="text-[11px] text-emerald-600 font-semibold mt-0.5 flex items-center gap-1">
                        <i class="ri-map-pin-user-fill"></i> Link directo sin descargas
                    </p>
                </div>
                <div class="w-11 h-11 rounded-2xl bg-emerald-500/10 text-emerald-600 flex items-center justify-center">
                    <i class="ri-road-map-line text-2xl"></i>
                </div>
            </div>
        </div>

        @if (session('exito'))
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-xs font-semibold text-emerald-800 shadow-xs flex items-center gap-2">
                <i class="ri-checkbox-circle-fill text-emerald-600 text-base"></i>
                <span>{{ session('exito') }}</span>
            </div>
        @endif

        <!-- Grid de Paquetes Turísticos -->
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">
            @forelse ($paquetes as $paquete)
                <article class="overflow-hidden rounded-3xl border border-white bg-white/95 shadow-sm hover:shadow-md transition flex flex-col justify-between">
                    @if ($paquete->imagen_url)
                        <div class="relative h-44 w-full overflow-hidden bg-slate-100">
                            <img src="{{ $paquete->imagen_url }}" alt="Portada de {{ $paquete->titulo }}" class="h-full w-full object-cover">
                            <div class="absolute inset-0 bg-gradient-to-t from-[#08142C]/65 via-transparent to-transparent"></div>
                            <div class="absolute top-3 right-3">
                                <span class="inline-flex items-center gap-1 rounded-full px-3 py-1 text-[11px] font-bold {{ $paquete->activo ? 'bg-emerald-500 text-white shadow-xs' : 'bg-slate-600 text-white' }}">
                                    <i class="{{ $paquete->activo ? 'ri-checkbox-blank-circle-fill text-[7px]' : 'ri-eye-off-line text-xs' }}"></i>
                                    <span>{{ $paquete->activo ? 'Activo' : 'Oculto' }}</span>
                                </span>
                            </div>
                            <div class="absolute bottom-3 left-4 right-4 flex items-center justify-between text-white">
                                <span class="inline-flex items-center gap-1 text-xs font-bold bg-[#08142C]/80 backdrop-blur-xs px-2.5 py-1 rounded-lg border border-white/15">
                                    <i class="ri-bus-2-line text-amber-300"></i>
                                    <span>Flota {{ $paquete->vehiculo_etiqueta ?? 'KALUZ' }}</span>
                                </span>
                            </div>
                        </div>
                    @else
                        <div class="flex h-36 items-center justify-center bg-[#08142C] text-white/70 gap-2 text-xs font-semibold">
                            <i class="ri-landscape-line text-xl"></i>
                            <span>Ruta Turística KALUZ</span>
                        </div>
                    @endif

                    <div class="p-5 flex-1 flex flex-col justify-between space-y-4">
                        <div>
                            <h3 class="text-base font-headline font-extrabold text-[#08142C] leading-snug">
                                {{ $paquete->titulo }}
                            </h3>
                            <p class="mt-1.5 text-xs text-slate-600 line-clamp-2 leading-relaxed">
                                {{ $paquete->descripcion }}
                            </p>
                        </div>

                        <!-- Métricas del Paquete con Iconos Reales -->
                        <div class="flex flex-wrap items-center gap-2 border-y border-slate-100 py-3 text-xs">
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-[#DCE6F2]/60 text-[#08142C] font-semibold">
                                <i class="ri-time-line text-sm"></i>
                                <span>{{ $paquete->duracion_horas }} horas</span>
                            </span>
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-red-50 text-[#D91B23] font-semibold">
                                <i class="ri-steering-2-line text-sm"></i>
                                <span>{{ $paquete->vehiculo_etiqueta ?? 'COMBI' }}</span>
                            </span>
                            @if(is_array($paquete->itinerario_paradas))
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-slate-100 text-slate-600 font-medium">
                                    <i class="ri-map-pin-2-line text-sm text-[#08142C]"></i>
                                    <span>{{ count($paquete->itinerario_paradas) }} paradas</span>
                                </span>
                            @endif
                        </div>

                        <!-- Paradas en miniatura -->
                        @if(is_array($paquete->itinerario_paradas) && count($paquete->itinerario_paradas) > 0)
                            <div class="flex items-center gap-1.5 text-[11px] text-slate-500 truncate">
                                <i class="ri-route-line text-[#08142C]"></i>
                                <span class="truncate">
                                    {{ collect($paquete->itinerario_paradas)->pluck('nombre')->implode(' → ') }}
                                </span>
                            </div>
                        @endif

                        <!-- Precio y Acciones -->
                        <div class="pt-1 flex items-center justify-between gap-2">
                            <div>
                                <span class="text-[10px] uppercase tracking-wider font-semibold text-slate-400 block">Tarifa cerrada</span>
                                <p class="text-2xl font-headline font-black text-[#08142C]">
                                    S/ {{ number_format($paquete->precio_cerrado, 2) }}
                                </p>
                            </div>

                            <div class="flex items-center gap-2">
                                <a href="{{ route('turismo.rastreo', ['id' => $paquete->id]) }}"
                                   class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl border border-slate-200 text-xs font-bold text-[#08142C] hover:bg-slate-50 transition"
                                   title="Ver Mapa GPS del Recorrido">
                                    <i class="ri-map-pin-2-fill text-[#D91B23]"></i>
                                    <span>Mapa GPS</span>
                                </a>
                                <a href="{{ route('turismo.edit', ['id' => $paquete->id], false) }}"
                                   class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-[#08142C] hover:bg-[#13264E] text-white text-xs font-bold transition shadow-xs">
                                    <i class="ri-edit-2-line"></i>
                                    <span>Editar</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </article>
            @empty
                <div class="col-span-full rounded-3xl border border-amber-200 bg-amber-50 p-8 text-center text-amber-800 space-y-2">
                    <i class="ri-bus-2-line text-3xl"></i>
                    <p class="font-headline font-bold text-base">Aún no hay paquetes turísticos registrados.</p>
                    <p class="text-xs text-amber-700">Crea uno nuevo usando el botón superior para asignarlo a la flota propia.</p>
                </div>
            @endforelse
        </div>
    </div>
@endsection
