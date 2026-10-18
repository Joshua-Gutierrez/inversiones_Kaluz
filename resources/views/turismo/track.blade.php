@extends('layouts.pwa-mobile')

@section('titulo', 'Rastreo en vivo - Taxi Kaluz')

@section('content')
    <div class="min-h-screen -m-4 bg-[#F4F5F7] text-slate-800" x-data="{ finalizado: {{ in_array($viaje->estado, ['completado', 'cancelado']) ? 'true' : 'false' }} }">
        <section class="bg-[#0B1B3D] p-5 text-white">
            <p class="text-xs font-bold uppercase tracking-[0.18em] text-red-300">Taxi Kaluz</p>
            <h1 class="mt-2 text-xl font-bold">Rastreo de tu viaje</h1>
            <p class="mt-1 text-sm text-slate-200">Actualizacion en vivo de la unidad asignada.</p>
        </section>

        <div id="mapa-rastreo" class="h-[54vh] min-h-80 w-full"></div>

        <section class="-mt-4 relative mx-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-md">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <p class="text-xs font-bold uppercase text-slate-500">Estado del viaje</p>
                    <p id="estado-viaje" class="mt-1 font-bold text-[#0B1B3D]">{{ ucfirst($viaje->estado) }}</p>
                </div>
                <span id="badge-estado" class="rounded-full border px-3 py-1 text-xs font-bold {{ in_array($viaje->estado, ['completado', 'cancelado']) ? 'border-red-300 bg-red-100 text-red-800' : 'border-emerald-300 bg-emerald-100 text-emerald-800' }}">{{ $viaje->estado }}</span>
            </div>
            @if ($viaje->conductor_id !== null)
                <div class="mt-4 border-t border-slate-100 pt-4">
                    <p id="nombre-conductor" class="font-bold text-[#0B1B3D]">{{ $viaje->nombre }}</p>
                    <p id="vehiculo-conductor" class="mt-1 text-sm text-slate-600">{{ $viaje->modelo_vehiculo }} · {{ $viaje->placa }}</p>
                    <a href="https://wa.me/{{ preg_replace('/\D/', '', $viaje->telefono_whatsapp) }}" class="mt-4 flex w-full items-center justify-center bg-[#E31B23] rounded-xl px-4 py-3 font-bold text-white shadow-md hover:bg-red-700">Llamar / WhatsApp al conductor</a>
                </div>
            @else
                <p class="mt-4 rounded-xl border border-amber-300 bg-amber-100 p-3 text-sm text-amber-800">Estamos buscando una unidad habilitada para tu viaje.</p>
            @endif
            @if ($modoDemo)
                <button id="simular-recorrido" type="button" class="mt-4 flex w-full items-center justify-center rounded-xl border-2 border-[#0B1B3D] px-4 py-3 font-bold text-[#0B1B3D]">Simular recorrido</button>
            @endif
        </section>
    </div>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIINfQWBnrfZ9tD/miZyoHS5obTRR9BMY=" crossorigin="">
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const paradas = @json($paradas);
            const posicionUrl = @json(route('turismo.posicion', ['id' => $viaje->id], false));
            const simularUrl = @json(route('turismo.simular', ['id' => $viaje->id], false));
            const mapa = L.map('mapa-rastreo', { scrollWheelZoom: false });
            let usuarioMovioMapa = false;
            let marcadorVehiculo;
            let polling;

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '&copy; OpenStreetMap contributors' }).addTo(mapa);
            L.polyline(paradas.map((parada) => [parada.lat, parada.lng]), { color: '#E31B23', weight: 4 }).addTo(mapa);
            paradas.forEach((parada, indice) => L.marker([parada.lat, parada.lng]).addTo(mapa).bindPopup(`${indice + 1}. ${parada.nombre}`));
            mapa.fitBounds(paradas.map((parada) => [parada.lat, parada.lng]), { padding: [24, 24] });
            mapa.on('dragstart zoomstart', () => { usuarioMovioMapa = true; });

            const moverVehiculo = (lat, lng) => {
                if (lat === null || lng === null) return;
                const destino = L.latLng(lat, lng);
                if (!marcadorVehiculo) {
                    marcadorVehiculo = L.circleMarker(destino, { radius: 10, color: '#0B1B3D', fillColor: '#E31B23', fillOpacity: 1, weight: 4 }).addTo(mapa).bindPopup('Unidad KALUZ');
                } else {
                    marcadorVehiculo.setLatLng(destino);
                }
                if (!usuarioMovioMapa) mapa.panTo(destino, { animate: true, duration: 0.8 });
            };

            const actualizar = async () => {
                const respuesta = await fetch(posicionUrl);
                if (!respuesta.ok) return;
                const datos = await respuesta.json();
                moverVehiculo(datos.lat, datos.lng);
                document.getElementById('estado-viaje').textContent = datos.estado.charAt(0).toUpperCase() + datos.estado.slice(1);
                document.getElementById('badge-estado').textContent = datos.estado;
                if (['completado', 'cancelado'].includes(datos.estado)) clearInterval(polling);
            };

            actualizar();
            if (!{{ in_array($viaje->estado, ['completado', 'cancelado']) ? 'true' : 'false' }}) polling = setInterval(actualizar, 4000);

            document.getElementById('simular-recorrido')?.addEventListener('click', async () => {
                await fetch(simularUrl, { method: 'POST', headers: { 'X-CSRF-TOKEN': @json(csrf_token()), 'Accept': 'application/json' } });
                actualizar();
            });
        });
    </script>
@endsection
