<?php

namespace App\Http\Controllers\Despacho;

use App\Http\Controllers\Controller;
use App\Models\Conductor;
use App\Services\Finanzas\ValidacionCandadoService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * 👨‍💻 ZONA DEV 1 - Controlador de la PWA del Conductor (Radar de viajes y estados)
 */
class ConductorPwaController extends Controller
{
    public function index(Request $request): View
    {
        return view('despacho.radar');
    }

    public function bloqueado(ValidacionCandadoService $validacionCandadoService): View
    {
        $conductorId = session('conductor_id');
        $conductor = is_scalar($conductorId) && ctype_digit((string) $conductorId)
            ? Conductor::query()->find((int) $conductorId)
            : null;
        $diasPendientes = $conductor
            ? $validacionCandadoService->diasDisponiblesParaPago($conductor)
            : 0;
        $pagoPendiente = $conductor
            ? $validacionCandadoService->pagoPendienteDelMes($conductor)
            : null;

        return view('despacho.bloqueado', [
            'conductor' => $conductor,
            'diasPendientes' => $diasPendientes,
            'pagoPendiente' => $pagoPendiente,
            'mediosPago' => array_filter([
                'yape' => config('finanzas.yape_destino'),
                'plin' => config('finanzas.plin_destino'),
            ]),
            'puedeRegistrarPago' => $conductor
                && today()->isLastOfMonth()
                && $diasPendientes > 0
                && ! $pagoPendiente,
        ]);
    }
}
