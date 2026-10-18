@extends('layouts.admin-tower')

@section('titulo', 'Planes de turismo - Taxi Kaluz')
@section('encabezado', 'Planes de turismo')

@section('content')
    <div class="mx-auto max-w-6xl space-y-6">
        <section class="flex flex-col justify-between gap-4 rounded-2xl bg-[#0B1B3D] p-6 text-white shadow-md md:flex-row md:items-center">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.18em] text-red-300">Catalogo para WhatsApp</p>
                <h2 class="mt-2 text-2xl font-bold">Administra los planes que vera el chatbot</h2>
                <p class="mt-2 text-sm text-slate-200">Solo los planes activos deben ser enviados a los clientes.</p>
            </div>
            <a href="{{ route('turismo.create', [], false) }}" class="inline-flex items-center justify-center rounded-xl bg-[#E31B23] px-5 py-3 font-bold text-white shadow-md hover:bg-red-700">Crear plan</a>
        </section>

        @if (session('exito'))
            <div class="rounded-xl border border-emerald-300 bg-emerald-100 p-4 text-sm font-semibold text-emerald-800">{{ session('exito') }}</div>
        @endif

        <section class="grid grid-cols-1 gap-5 lg:grid-cols-2">
            @forelse ($paquetes as $paquete)
                <article class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                    @if ($paquete->imagen_url)
                        <img src="{{ $paquete->imagen_url }}" alt="Portada de {{ $paquete->titulo }}" class="h-44 w-full object-cover">
                    @else
                        <div class="flex h-44 items-center justify-center bg-slate-100 text-sm font-semibold text-slate-500">Sin portada para WhatsApp</div>
                    @endif
                    <div class="space-y-4 p-5">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <h3 class="text-lg font-bold text-[#0B1B3D]">{{ $paquete->titulo }}</h3>
                                <p class="mt-1 text-sm text-slate-600">{{ $paquete->descripcion }}</p>
                            </div>
                            <span class="shrink-0 rounded-full border px-2.5 py-1 text-xs font-bold {{ $paquete->activo ? 'border-emerald-300 bg-emerald-100 text-emerald-800' : 'border-amber-300 bg-amber-100 text-amber-800' }}">{{ $paquete->activo ? 'Activo' : 'Oculto' }}</span>
                        </div>
                        <div class="flex flex-wrap gap-3 border-y border-slate-100 py-3 text-sm text-slate-600">
                            <span>{{ $paquete->duracion_horas }} h</span>
                            <span>S/ {{ number_format($paquete->precio_cerrado, 2) }}</span>
                            <span>{{ count($paquete->itinerario_paradas) }} paradas</span>
                            <span>{{ $paquete->vehiculo_etiqueta }}</span>
                        </div>
                        <a href="{{ route('turismo.edit', ['id' => $paquete->id], false) }}" class="inline-flex w-full items-center justify-center rounded-xl border-2 border-[#0B1B3D] px-4 py-3 font-bold text-[#0B1B3D] hover:bg-slate-50">Editar plan</a>
                    </div>
                </article>
            @empty
                <div class="rounded-2xl border border-amber-300 bg-amber-100 p-5 text-amber-800">Aun no hay planes creados.</div>
            @endforelse
        </section>
    </div>
@endsection
