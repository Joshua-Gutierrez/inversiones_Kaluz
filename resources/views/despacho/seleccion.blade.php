@extends('layouts.pwa-mobile')

@section('titulo', 'Entrar como Conductor - Taxi KALUZ')
@section('header_titulo', 'Bienvenido, chofer')
@section('header_sub', 'Elige tu perfil para el radar')

@section('content')
    <div x-data="{ busqueda: '' }" class="space-y-4">

        @if (session('demo_ok'))
            <div class="bg-emerald-100 text-emerald-800 border border-emerald-300 rounded-2xl px-4 py-3 text-sm font-body">
                {{ session('demo_ok') }}
            </div>
        @endif

        {{-- Ingreso rápido por WhatsApp --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-4">
            <h2 class="font-headline font-bold text-base mb-1">Ingresa tu número</h2>
            <p class="text-xs text-slate-500 mb-3">Tu número de WhatsApp registrado con Kaluz.</p>

            <form action="{{ route('despacho.entrar') }}" method="POST" class="flex gap-2">
                @csrf
                <input
                    type="tel"
                    name="telefono_whatsapp"
                    value="{{ old('telefono_whatsapp') }}"
                    placeholder="964 123 456"
                    class="flex-1 min-w-0 border border-slate-300 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#0B1B3D] @error('telefono_whatsapp') border-red-400 @enderror"
                    required
                >
                <button type="submit"
                    class="bg-[#E31B23] hover:bg-red-700 text-white font-bold rounded-xl shadow-md px-4 py-2.5 text-sm whitespace-nowrap">
                    Entrar
                </button>
            </form>

            @error('telefono_whatsapp')
                <p class="text-xs text-red-600 mt-2">{{ $message }}</p>
            @enderror
        </div>

        {{-- Lista de conductores (demo) --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-4">
            <div class="flex items-center justify-between mb-3">
                <h2 class="font-headline font-bold text-base">O elige de la flota</h2>
                <span class="text-[10px] font-bold uppercase tracking-wide bg-[#F4F5F7] text-slate-500 border border-slate-200 px-2 py-0.5 rounded-full">Demo</span>
            </div>

            <input
                type="search"
                x-model="busqueda"
                placeholder="Buscar por nombre o placa..."
                class="w-full border border-slate-300 rounded-xl px-3 py-2.5 text-sm mb-3 focus:outline-none focus:ring-2 focus:ring-[#0B1B3D]"
            >

            <ul class="space-y-2 max-h-[420px] overflow-y-auto">
                @forelse ($conductores as $c)
                    <li x-show="{{ json_encode(mb_strtolower($c->nombre . ' ' . $c->placa)) }}.includes(busqueda.toLowerCase())">
                        <form action="{{ route('despacho.entrar') }}" method="POST">
                            @csrf
                            <input type="hidden" name="conductor_id" value="{{ $c->id }}">
                            <button type="submit"
                                class="w-full text-left flex items-center gap-3 border border-slate-200 rounded-xl px-3 py-3 hover:border-[#0B1B3D] hover:bg-[#F4F5F7] transition @unless($c->habilitado) opacity-60 @endunless">
                                <span class="w-10 h-10 shrink-0 rounded-xl {{ $c->habilitado ? 'bg-[#0B1B3D]' : 'bg-red-600' }} text-white flex items-center justify-center font-headline font-bold text-sm">
                                    {{ mb_substr($c->nombre, 0, 1) }}
                                </span>
                                <span class="flex-1 min-w-0">
                                    <span class="block font-headline font-bold text-sm truncate">{{ $c->nombre }}</span>
                                    <span class="block text-[11px] text-slate-500 truncate">{{ $c->placa }} • {{ $c->modelo_vehiculo }}</span>
                                </span>
                                <span class="shrink-0 text-[10px] font-bold px-2 py-1 rounded-lg border
                                    {{ $c->habilitado
                                        ? 'bg-emerald-100 text-emerald-800 border-emerald-300'
                                        : 'bg-red-100 text-red-800 border-red-300' }}">
                                    {{ $c->habilitado ? ($c->estado_comision === 'al_dia' ? 'AL DÍA' : 'OK') : 'BLOQUEADO' }}
                                </span>
                            </button>
                        </form>
                    </li>
                @empty
                    <li class="text-center text-sm text-slate-400 py-6">
                        <p class="mb-3">No hay conductores registrados aún.</p>
                        <a href="{{ route('despacho.demo.sembrar-conductores') }}"
                           class="inline-block bg-[#0B1B3D] text-white font-bold rounded-xl px-4 py-2.5 text-xs shadow-sm">
                            Sembrar 8 conductores de prueba
                        </a>
                    </li>
                @endforelse
            </ul>
        </div>

        <p class="text-[11px] text-slate-400 text-center pb-2">
            Taxi KALUZ • Inversiones Kaluz E.I.R.L. • Huancayo
        </p>
    </div>
@endsection
