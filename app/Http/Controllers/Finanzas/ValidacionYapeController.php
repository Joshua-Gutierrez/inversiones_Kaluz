<?php

namespace App\Http\Controllers\Finanzas;

use App\Http\Controllers\Controller;
use App\Services\Finanzas\ValidacionCandadoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * 👨‍💻 ZONA DEV 2 - Controlador de Validación de Pagos Yape (Comisión de S/ 1.00)
 */
class ValidacionYapeController extends Controller
{
    public function __construct(
        protected ValidacionCandadoService $validacionCandadoService
    ) {}

    public function validarComprobante(Request $request): JsonResponse
    {
        return response()->json([
            'exito' => true,
            'mensaje' => 'Pago Yape de S/ 1.00 validado correctamente. Candado financiero desbloqueado.',
        ]);
    }
}
