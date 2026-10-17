@extends('layouts.pwa-mobile')

@section('titulo', 'Cuenta Bloqueada - Taxi KALUZ')
@section('header_titulo', 'Taxi KALUZ')
@section('header_sub', 'Candado activo')

@section('content')
    <div class="bg-white rounded-2xl shadow-sm border border-red-300 p-6 text-center space-y-5 my-4">
        <div class="w-16 h-16 rounded-2xl bg-red-100 border border-red-300 text-red-600 flex items-center justify-center mx-auto text-3xl font-bold">
            🔒
        </div>

        <div>
            <span class="text-[10px] font-bold uppercase tracking-wide bg-red-100 text-red-800 border border-red-300 px-3 py-1 rounded-lg">
                Cuenta bloqueada
            </span>
            <h2 class="font-headline text-xl font-black text-[#0B1B3D] mt-3">No recibes viajes</h2>
            <p class="text-sm text-slate-600 mt-2">
                {{ $motivo }}
            </p>
        </div>

        <div class="bg-[#F4F5F7] border border-slate-200 p-4 rounded-2xl text-left text-xs text-slate-600 space-y-2">
            <p class="font-headline font-bold text-[#0B1B3D] text-sm">⚡ ¿Cómo te desbloqueas?</p>
            <p>1. Paga tu comisión diaria de <strong>S/ 1.00</strong> al Yape de Inversiones Kaluz.</p>
            <p>2. Envía la captura del comprobante por WhatsApp para validación.</p>
            <p>3. Al ser aprobada, tu candado se libera automáticamente y vuelves al radar.</p>
        </div>

        <a href="https://wa.me/51964000000?text=Hola%20Taxi%20Kaluz,%20adjunto%20mi%20comprobante%20de%20pago%20de%20S/1.00"
            class="block w-full bg-[#E31B23] hover:bg-red-700 text-white font-bold rounded-xl shadow-md py-3.5 text-sm">
            📲 Enviar comprobante por WhatsApp
        </a>

        <form action="{{ route('despacho.salir') }}" method="POST">
            @csrf
            <button type="submit" class="text-xs font-bold text-slate-400 py-2">
                Cambiar de conductor
            </button>
        </form>
    </div>
@endsection
