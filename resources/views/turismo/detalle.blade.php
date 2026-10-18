@extends('layouts.pwa-mobile')

@section('titulo', $paquete->titulo . ' - Taxi Kaluz')

@section('content')
    <div class="min-h-screen -m-4 bg-[#F4F5F7] p-4 text-slate-800" x-data="{ paradaActiva: 0 }">
        <a href="{{ route('turismo.index', [], false) }}" class="inline-flex text-sm font-bold text-[#0B1B3D]">Volver a paquetes</a>

        <section class="mt-4 rounded-2xl bg-[#0B1B3D] p-5 text-white shadow-md">
            <span class="inline-flex rounded-full border border-white/30 bg-white/10 px-2.5 py-1 text-xs font-bold">{{ $paquete->vehiculo_etiqueta }} propio</span>
            <h1 class="mt-3 text-2xl font-bold">{{ $paquete->titulo }}</h1>
            <div class="mt-4 flex items-center justify-between text-sm text-slate-200">
                <span>{{ $paquete->duracion_horas }} horas</span>
                <span>{{ count($paquete->itinerario_paradas) }} paradas</span>
                <span class="font-bold text-white">S/ {{ number_format($paquete->precio_cerrado, 2) }}</span>
            </div>
        </section>

        <section class="mt-5 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div id="mapa-itinerario" class="h-64 w-full"></div>
        </section>

        <section class="mt-5 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="text-lg font-bold text-[#0B1B3D]">Itinerario multiparada</h2>
            <ol class="mt-5 space-y-1">
                @foreach ($paquete->itinerario_paradas as $indice => $parada)
                    <li class="relative flex gap-4 pb-5 last:pb-0">
                        <span class="absolute left-4 top-8 h-full w-px bg-slate-200 last:hidden"></span>
                        <button type="button" x-on:click="paradaActiva = {{ $indice }}" class="relative z-10 flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-sm font-bold" :class="paradaActiva === {{ $indice }} ? 'bg-[#E31B23] text-white' : 'bg-slate-200 text-slate-700'">{{ $indice + 1 }}</button>
                        <div class="pt-1">
                            <p class="font-semibold text-slate-800">{{ $parada['nombre'] }}</p>
                            <p x-show="paradaActiva === {{ $indice }}" x-cloak class="mt-1 text-sm text-slate-500">Parada {{ $indice + 1 }} del recorrido.</p>
                        </div>
                    </li>
                @endforeach
            </ol>
        </section>

        <section id="reserva" class="mt-5 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="text-lg font-bold text-[#0B1B3D]">Reserva tu tour</h2>
            <p class="mt-1 text-sm text-slate-500">Para grupos de mas de 4 pasajeros se asigna combi propia.</p>
            <form action="{{ route('turismo.reservar', [], false) }}" method="POST" class="mt-4 space-y-4">
                @csrf
                <input type="hidden" name="paquete_id" value="{{ $paquete->id }}">
                <label class="block text-sm font-semibold text-slate-700">
                    Nombre completo
                    <input type="text" name="cliente_nombre" value="{{ old('cliente_nombre') }}" required class="mt-1 w-full rounded-xl border-slate-300 text-slate-800 focus:border-[#0B1B3D] focus:ring-[#0B1B3D]">
                    @error('cliente_nombre') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                </label>
                <label class="block text-sm font-semibold text-slate-700">
                    WhatsApp
                    <input type="tel" name="cliente_telefono" value="{{ old('cliente_telefono') }}" required class="mt-1 w-full rounded-xl border-slate-300 text-slate-800 focus:border-[#0B1B3D] focus:ring-[#0B1B3D]">
                    @error('cliente_telefono') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                </label>
                <div class="grid grid-cols-2 gap-3">
                    <label class="block text-sm font-semibold text-slate-700">
                        Fecha y hora
                        <input type="datetime-local" name="fecha_hora" value="{{ old('fecha_hora') }}" required class="mt-1 w-full rounded-xl border-slate-300 text-slate-800 focus:border-[#0B1B3D] focus:ring-[#0B1B3D]">
                        @error('fecha_hora') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                    </label>
                    <label class="block text-sm font-semibold text-slate-700">
                        Pasajeros
                        <input x-model.number="pasajeros" type="number" name="pasajeros" min="1" max="20" value="{{ old('pasajeros', 1) }}" required class="mt-1 w-full rounded-xl border-slate-300 text-slate-800 focus:border-[#0B1B3D] focus:ring-[#0B1B3D]">
                    </label>
                </div>
                <p x-show="pasajeros > 4" x-cloak class="rounded-xl border border-amber-300 bg-amber-100 p-3 text-sm font-medium text-amber-800">Por capacidad, se solicitara una combi propia.</p>
                <button type="submit" class="flex w-full items-center justify-center bg-[#E31B23] rounded-xl px-4 py-3 font-bold text-white shadow-md hover:bg-red-700">Confirmar reserva</button>
            </form>
        </section>
    </div>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIINfQWBnrfZ9tD/miZyoHS5obTRR9BMY=" crossorigin="">
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const paradas = @json($paquete->itinerario_paradas);
            const mapa = L.map('mapa-itinerario', { scrollWheelZoom: false });
            const ruta = paradas.map((parada) => [parada.lat, parada.lng]);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; OpenStreetMap contributors',
            }).addTo(mapa);

            paradas.forEach((parada, indice) => {
                L.marker([parada.lat, parada.lng])
                    .addTo(mapa)
                    .bindPopup(`<strong>${indice + 1}. ${parada.nombre}</strong>`);
            });

            L.polyline(ruta, { color: '#E31B23', weight: 4 }).addTo(mapa);
            mapa.fitBounds(ruta, { padding: [24, 24] });
        });
    </script>
@endsection
