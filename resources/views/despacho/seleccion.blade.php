@extends('layouts.pwa-mobile')

@section('titulo', 'Acceso de Conductor - Taxi KALUZ')
@section('tema', 'oscuro')

@section('header_centro')
    <span class="inline-flex items-center gap-1.5 text-[11px] font-headline font-bold uppercase tracking-wide px-3 py-1 rounded-full bg-white/10 text-slate-200 border border-white/15">
        <i class="ri-Taxi-line text-emerald-400"></i>
        FLOTA HUANCAYO
    </span>
@endsection

@section('content')
    <div x-data="{ busqueda: '' }" class="space-y-4">

        <div>
            <h1 class="font-headline font-extrabold text-2xl text-white">Mi Cuenta KALUZ</h1>
            <p class="text-xs text-slate-400 mt-0.5">Ingresa con tu WhatsApp o selecciona tu unidad de la flota</p>
        </div>

        @if (session('demo_ok'))
            <div class="bg-emerald-500/15 text-emerald-300 border border-emerald-500/30 rounded-2xl px-4 py-3 text-xs font-medium flex items-center gap-2">
                <i class="ri-checkbox-circle-fill text-base text-emerald-400"></i>
                <span>{{ session('demo_ok') }}</span>
            </div>
        @endif

        {{-- Ingreso rápido por WhatsApp --}}
        <div class="bg-[#111E3B] rounded-2xl border border-white/10 p-4 space-y-3 shadow-lg">
            <div class="flex items-center gap-2">
                <i class="ri-whatsapp-line text-lg text-emerald-400"></i>
                <div>
                    <h2 class="font-headline font-bold text-sm text-white">Ingreso por WhatsApp</h2>
                    <p class="text-[11px] text-slate-400">Tu teléfono registrado en el padrón de Inversiones Kaluz</p>
                </div>
            </div>

            <form action="{{ route('despacho.entrar') }}" method="POST" class="flex gap-2">
                @csrf
                <input
                    type="tel"
                    name="telefono_whatsapp"
                    value="{{ old('telefono_whatsapp') }}"
                    placeholder="Ej: 964 111 222"
                    class="flex-1 min-w-0 bg-[#081124] border border-white/15 rounded-xl px-3.5 py-2.5 text-sm text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-[#E31B23]"
                    required
                >
                <button type="submit"
                    class="bg-[#D91B23] hover:bg-[#B9151C] text-white font-bold rounded-xl shadow-md px-4 py-2.5 text-xs whitespace-nowrap flex items-center gap-1.5">
                    <i class="ri-login-box-line text-sm"></i>
                    <span>Entrar</span>
                </button>
            </form>

            @error('telefono_whatsapp')
                <p class="text-xs text-red-400 flex items-center gap-1">
                    <i class="ri-error-warning-line"></i>
                    <span>{{ $message }}</span>
                </p>
            @enderror
        </div>

        {{-- Lista de conductores de la flota --}}
        <div class="bg-[#111E3B] rounded-2xl border border-white/10 p-4 space-y-3 shadow-lg">
            <div class="flex items-center justify-between">
                <h2 class="font-headline font-bold text-sm text-white flex items-center gap-1.5">
                    <i class="ri-steering-2-line text-slate-300"></i>
                    <span>Seleccionar Conductor</span>
                </h2>
                <span class="text-[10px] font-bold uppercase tracking-wide bg-white/10 text-slate-300 px-2.5 py-0.5 rounded-full">
                    {{ count($conductores) }} unidades
                </span>
            </div>

            <div class="relative">
                <i class="ri-search-line absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
                <input
                    type="search"
                    x-model="busqueda"
                    placeholder="Buscar por nombre o placa..."
                    class="w-full bg-[#081124] border border-white/15 rounded-xl pl-9 pr-3 py-2 text-xs text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-white/20"
                >
            </div>

            <ul class="space-y-2 max-h-[380px] overflow-y-auto pr-0.5">
                @forelse ($conductores as $c)
                    <li x-show="{{ json_encode(mb_strtolower($c->nombre . ' ' . $c->placa)) }}.includes(busqueda.toLowerCase())">
                        <form action="{{ route('despacho.entrar') }}" method="POST">
                            @csrf
                            <input type="hidden" name="conductor_id" value="{{ $c->id }}">
                            <button type="submit"
                                class="w-full text-left flex items-center gap-3 bg-[#081124]/80 hover:bg-[#16274C] border border-white/10 rounded-xl px-3 py-2.5 transition">
                                <span class="w-9 h-9 shrink-0 rounded-full {{ $c->habilitado ? 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/40' : 'bg-red-500/20 text-red-300 border border-red-500/40' }} flex items-center justify-center font-headline font-bold text-xs">
                                    {{ mb_substr($c->nombre, 0, 1) }}
                                </span>
                                <span class="flex-1 min-w-0">
                                    <span class="block font-headline font-bold text-xs text-white truncate">{{ $c->nombre }}</span>
                                    <span class="block text-[11px] text-slate-400 truncate">{{ $c->placa }} • {{ $c->modelo_vehiculo }}</span>
                                </span>
                                <span class="shrink-0 inline-flex items-center gap-1 text-[10px] font-bold px-2.5 py-1 rounded-full
                                    {{ $c->habilitado
                                        ? 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/30'
                                        : 'bg-red-500/20 text-red-300 border border-red-500/30' }}">
                                    <i class="{{ $c->habilitado ? 'ri-checkbox-blank-circle-fill text-[7px] text-emerald-400' : 'ri-lock-fill text-[10px] text-red-400' }}"></i>
                                    {{ $c->habilitado ? 'HABILITADO' : 'BLOQUEADO' }}
                                </span>
                            </button>
                        </form>
                    </li>
                @empty
                    <li class="text-center text-xs text-slate-400 py-6 space-y-3">
                        <p>No hay conductores registrados aún.</p>
                        <a href="{{ route('despacho.demo.sembrar-conductores') }}"
                           class="inline-flex items-center gap-1.5 bg-[#E31B23] text-white font-bold rounded-xl px-4 py-2.5 text-xs shadow-sm">
                            <i class="ri-database-2-line"></i>
                            <span>Sembrar 8 conductores de prueba</span>
                        </a>
                    </li>
                @endforelse
            </ul>
        </div>
    </div>
@endsection
