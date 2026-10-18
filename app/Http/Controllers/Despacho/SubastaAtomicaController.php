<?php

namespace App\Http\Controllers\Despacho;

use App\Http\Controllers\Controller;
use App\Services\Despacho\AtomicAssignmentService;
use App\Services\Supabase\Supabase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;

/**
 * 👨‍💻 ZONA DEV 1 - API de Subasta Atómica (JSON para la PWA del conductor)
 *
 * Formato consistente de respuesta: { ok: bool, mensaje: string, data: ... }
 * El polling de la PWA llama a estas rutas cada 3 segundos.
 */
class SubastaAtomicaController extends Controller
{
    public function __construct(
        protected AtomicAssignmentService $asignacion
    ) {}

    /**
     * GET /conductor/api/solicitudes - Lista en vivo para el polling del radar.
     */
    public function solicitudes(Request $request): JsonResponse
    {
        $conductorId = $this->conductorId($request);

        if (! $conductorId) {
            return response()->json(['ok' => false, 'mensaje' => 'Conductor no identificado.', 'data' => null], 401);
        }

        $conductor = $this->asignacion->obtenerConductor($conductorId);

        if (! $conductor) {
            return response()->json(['ok' => false, 'mensaje' => 'Conductor no encontrado.', 'data' => null], 404);
        }

        $solicitudes = $this->asignacion->solicitudesDisponibles($conductor);
        $viajeActivo = $this->asignacion->viajeActivoDe($conductorId);

        return response()->json([
            'ok' => true,
            'mensaje' => 'Solicitudes al día.',
            'data' => [
                'solicitudes' => $solicitudes->map(fn ($s) => $this->formaSolicitud($s))->values(),
                'viaje_activo' => $viajeActivo ? $this->formaSolicitud($viajeActivo) : null,
                'en_linea' => (bool) $request->input('en_linea', true),
            ],
        ]);
    }

    /**
     * POST /conductor/api/solicitudes/{id}/aceptar - Aceptar al precio del cliente (atómico).
     */
    public function aceptar(Request $request, int $solicitudId): JsonResponse
    {
        $conductorId = $this->conductorId($request);

        if (! $conductorId) {
            return response()->json(['ok' => false, 'mensaje' => 'Conductor no identificado.', 'data' => null], 401);
        }

        $resultado = $this->asignacion->aceptarViaje($solicitudId, $conductorId);

        if ($resultado['ok'] && $resultado['data']) {
            $viajeObj = $resultado['data'];
            $conductor = $this->asignacion->obtenerConductor($conductorId);

            // FLUJO 1 - Paso 5A: Si el chofer acepta directamente la tarifa del cliente
            if (! empty($viajeObj->cliente_telefono) && $conductor) {
                try {
                    $telefonoWa = preg_replace('/\D+/', '', (string) $viajeObj->cliente_telefono);
                    if (strlen($telefonoWa) === 9) {
                        $telefonoWa = '51'.$telefonoWa;
                    }
                    $precioFmt = number_format((float) ($viajeObj->precio_final ?? $viajeObj->oferta_cliente), 2, '.', '');

                    Http::withoutVerifying()
                        ->withToken((string) env('META_WA_BEARER_TOKEN'))
                        ->timeout(8)
                        ->post('https://graph.facebook.com/v20.0/'.env('META_WA_PHONE_NUMBER_ID').'/messages', [
                            'messaging_product' => 'whatsapp',
                            'to' => $telefonoWa,
                            'type' => 'text',
                            'text' => [
                                'body' => "🎉 ¡Viaje confirmado!\n\n".
                                    "🚗 Conductor: {$conductor->nombre}\n".
                                    "🚙 Vehículo: {$conductor->modelo_vehiculo} ({$conductor->placa})\n".
                                    "💰 Tarifa acordada: S/ {$precioFmt}\n\n".
                                    'El conductor ya tiene tu ubicación y está en camino. ¡Gracias por usar KALUZ!',
                            ],
                        ]);
                } catch (\Throwable) {
                    // Continuar sin interrumpir la asignación
                }
            }

            $resultado['data'] = $this->formaSolicitud($resultado['data']);
        }

        return response()->json($resultado, $resultado['ok'] ? 200 : 409);
    }

    /**
     * POST /conductor/api/solicitudes/{id}/contraoferta - Enviar contraoferta estilo inDrive.
     */
    public function contraofertar(Request $request, int $solicitudId): JsonResponse
    {
        $conductorId = $this->conductorId($request);

        if (! $conductorId) {
            return response()->json(['ok' => false, 'mensaje' => 'Conductor no identificado.', 'data' => null], 401);
        }

        $datos = $request->validate([
            'monto' => ['required', 'numeric', 'min:1', 'max:999'],
            'tiempo_llegada_min' => ['required', 'integer', 'min:1', 'max:120'],
        ], [], ['monto' => 'monto', 'tiempo_llegada_min' => 'tiempo de llegada']);

        $resultado = $this->asignacion->contraofertar(
            $solicitudId,
            $conductorId,
            (float) $datos['monto'],
            (int) $datos['tiempo_llegada_min']
        );

        // FLUJO 1 - Paso 4: Enviar botones interactivos por WhatsApp al pasajero cuando el conductor lanza contraoferta
        if ($resultado['ok']) {
            try {
                $solicitud = Supabase::table('solicitudes_viaje')->where('id', $solicitudId)->first();
                $conductor = $this->asignacion->obtenerConductor($conductorId);

                if ($solicitud && ! empty($solicitud->cliente_telefono) && $conductor) {
                    $telefonoWa = preg_replace('/\D+/', '', (string) $solicitud->cliente_telefono);
                    if (strlen($telefonoWa) === 9) {
                        $telefonoWa = '51'.$telefonoWa;
                    }

                    if (function_exists('enviarContraofertaInteractiva')) {
                        enviarContraofertaInteractiva(
                            $telefonoWa,
                            (string) $conductor->nombre,
                            (float) $datos['monto'],
                            (string) ($conductor->placa ?? 'W3X-451')
                        );
                    }
                }
            } catch (\Throwable) {
                // Continuar sin interrumpir respuesta JSON al radar
            }
        }

        return response()->json($resultado, $resultado['ok'] ? 200 : 409);
    }

    /**
     * GET /conductor/api/viaje-activo - JSON del viaje actual del conductor.
     */
    public function viajeActivo(Request $request): JsonResponse
    {
        $conductorId = $this->conductorId($request);

        if (! $conductorId) {
            return response()->json(['ok' => false, 'mensaje' => 'Conductor no identificado.', 'data' => null], 401);
        }

        $viaje = $this->asignacion->viajeActivoDe($conductorId);

        return response()->json([
            'ok' => true,
            'mensaje' => $viaje ? 'Viaje activo encontrado.' : 'No tienes viaje activo.',
            'data' => $viaje ? $this->formaSolicitud($viaje) : null,
        ]);
    }

    /**
     * POST /conductor/api/viaje/{id}/estado - Cambiar estado (en_ruta / completado / cancelado).
     */
    public function cambiarEstadoViaje(Request $request, int $solicitudId): JsonResponse
    {
        $conductorId = $this->conductorId($request);

        if (! $conductorId) {
            return response()->json(['ok' => false, 'mensaje' => 'Conductor no identificado.', 'data' => null], 401);
        }

        $datos = $request->validate([
            'estado' => ['required', 'in:en_ruta,completado,cancelado'],
        ], [], ['estado' => 'estado']);

        $resultado = $this->asignacion->actualizarEstadoViaje($solicitudId, $conductorId, $datos['estado']);

        if ($resultado['ok'] && $resultado['data']) {
            $resultado['data'] = $this->formaSolicitud($resultado['data']);
        }

        return response()->json($resultado, $resultado['ok'] ? 200 : 409);
    }

    /**
     * POST /conductor/api/ubicacion - Actualizar GPS (la PWA envía su posición cada ~5 s).
     */
    public function ubicacion(Request $request): JsonResponse
    {
        $conductorId = $this->conductorId($request);

        if (! $conductorId) {
            return response()->json(['ok' => false, 'mensaje' => 'Conductor no identificado.', 'data' => null], 401);
        }

        $datos = $request->validate([
            'lat' => ['required', 'numeric', 'between:-90,90'],
            'lng' => ['required', 'numeric', 'between:-180,180'],
        ], [], ['lat' => 'latitud', 'lng' => 'longitud']);

        $ok = $this->asignacion->actualizarUbicacion($conductorId, (float) $datos['lat'], (float) $datos['lng']);

        return response()->json([
            'ok' => $ok,
            'mensaje' => $ok ? 'Ubicación registrada.' : 'No se pudo registrar la ubicación.',
            'data' => ['lat' => (float) $datos['lat'], 'lng' => (float) $datos['lng']],
        ], $ok ? 200 : 500);
    }

    /**
     * POST /conductor/api/ofertas/{id}/aceptar - Aceptar una contraoferta.
     * La llama el Bot de Dev 4 cuando el cliente responde "acepto" por WhatsApp.
     * No requiere sesión de conductor (es la acción del pasajero).
     */
    public function aceptarContraoferta(int $ofertaId): JsonResponse
    {
        $resultado = $this->asignacion->aceptarContraoferta($ofertaId);

        if ($resultado['ok'] && $resultado['data']) {
            $resultado['data'] = $this->formaSolicitud($resultado['data']);
        }

        return response()->json($resultado, $resultado['ok'] ? 200 : 409);
    }

    /**
     * conductor_id desde la sesión (o query/POST para pruebas curl con ?conductor_id=).
     */
    private function conductorId(Request $request): ?int
    {
        $id = $request->session()->get('conductor_id')
            ?? $request->input('conductor_id');

        return $id ? (int) $id : null;
    }

    /**
     * Forma uniforme de una solicitud para la PWA (nombres del contrato CONTEXT.md).
     */
    private function formaSolicitud(object $s): object
    {
        $desdePublicacion = $s->created_at ?? null;

        return (object) [
            'id' => (int) $s->id,
            'cliente_nombre' => $s->cliente_nombre,
            'cliente_telefono' => $s->cliente_telefono,
            'origen_texto' => $s->origen_texto,
            'destino_texto' => $s->destino_texto,
            'tipo_servicio' => $s->tipo_servicio,
            'oferta_cliente' => (float) $s->oferta_cliente,
            'precio_final' => $s->precio_final !== null ? (float) $s->precio_final : null,
            'estado' => $s->estado,
            'conductor_id' => $s->conductor_id ? (int) $s->conductor_id : null,
            'hace_segundos' => $desdePublicacion ? (int) max(0, now()->diffInSeconds(Carbon::parse($desdePublicacion), true)) : null,
        ];
    }
}
