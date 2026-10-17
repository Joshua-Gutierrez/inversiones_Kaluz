<?php

namespace App\Http\Middleware;

use App\Services\Finanzas\ValidacionCandadoService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Bloquea el acceso a la PWA del conductor si:
 * 1. Debe S/ 1.00 o más de comisión por viaje anterior.
 * 2. Tiene su permiso municipal o SOAT vencido.
 */
class CandadoFinancieroMiddleware
{
    public function __construct(
        protected ValidacionCandadoService $validacionCandadoService
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $conductorId = $request->session()->get('conductor_id') ?? $request->query('conductor_id');

        if ($conductorId) {
            $evaluacion = $this->validacionCandadoService->verificarEstadoConductor((int) $conductorId);

            if (! $evaluacion['habilitado']) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'bloqueado' => true,
                        'motivo' => $evaluacion['motivo'],
                        'deuda_pendiente' => $evaluacion['deuda'] ?? 1.00,
                    ], 403);
                }

                return redirect()->route('despacho.pwa.bloqueado')->with('motivo_bloqueo', $evaluacion['motivo']);
            }
        }

        return $next($request);
    }
}
