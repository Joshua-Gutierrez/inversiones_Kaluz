<?php

namespace App\Http\Controllers\Despacho;

use App\Http\Controllers\Controller;
use App\Services\Despacho\AsignacionAtomicaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * 👨‍💻 ZONA DEV 1 - Controlador de Subasta Atómica y Contraofertas
 */
class SubastaAtomicaController extends Controller
{
    public function __construct(
        protected AsignacionAtomicaService $asignacionAtomicaService
    ) {}

    public function ofertar(Request $request, int $solicitudId): JsonResponse
    {
        $conductorId = (int) $request->input('conductor_id', 1);
        $montoOferta = (float) $request->input('monto_oferta', 10.00);

        $resultado = $this->asignacionAtomicaService->asignarViajeAtomicamente(
            $solicitudId,
            $conductorId,
            $montoOferta
        );

        return response()->json($resultado, $resultado['exito'] ? 200 : 409);
    }
}
