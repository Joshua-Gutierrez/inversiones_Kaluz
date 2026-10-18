<?php

namespace App\Http\Middleware;

use App\Services\Finanzas\ValidacionCandadoService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Bloquea el acceso a la PWA del conductor si:
 * 1. Tiene comisiones diarias pendientes.
 * 2. Tiene su permiso municipal o SOAT vencido.
 */
class CandadoFinancieroMiddleware
{
    public function __construct(
        protected ValidacionCandadoService $validacionCandadoService
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $conductorId = $request->session()->get('conductor_id');

        if (! $conductorId || ! ctype_digit((string) $conductorId)) {
            return $this->bloquear($request, 'No se pudo identificar una sesión válida de conductor.');
        }

        $evaluacion = $this->validacionCandadoService->verificarEstadoConductor((int) $conductorId);

        if (! $evaluacion['habilitado']) {
            return $this->bloquear($request, $evaluacion['motivo'], (float) $evaluacion['deuda']);
        }

        return $next($request);
    }

    private function bloquear(Request $request, string $motivo, float $deuda = 0): Response
    {
        if ($request->expectsJson()) {
            return response()->json([
                'bloqueado' => true,
                'motivo' => $motivo,
                'deuda_pendiente' => $deuda,
            ], 403);
        }

        return redirect()->route('despacho.pwa.bloqueado')->with('motivo_bloqueo', $motivo);
    }
}
