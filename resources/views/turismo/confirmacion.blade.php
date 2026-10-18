@extends('layouts.pwa-mobile')

@section('titulo', 'Reserva confirmada - Taxi Kaluz')

@section('content')
    <div class="min-h-screen -m-4 bg-[#F4F5F7] p-4 text-slate-800">
        <section class="rounded-2xl bg-[#0B1B3D] p-6 text-center text-white shadow-md">
            <p class="text-sm font-bold text-emerald-300">Reserva registrada</p>
            <h1 class="mt-2 text-2xl font-bold">Tu tour esta en proceso</h1>
            <p class="mt-3 text-sm text-slate-200">Salida solicitada: {{ \Illuminate\Support\Carbon::parse($fechaHora)->format('d/m/Y H:i') }}</p>
        </section>

        <section class="mt-5 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-sm font-semibold text-slate-500">Estado de unidad</p>
            <p class="mt-2 font-bold text-[#0B1B3D]">{{ $asignacion['mensaje'] }}</p>
            @if ($asignacion['conductor'] !== null)
                <div class="mt-4 rounded-xl bg-emerald-100 p-4 text-emerald-800">
                    <p class="font-bold">{{ $asignacion['conductor']->nombre }}</p>
                    <p class="mt-1 text-sm">{{ $asignacion['conductor']->modelo_vehiculo }} · {{ $asignacion['conductor']->placa }}</p>
                </div>
            @endif
        </section>

        <a href="{{ route('turismo.track', ['id' => $viajeId], false) }}" class="mt-5 flex w-full items-center justify-center bg-[#E31B23] rounded-xl px-4 py-3 font-bold text-white shadow-md hover:bg-red-700">Ver rastreo del viaje</a>
    </div>
@endsection
