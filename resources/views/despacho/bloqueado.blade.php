@extends('layouts.pwa-movil')

@section('titulo', 'Acceso bloqueado - Taxi Kaluz')

@section('content')
    <div class="bg-[#081328] border border-[#E31B23]/40 rounded-3xl p-6 text-center space-y-5 my-8 shadow-2xl">
        <div class="w-16 h-16 rounded-2xl bg-[#E31B23]/20 border border-[#E31B23]/40 text-[#E31B23] flex items-center justify-center mx-auto text-3xl font-bold shadow-lg">
            🔒
        </div>

        <div>
            <span class="text-xs font-label bg-[#E31B23]/20 text-[#E31B23] border border-[#E31B23]/40 px-3 py-1 rounded-full font-bold">
                ACCESO TEMPORALMENTE BLOQUEADO
            </span>
            <h2 class="font-headline text-2xl font-black text-white mt-3">Radar Bloqueado</h2>
            <p class="font-body text-xs text-slate-300 mt-2 px-2">
                {{ session('motivo_bloqueo', 'Tu permiso municipal/SOAT está vencido o tienes una cuota mensual vencida.') }}
            </p>
        </div>

        @if (session('estado'))
            <p role="status" class="rounded-xl border border-emerald-400/30 bg-emerald-500/10 px-4 py-3 text-left text-sm text-emerald-200">
                {{ session('estado') }}
            </p>
        @endif

        @if ($errors->any())
            <div role="alert" class="rounded-xl border border-red-400/30 bg-red-500/10 px-4 py-3 text-left text-sm text-red-200">
                <ul class="list-inside list-disc space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if ($conductor && $diasPendientes > 0)
            <div class="bg-[#08183D] border border-white/10 p-5 rounded-2xl text-left text-sm font-body text-slate-300 space-y-3">
                <div>
                    <p class="font-headline font-bold text-amber-300">Cuota del mes</p>
                    <p class="mt-1">{{ $diasPendientes }} {{ $diasPendientes === 1 ? 'día' : 'días' }} pendientes · S/ {{ number_format($diasPendientes, 2) }}</p>
                </div>

                @if ($pagoPendiente)
                    <p class="rounded-lg border border-amber-300/30 bg-amber-300/10 px-3 py-2 text-amber-100">
                        Tu comprobante por S/ {{ number_format((float) $pagoPendiente->monto, 2) }} está pendiente de validación administrativa.
                    </p>
                @elseif ($puedeRegistrarPago && count($mediosPago) > 0)
                    <div class="rounded-lg border border-white/10 bg-white/5 px-3 py-2 text-xs">
                        <p class="font-semibold text-slate-100">Paga solo a una cuenta oficial:</p>
                        @foreach ($mediosPago as $medio => $destino)
                            <p class="mt-1">{{ ucfirst($medio) }}: <span class="font-bold text-white">{{ $destino }}</span></p>
                        @endforeach
                    </div>
                    <form method="POST" action="{{ route('finanzas.pagos.store') }}" enctype="multipart/form-data" class="space-y-3">
                        @csrf
                        <label class="block text-xs font-semibold text-slate-200">
                            Medio de pago
                            <select name="metodo_pago" required class="mt-1 min-h-11 w-full rounded-xl border border-slate-300 bg-white px-3 text-sm text-[#08183D]">
                                @foreach ($mediosPago as $medio => $destino)
                                    <option value="{{ $medio }}" @selected(old('metodo_pago', array_key_first($mediosPago)) === $medio)>
                                        {{ ucfirst($medio) }} · {{ $destino }}
                                    </option>
                                @endforeach
                            </select>
                        </label>
                        <label class="block text-xs font-semibold text-slate-200">
                            Captura del comprobante (JPG, PNG o WEBP; máximo 5 MB)
                            <input type="file" name="comprobante" accept="image/jpeg,image/png,image/webp" required
                                class="mt-1 block min-h-11 w-full rounded-xl border border-white/20 bg-white/5 px-3 py-2 text-xs text-white file:mr-3 file:rounded-lg file:border-0 file:bg-white file:px-3 file:py-1.5 file:font-semibold file:text-[#08183D]">
                        </label>
                        <button type="submit" class="w-full rounded-xl bg-[#E31B23] px-4 py-3 text-sm font-bold text-white transition hover:bg-red-700">
                            Enviar comprobante por S/ {{ number_format($diasPendientes, 2) }}
                        </button>
                    </form>
                    <p class="text-xs text-slate-400">El acceso se habilita cuando administración apruebe el comprobante.</p>
                @elseif ($puedeRegistrarPago)
                    <p class="rounded-lg border border-amber-300/30 bg-amber-300/10 px-3 py-2 text-xs text-amber-100">
                        El destino oficial de Yape o Plin aún no está configurado. No transfieras hasta que administración publique la cuenta correcta.
                    </p>
                @else
                    <p class="rounded-lg border border-white/10 bg-white/5 px-3 py-2 text-xs text-slate-300">
                        La cuota se liquida el último día del mes. Ese día podrás adjuntar el comprobante aquí.
                    </p>
                @endif
            </div>
        @elseif (! $conductor)
            <p class="rounded-xl border border-amber-300/30 bg-amber-300/10 px-4 py-3 text-left text-sm text-amber-100">
                Inicia sesión con la cuenta vinculada a tu registro de conductor para consultar tu cuota y enviar un comprobante.
            </p>
        @endif
    </div>
@endsection
