<?php

namespace App\Http\Controllers\Finanzas;

use App\Http\Controllers\Controller;
use App\Models\Conductor;
use App\Models\PagoComision;
use App\Services\Finanzas\ComprobanteStorageException;
use App\Services\Finanzas\ComprobanteStorageService;
use App\Services\Finanzas\ValidacionCandadoService;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RegistroPagoController extends Controller
{
    public function store(
        Request $request,
        ComprobanteStorageService $comprobanteStorage,
        ValidacionCandadoService $validacionCandadoService
    ): RedirectResponse {
        $conductorId = $request->session()->get('conductor_id');

        if (! is_scalar($conductorId) || ! ctype_digit((string) $conductorId)) {
            return redirect()->route('despacho.pwa.bloqueado')
                ->withErrors(['pago' => 'No se encontró una sesión válida de conductor para registrar el pago.']);
        }

        $datos = $request->validate([
            'metodo_pago' => ['required', 'in:yape,plin'],
            'comprobante' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        try {
            DB::transaction(function () use (
                $conductorId,
                $datos,
                $comprobanteStorage,
                $validacionCandadoService
            ): void {
                $conductor = Conductor::query()->lockForUpdate()->find($conductorId);

                if (! $conductor) {
                    throw ValidationException::withMessages([
                        'pago' => 'No se encontró el conductor de la sesión actual.',
                    ]);
                }

                if (! today()->isLastOfMonth()) {
                    throw ValidationException::withMessages([
                        'pago' => 'La cuota mensual se liquida al cierre del mes. Podrás registrar el pago el último día.',
                    ]);
                }

                $diasPendientes = $validacionCandadoService->diasDisponiblesParaPago($conductor);

                if ($diasPendientes < 1) {
                    throw ValidationException::withMessages([
                        'pago' => 'No hay una cuota pendiente para registrar en este mes.',
                    ]);
                }

                $rutaComprobante = $comprobanteStorage->subir(
                    $datos['comprobante'],
                    $conductor->id,
                    now()
                );

                try {
                    PagoComision::query()->create([
                        'conductor_id' => $conductor->id,
                        'monto' => $diasPendientes,
                        'dias_pagados' => $diasPendientes,
                        'metodo_pago' => $datos['metodo_pago'],
                        'comprobante_url' => $rutaComprobante,
                        'estado' => 'pendiente',
                    ]);
                } catch (QueryException $exception) {
                    $comprobanteStorage->eliminar($rutaComprobante);
                    throw $exception;
                }
            });
        } catch (ComprobanteStorageException $exception) {
            return back()->withErrors(['comprobante' => $exception->getMessage()]);
        }

        return redirect()->route('despacho.pwa.bloqueado')
            ->with('estado', 'Comprobante recibido. El administrador revisará el pago para actualizar tu estado.');
    }
}
