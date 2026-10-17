@extends('layouts.pwa-movil')

@section('titulo', 'Candado Financiero Activo - Taxi Kaluz')

@section('content')
    <div class="bg-[#081328] border border-[#E31B23]/40 rounded-3xl p-6 text-center space-y-5 my-8 shadow-2xl">
        <div class="w-16 h-16 rounded-2xl bg-[#E31B23]/20 border border-[#E31B23]/40 text-[#E31B23] flex items-center justify-center mx-auto text-3xl font-bold shadow-lg">
            🔒
        </div>
        
        <div>
            <span class="text-xs font-label bg-[#E31B23]/20 text-[#E31B23] border border-[#E31B23]/40 px-3 py-1 rounded-full font-bold">
                CANDADO S/ 1.00 ACTIVO
            </span>
            <h2 class="font-headline text-2xl font-black text-white mt-3">Radar Bloqueado</h2>
            <p class="font-body text-xs text-slate-300 mt-2 px-2">
                {{ session('motivo_bloqueo', 'Tienes una comisión pendiente de S/ 1.00 diario o tu credencial operativa se encuentra temporalmente suspendida.') }}
            </p>
        </div>

        <div class="bg-[#08183D] border border-white/10 p-5 rounded-2xl text-left text-xs font-body text-slate-300 space-y-2">
            <p class="font-headline font-bold text-amber-300 flex items-center gap-1.5 text-sm">
                <span>⚡</span> Desbloqueo Automático con Yape
            </p>
            <p class="text-slate-300">1. Transfiere <strong>S/ 1.00</strong> al Yape oficial de Taxi Kaluz (964-XXX-XXX).</p>
            <p class="text-slate-300">2. Envía la captura del comprobante por WhatsApp o súbela aquí para validación instantánea.</p>
        </div>

        <div class="pt-2">
            <a href="https://wa.me/51964000000?text=Hola%20Taxi%20Kaluz,%20adjunto%20mi%20Yape%20de%20S/1.00" class="btn-accent w-full justify-center text-sm font-headline">
                <span>📲 Enviar Comprobante por WhatsApp</span>
            </a>
        </div>
    </div>
@endsection
