<?php

namespace App\Http\Controllers\Finanzas;

use App\Http\Controllers\Controller;
use App\Models\PagoComision;
use App\Services\Finanzas\ComprobanteStorageException;
use App\Services\Finanzas\ComprobanteStorageService;
use App\Services\Finanzas\ValidacionCandadoService;
use Illuminate\Http\RedirectResponse;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirectResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * 👨‍💻 ZONA DEV 2 - Controlador de Validación de Pagos Yape (Comisión de S/ 1.00)
 */
class ValidacionYapeController extends Controller
{
    public function __construct(
        protected ValidacionCandadoService $validacionCandadoService
    ) {}

    public function verComprobante(
        PagoComision $pagoComision,
        ComprobanteStorageService $comprobanteStorage
    ): SymfonyRedirectResponse {
        $ruta = $pagoComision->comprobante_url;

        if (! is_string($ruta) || trim($ruta) === '') {
            abort(404, 'Este pago no tiene un comprobante registrado.');
        }

        if (
            filter_var($ruta, FILTER_VALIDATE_URL)
            && in_array(parse_url($ruta, PHP_URL_SCHEME), ['http', 'https'], true)
        ) {
            return redirect()->away($ruta);
        }

        try {
            return redirect()->away($comprobanteStorage->urlFirmada($ruta));
        } catch (ComprobanteStorageException $exception) {
            throw new HttpException(503, $exception->getMessage(), $exception);
        }
    }

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

        return back()->with(
            'estado',
            'Comprobante rechazado. El pago no reduce la cuota; el candado se evalúa al cierre del mes y según la vigencia documental.'
        );
    }
}
