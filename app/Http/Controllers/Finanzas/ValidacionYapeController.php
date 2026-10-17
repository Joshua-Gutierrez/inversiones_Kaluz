<?php

namespace App\Http\Controllers\Finanzas;

use App\Http\Controllers\Controller;
use App\Models\PagoComision;
use App\Services\Finanzas\ValidacionCandadoService;
use Illuminate\Http\RedirectResponse;

/**
 * 👨‍💻 ZONA DEV 2 - Controlador de Validación de Pagos Yape (Comisión de S/ 1.00)
 */
class ValidacionYapeController extends Controller
{
    public function __construct(
        protected ValidacionCandadoService $validacionCandadoService
    ) {}

    public function validarComprobante(PagoComision $pagoComision): RedirectResponse
    {
        if (! $this->validacionCandadoService->aprobarPago($pagoComision->id)) {
            return back()->withErrors(['pago' => 'El comprobante ya fue procesado y no se puede validar nuevamente.']);
        }

        return back()->with('estado', 'Comprobante aprobado. Se actualizó la deuda del conductor.');
    }

    public function rechazarComprobante(PagoComision $pagoComision): RedirectResponse
    {
        if (! $this->validacionCandadoService->rechazarPago($pagoComision->id)) {
            return back()->withErrors(['pago' => 'El comprobante ya fue procesado y no se puede rechazar nuevamente.']);
        }

        return back()->with('estado', 'Comprobante rechazado. El conductor continúa bloqueado hasta registrar un pago válido.');
    }
}
