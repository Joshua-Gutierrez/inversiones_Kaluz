@extends('layouts.pwa-movil')

@section('titulo', 'Candado Financiero Activo - Taxi Kaluz')

@section('content')
    <div class="bg-red-950/60 border border-red-500/50 rounded-2xl p-6 text-center space-y-4 my-8">
        <div class="w-16 h-16 rounded-full bg-red-500/20 text-red-400 flex items-center justify-center mx-auto text-3xl font-bold">
            🔒
        </div>
        <h2 class="text-xl font-extrabold text-white">Radar Bloqueado</h2>
        <p class="text-sm text-red-200">
            {{ session('motivo_bloqueo', 'Tienes una comisión pendiente de S/ 1.00 o tu permiso se encuentra vencido.') }}
        </p>
        <div class="bg-slate-900/90 p-4 rounded-xl text-left text-xs text-slate-300 space-y-1">
            <p class="font-bold text-amber-400">¿Cómo desbloquear inmediatamente?</p>
            <p>1. Yapea <strong>S/ 1.00</strong> al número oficial de Taxi Kaluz.</p>
            <p>2. Sube la captura del comprobante para habilitar tu radar al instante.</p>
        </div>
    </div>
@endsection
