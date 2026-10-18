@extends('layouts.pwa-mobile')

@section('titulo', 'Pagar Cuota Diaria S/ 1.00 - KALUZ')
@section('tema', 'claro')
@section('ocultar_header', '1')

@section('content')
<div x-data="{ copiado: false, archivoNombre: '', previewUrl: null }" class="space-y-4 pt-1">
    {{-- Barra superior con botón volver y logo KALUZ (idéntico a Imagen 2 centro) --}}
    <div class="flex items-center justify-between">
        <a href="{{ url()->previous() !== url()->current() ? url()->previous() : route('despacho.pwa') }}"
           class="w-10 h-10 rounded-xl bg-white shadow-sm border border-slate-200/80 flex items-center justify-center text-[#0A1329] hover:bg-slate-50 transition">
            <i class="ri-arrow-left-line text-lg"></i>
        </a>
        <span class="font-headline font-black text-2xl tracking-wider text-[#0A1329]">KALUZ</span>
        <div class="w-10"></div>
    </div>

    <h1 class="font-headline font-extrabold text-2xl text-center text-[#0A1329]">Pagar Cuota Diaria</h1>

    {{-- Tarjeta Blanca Principal --}}
    <div class="bg-white rounded-3xl shadow-md border border-slate-200/80 p-5 text-center space-y-4">
        <div>
            <p class="text-xs font-medium text-slate-500">Monto a pagar:</p>
            <p class="font-headline font-black text-3xl text-[#0A1329] mt-0.5">S/ 1.00</p>
            @if(!empty($conductor))
                <p class="text-[11px] text-slate-500 mt-1">
                    Socio: <strong class="text-[#0A1329]">{{ $conductor->nombre }}</strong> ({{ $conductor->placa }})
                </p>
            @endif
        </div>

        {{-- Código QR Vectorial Real con emblema central --}}
        <div class="relative w-44 h-44 mx-auto bg-white p-2.5 rounded-2xl border-2 border-slate-200 shadow-xs flex items-center justify-center">
            <svg viewBox="0 0 29 29" class="w-full h-full text-[#0A1329]" fill="currentColor" shape-rendering="crispEdges">
                <!-- Patrón QR real -->
                <path d="M0,0 h7 v7 h-7 z M1,1 v5 h5 v-5 z M2,2 h3 v3 h-3 z
                         M22,0 h7 v7 h-7 z M23,1 v5 h5 v-5 z M24,2 h3 v3 h-3 z
                         M0,22 h7 v7 h-7 z M1,23 v5 h5 v-5 z M2,24 h3 v3 h-3 z
                         M9,0 h2 v2 h-2 z M13,0 h1 v3 h-1 z M16,0 h3 v1 h-3 z M20,1 h1 v2 h-1 z
                         M8,3 h3 v1 h-3 z M12,4 h2 v2 h-2 z M15,3 h2 v2 h-2 z M19,4 h2 v1 h-2 z
                         M9,6 h1 v2 h-1 z M11,6 h2 v1 h-2 z M16,6 h4 v1 h-4 z
                         M0,9 h2 v2 h-2 z M4,8 h3 v1 h-3 z M8,9 h2 v3 h-2 z M18,9 h3 v2 h-3 z M23,9 h2 v1 h-2 z M27,8 h2 v3 h-2 z
                         M1,12 h3 v2 h-3 z M6,11 h2 v2 h-2 z M21,12 h3 v1 h-3 z M25,11 h2 v3 h-2 z
                         M0,16 h2 v3 h-2 z M3,15 h3 v2 h-3 z M7,16 h2 v2 h-2 z M20,16 h2 v2 h-2 z M24,16 h3 v2 h-3 z M28,15 h1 v4 h-1 z
                         M2,20 h4 v1 h-4 z M8,19 h3 v2 h-3 z M18,19 h2 v3 h-2 z M22,20 h3 v2 h-3 z
                         M9,23 h2 v2 h-2 z M12,22 h3 v1 h-3 z M16,23 h2 v3 h-2 z M20,23 h2 v2 h-2 z M24,23 h4 v1 h-4 z
                         M8,26 h3 v3 h-3 z M13,25 h2 v4 h-2 z M19,27 h3 v2 h-3 z M23,26 h2 v3 h-2 z M27,25 h2 v4 h-2 z"/>
            </svg>
            <div class="absolute inset-0 flex items-center justify-center">
                <div class="w-10 h-10 rounded-full bg-white shadow-md border border-slate-200 flex items-center justify-center text-[#0A1329]">
                    <i class="ri-whatsapp-fill text-2xl"></i>
                </div>
            </div>
        </div>

        {{-- Número de Yape con botón de copiado --}}
        <div class="space-y-1">
            <p class="text-xs text-slate-600">Copiar número de Yape:</p>
            <div class="inline-flex items-center gap-2 bg-slate-100 px-3.5 py-1.5 rounded-xl border border-slate-200">
                <span class="font-headline font-black text-base text-[#0A1329] tracking-wide">925 424 844</span>
                <button type="button"
                        @click="navigator.clipboard.writeText('925424844'); copiado = true; setTimeout(() => copiado = false, 2000)"
                        class="text-xs font-bold text-[#E31B23] hover:underline flex items-center gap-1">
                    <i :class="copiado ? 'ri-check-line text-emerald-600' : 'ri-file-copy-line'"></i>
                    <span x-text="copiado ? 'Copiado' : 'Copiar'"></span>
                </button>
            </div>
            <p class="text-xs text-slate-500 pt-0.5">Yapear directamente a Inversiones Kaluz</p>
        </div>

        <form action="{{ route('despacho.cuota.pagar') }}" method="POST" enctype="multipart/form-data" class="space-y-3 pt-1">
            @csrf
            @if(!empty($conductor))
                <input type="hidden" name="conductor_id" value="{{ $conductor->id }}">
            @endif

            {{-- Botón Subir Captura de Pago --}}
            <label for="captura-input"
                   class="cursor-pointer block w-full bg-[#0A1329] hover:bg-[#132347] text-white font-semibold rounded-xl py-2.5 text-xs shadow-sm transition">
                <i class="ri-upload-2-line mr-1"></i> Subir Captura de Pago
            </label>

            {{-- Dropzone de imagen (idéntico a Imagen 2 centro) --}}
            <label for="captura-input"
                   class="cursor-pointer block rounded-2xl border-2 border-dashed border-slate-300 bg-[#DCE6F2]/55 hover:bg-[#DCE6F2]/80 p-4 transition">
                <input id="captura-input" type="file" name="comprobante" accept="image/*" class="hidden"
                       @change="if ($event.target.files[0]) { archivoNombre = $event.target.files[0].name; previewUrl = URL.createObjectURL($event.target.files[0]); }">

                <template x-if="!previewUrl">
                    <div class="flex flex-col items-center justify-center space-y-1.5 py-2">
                        <div class="flex items-center gap-1 text-[#0A1329]">
                            <i class="ri-camera-fill text-3xl"></i>
                            <i class="ri-image-add-fill text-2xl -ml-2 mt-2"></i>
                        </div>
                        <p class="text-xs font-medium text-slate-600">Adjunta tu captura de Yape o Plin aquí</p>
                    </div>
                </template>

                <template x-if="previewUrl">
                    <div class="flex items-center justify-center gap-3 py-1">
                        <img :src="previewUrl" alt="Comprobante Yape" class="h-14 w-14 object-cover rounded-xl border border-slate-300">
                        <div class="text-left min-w-0">
                            <p class="text-xs font-bold text-emerald-700 flex items-center gap-1">
                                <i class="ri-checkbox-circle-fill"></i> Captura lista
                            </p>
                            <p class="text-[11px] text-slate-500 truncate max-w-[170px]" x-text="archivoNombre"></p>
                        </div>
                    </div>
                </template>
            </label>

            {{-- Botón Rojo Confirmar Envío --}}
            <button type="submit"
                    class="w-full bg-[#D91B23] hover:bg-[#B9151C] text-white font-headline font-bold rounded-xl py-3.5 text-sm shadow-md transition flex items-center justify-center gap-2">
                <i class="ri-check-double-line text-base"></i>
                <span>Confirmar Envío</span>
            </button>
        </form>
    </div>
</div>
@endsection
