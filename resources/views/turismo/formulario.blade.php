@extends('layouts.admin-tower')

@section('titulo', ($paquete ? 'Editar' : 'Crear') . ' plan turistico - Taxi Kaluz')
@section('encabezado', $paquete ? 'Editar plan turistico' : 'Crear plan turistico')

@section('content')
    <div class="mx-auto max-w-4xl">
        <form action="{{ $paquete ? route('turismo.update', ['id' => $paquete->id], false) : route('turismo.store', [], false) }}" method="POST" enctype="multipart/form-data" class="space-y-6" x-data="{ paradas: @js(old('paradas', $paradas)) }">
            @csrf
            @if ($paquete) @method('PUT') @endif

            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-bold text-[#0B1B3D]">Informacion para el chatbot</h2>
                <div class="mt-5 grid grid-cols-1 gap-5 md:grid-cols-2">
                    <label class="block text-sm font-semibold text-slate-700 md:col-span-2">Nombre del plan
                        <input name="titulo" value="{{ old('titulo', $paquete?->titulo) }}" required class="mt-1 w-full rounded-xl border-slate-300 focus:border-[#0B1B3D] focus:ring-[#0B1B3D]">
                    </label>
                    <label class="block text-sm font-semibold text-slate-700 md:col-span-2">Descripcion breve
                        <textarea name="descripcion" rows="3" required class="mt-1 w-full rounded-xl border-slate-300 focus:border-[#0B1B3D] focus:ring-[#0B1B3D]">{{ old('descripcion', $paquete?->descripcion) }}</textarea>
                    </label>
                    <label class="block text-sm font-semibold text-slate-700 md:col-span-2">Foto de portada
                        <input type="file" name="imagen" accept="image/jpeg,image/png,image/webp" class="mt-1 block w-full rounded-xl border border-slate-300 bg-white text-sm text-slate-700 file:mr-4 file:rounded-l-xl file:border-0 file:bg-[#0B1B3D] file:px-4 file:py-2 file:font-bold file:text-white hover:file:bg-slate-800">
                        <span class="mt-1 block text-xs font-normal text-slate-500">Selecciona JPG, PNG o WEBP de hasta 5 MB. Esta imagen se enviara junto al plan en WhatsApp.</span>
                        @if ($paquete?->imagen_url)
                            <img src="{{ $paquete->imagen_url }}" alt="Portada actual" class="mt-3 h-32 w-48 rounded-xl object-cover">
                        @endif
                        @error('imagen') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                    </label>
                    <label class="block text-sm font-semibold text-slate-700">Duracion en horas
                        <input type="number" name="duracion_horas" min="1" max="24" value="{{ old('duracion_horas', $paquete?->duracion_horas) }}" required class="mt-1 w-full rounded-xl border-slate-300">
                    </label>
                    <label class="block text-sm font-semibold text-slate-700">Precio cerrado (S/)
                        <input type="number" name="precio_cerrado" min="0" step="0.01" value="{{ old('precio_cerrado', $paquete?->precio_cerrado) }}" required class="mt-1 w-full rounded-xl border-slate-300">
                    </label>
                    <label class="block text-sm font-semibold text-slate-700">Vehiculo recomendado
                        <select name="vehiculo_recomendado" class="mt-1 w-full rounded-xl border-slate-300">
                            <option value="propia_auto" @selected(old('vehiculo_recomendado', $paquete?->vehiculo_recomendado) === 'propia_auto')>Auto propio</option>
                            <option value="propia_combi" @selected(old('vehiculo_recomendado', $paquete?->vehiculo_recomendado) === 'propia_combi')>Combi propia</option>
                        </select>
                    </label>
                    <label class="flex items-center gap-3 text-sm font-semibold text-slate-700"><input type="hidden" name="activo" value="0"><input type="checkbox" name="activo" value="1" @checked(old('activo', $paquete?->activo ?? true)) class="rounded border-slate-300 text-[#E31B23] focus:ring-[#E31B23]">Visible para el chatbot</label>
                </div>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="flex items-center justify-between gap-4"><div><h2 class="text-lg font-bold text-[#0B1B3D]">Itinerario y coordenadas</h2><p class="mt-1 text-sm text-slate-500">El mapa y rastreo usan estas paradas.</p></div><button type="button" x-on:click="paradas.push({ nombre: '', lat: '', lng: '' })" class="rounded-xl border-2 border-[#0B1B3D] px-3 py-2 text-sm font-bold text-[#0B1B3D]">Agregar parada</button></div>
                <div class="mt-5 space-y-3">
                    <template x-for="(parada, indice) in paradas" :key="indice"><div class="grid grid-cols-1 gap-3 rounded-xl bg-slate-50 p-4 md:grid-cols-[2fr_1fr_1fr_auto]"><input x-model="parada.nombre" :name="`paradas[${indice}][nombre]`" placeholder="Lugar" required class="rounded-xl border-slate-300"><input x-model="parada.lat" :name="`paradas[${indice}][lat]`" type="number" step="0.000001" placeholder="Latitud" required class="rounded-xl border-slate-300"><input x-model="parada.lng" :name="`paradas[${indice}][lng]`" type="number" step="0.000001" placeholder="Longitud" required class="rounded-xl border-slate-300"><button type="button" x-on:click="paradas.splice(indice, 1)" x-show="paradas.length > 2" class="rounded-xl px-3 text-sm font-bold text-red-700">Quitar</button></div></template>
                </div>
            </section>

            <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end"><a href="{{ route('turismo.index', [], false) }}" class="rounded-xl px-5 py-3 text-center font-bold text-slate-700">Cancelar</a><button type="submit" class="rounded-xl bg-[#E31B23] px-5 py-3 font-bold text-white shadow-md hover:bg-red-700">{{ $paquete ? 'Guardar cambios' : 'Crear plan' }}</button></div>
        </form>
    </div>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
@endsection
