<?php

namespace App\Http\Controllers\Finanzas;

use App\Http\Controllers\Controller;
use App\Services\Finanzas\ValidacionCandadoService;
use App\Services\Supabase\Supabase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * ZONA DEV 2 - Controlador de Validación de Pagos Yape (Comisión de S/ 1.00)
 */
class ValidacionYapeController extends Controller
{
    public function __construct(
        protected ValidacionCandadoService $validacionCandadoService
    ) {}

    private function tabla(string $table)
    {
        if (extension_loaded('pdo_pgsql')) {
            try {
                DB::connection()->getPdo();

                return DB::table($table);
            } catch (\Throwable) {
                // Fallback a Supabase REST
            }
        }

        return Supabase::table($table);
    }

    /**
     * GET /finanzas/yape - Bandeja de Validación y Trazabilidad de Pagos Yape (S/ 1.00).
     */
    public function index(): View
    {
        $conductores = $this->tabla('conductores')->orderBy('id')->get();

        return view('finanzas.yape', [
            'conductores' => $conductores,
        ]);
    }

    /**
     * POST /finanzas/yape/validar - Aprueba o rechaza el pago Yape de S/ 1.00.
     */
    public function validarComprobante(Request $request): JsonResponse|RedirectResponse
    {
        $conductorId = $request->input('conductor_id');
        $accion = $request->input('accion', 'aprobar');
        $aprobar = $accion !== 'rechazar';

        if ($conductorId) {
            $this->tabla('conductores')->where('id', (int) $conductorId)->update([
                'habilitado' => $aprobar,
                'estado_comision' => $aprobar ? 'al_dia' : 'moroso',
                'fecha_vencimiento_comision' => $aprobar ? now()->addDay() : now()->subDay(),
                'updated_at' => now(),
            ]);
        }

        $mensaje = $aprobar
            ? 'Pago Yape de S/ 1.00 validado correctamente. Candado financiero desbloqueado.'
            : 'Comprobante Yape rechazado. El conductor permanece con el candado activo.';

        if ($request->expectsJson()) {
            return response()->json([
                'exito' => true,
                'aprobado' => $aprobar,
                'mensaje' => $mensaje,
            ]);
        }

        return back()->with('exito', $mensaje);
    }
}
