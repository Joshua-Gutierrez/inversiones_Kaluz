<?php

namespace App\Http\Controllers\Despacho;

use App\Http\Controllers\Controller;
use App\Services\Despacho\AtomicAssignmentService;
use App\Services\Supabase\Supabase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

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

        $misOfertas = Supabase::table('ofertas_conductores')
            ->where('conductor_id', $conductorId)
            ->where('estado', 'pendiente')
            ->get()
            ->keyBy('solicitud_id');

        return response()->json([
            'ok' => true,
            'mensaje' => 'Solicitudes al día.',
            'data' => [
                'solicitudes' => $solicitudes->map(fn ($s) => $this->formaSolicitud($s, $conductor, $misOfertas->get($s->id)))->values(),
                'viaje_activo' => $viajeActivo ? $this->formaSolicitud($viajeActivo, $conductor) : null,
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

            // FLUJO 1 - Paso 5A: Si el chofer acepta directamente la tarifa del cliente desde el Radar
            if (! empty($viajeObj->cliente_telefono) && $conductor) {
                $this->notificarViajeConfirmadoWhatsApp($viajeObj, $conductor);
            }

            $resultado['data'] = $this->formaSolicitud($resultado['data'], $conductor);
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
                $ofertaId = (int) ($resultado['data']->id ?? 0);

                if ($solicitud && ! empty($solicitud->cliente_telefono) && $conductor) {
                    $telefonoWa = $this->normalizarTelefonoWa((string) $solicitud->cliente_telefono);

                    if (function_exists('enviarContraofertaInteractiva')) {
                        enviarContraofertaInteractiva(
                            $telefonoWa,
                            (string) $conductor->nombre,
                            (float) $datos['monto'],
                            (string) ($conductor->placa ?? 'W3X-451'),
                            (int) $datos['tiempo_llegada_min'],
                            $ofertaId
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
            $viajeObj = $resultado['data'];
            $conductor = $this->asignacion->obtenerConductor($conductorId);

            if (! empty($viajeObj->cliente_telefono) && function_exists('enviarTextoMetaWhatsApp')) {
                try {
                    $telefonoWa = $this->normalizarTelefonoWa((string) $viajeObj->cliente_telefono);
                    $precioFmt = number_format((float) ($viajeObj->precio_final ?? $viajeObj->oferta_cliente), 2, '.', '');
                    $urlRastreo = url('/rastreo/'.$viajeObj->id);
                    $nombreConductor = $conductor->nombre ?? 'Tu conductor KALUZ';
                    $placa = $conductor->placa ?? '';

                    if ($datos['estado'] === 'en_ruta') {
                        enviarTextoMetaWhatsApp(
                            $telefonoWa,
                            "🚀 *¡Tu viaje ha iniciado!*\n\n".
                            "Estás en ruta hacia *{$viajeObj->destino_texto}* con *{$nombreConductor}* ({$placa}).\n".
                            "💰 Tarifa acordada: *S/ {$precioFmt}*\n\n".
                            "🗺️ Sigue el recorrido en vivo aquí:\n{$urlRastreo}"
                        );
                    } elseif ($datos['estado'] === 'completado') {
                        enviarTextoMetaWhatsApp(
                            $telefonoWa,
                            "🏁 *¡Llegaste a tu destino!*\n\n".
                            "📍 Ruta: *{$viajeObj->origen_texto}* ➔ *{$viajeObj->destino_texto}*\n".
                            "💰 Total acordado: *S/ {$precioFmt}*\n\n".
                            '¡Gracias por viajar con *Taxi KALUZ Huancayo*! Escribe *Hola* cuando necesites otro taxi.'
                        );
                    } elseif ($datos['estado'] === 'cancelado') {
                        enviarTextoMetaWhatsApp(
                            $telefonoWa,
                            "⚠️ El conductor tuvo un inconveniente y no pudo completar el servicio.\n\n".
                            'Escribe *Hola* o envíanos tu ruta para asignarte otra unidad de inmediato.'
                        );
                    }
                } catch (\Throwable) {
                    // Continuar sin interrumpir respuesta JSON
                }
            }

            $resultado['data'] = $this->formaSolicitud($resultado['data'], $conductor);
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
     * La llama el Bot de Dev 4 o la vista /rastreo/{id} cuando el cliente acepta.
     */
    public function aceptarContraoferta(int $ofertaId): JsonResponse
    {
        $resultado = $this->asignacion->aceptarContraoferta($ofertaId);

        if ($resultado['ok'] && $resultado['data']) {
            $viajeObj = $resultado['data'];
            $conductor = $viajeObj->conductor_id
                ? $this->asignacion->obtenerConductor((int) $viajeObj->conductor_id)
                : null;

            if (! empty($viajeObj->cliente_telefono) && $conductor) {
                $this->notificarViajeConfirmadoWhatsApp($viajeObj, $conductor);
            }

            $resultado['data'] = $this->formaSolicitud($resultado['data'], $conductor);
        }

        return response()->json($resultado, $resultado['ok'] ? 200 : 409);
    }

    private function normalizarTelefonoWa(string $telefono): string
    {
        $soloDigitos = preg_replace('/\D+/', '', $telefono);
        if (strlen($soloDigitos) === 9) {
            return '51'.$soloDigitos;
        }

        return $soloDigitos;
    }

    private function notificarViajeConfirmadoWhatsApp(object $viajeObj, object $conductor): void
    {
        try {
            $telefonoWa = $this->normalizarTelefonoWa((string) $viajeObj->cliente_telefono);
            $precioFmt = number_format((float) ($viajeObj->precio_final ?? $viajeObj->oferta_cliente), 2, '.', '');
            $urlRastreo = url('/rastreo/'.$viajeObj->id);

            if (function_exists('enviarTextoMetaWhatsApp')) {
                enviarTextoMetaWhatsApp(
                    $telefonoWa,
                    "🎉 ¡Viaje confirmado!\n\n".
                    "🚗 Conductor: {$conductor->nombre}\n".
                    "🚙 Vehículo: {$conductor->modelo_vehiculo} ({$conductor->placa})\n".
                    "💰 Tarifa acordada: S/ {$precioFmt}\n\n".
                    "📍 Sigue a tu taxi en tiempo real aquí:\n{$urlRastreo}\n\n".
                    'El conductor ya tiene tu ubicación y está en camino. ¡Gracias por usar KALUZ!'
                );
            }
        } catch (\Throwable) {
            // Continuar sin interrumpir la asignación
        }
    }

    /**
     * conductor_id desde query/POST, sesión o cookie persistente para entornos Serverless (Vercel).
     */
    private function conductorId(Request $request): ?int
    {
        $id = $request->input('conductor_id')
            ?? $request->session()->get('conductor_id')
            ?? $request->cookie('kaluz_conductor_id');

        if ($id) {
            return (int) $id;
        }

        $primero = Supabase::table('conductores')->where('habilitado', true)->orderBy('id')->first();

        return $primero ? (int) $primero->id : null;
    }

    /**
     * Forma uniforme de una solicitud para la PWA (nombres del contrato CONTEXT.md).
     */
    private function formaSolicitud(object $s, ?object $conductor = null, ?object $miOferta = null): object
    {
        $desdePublicacion = $s->updated_at ?? $s->created_at ?? null;

        $latOrigen = ! empty($s->origen_lat) ? (float) $s->origen_lat : -12.0651;
        $lngOrigen = ! empty($s->origen_lng) ? (float) $s->origen_lng : -75.2049;
        $latChofer = ($conductor && ! empty($conductor->lat_actual)) ? (float) $conductor->lat_actual : -12.0651;
        $lngChofer = ($conductor && ! empty($conductor->lng_actual)) ? (float) $conductor->lng_actual : -75.2049;

        $distanciaKm = function_exists('calcularDistanciaHaversine')
            ? calcularDistanciaHaversine($latOrigen, $lngOrigen, $latChofer, $lngChofer)
            : 0.8;

        return (object) [
            'id' => (int) $s->id,
            'cliente_nombre' => $s->cliente_nombre ?: 'Pasajero WhatsApp',
            'cliente_telefono' => $s->cliente_telefono,
            'origen_texto' => $s->origen_texto,
            'destino_texto' => $s->destino_texto,
            'origen_lat' => $latOrigen,
            'origen_lng' => $lngOrigen,
            'destino_lat' => ! empty($s->destino_lat) ? (float) $s->destino_lat : -12.0558,
            'destino_lng' => ! empty($s->destino_lng) ? (float) $s->destino_lng : -75.2049,
            'distancia_km' => max(0.1, $distanciaKm),
            'tipo_servicio' => $s->tipo_servicio,
            'precio_sugerido' => (float) ($s->precio_sugerido ?? $s->oferta_cliente ?? 6.00),
            'oferta_cliente' => (float) $s->oferta_cliente,
            'precio_final' => $s->precio_final !== null ? (float) $s->precio_final : null,
            'mi_oferta' => $miOferta ? (float) $miOferta->monto_contraoferta : null,
            'estado' => $s->estado,
            'conductor_id' => $s->conductor_id ? (int) $s->conductor_id : null,
            'hace_segundos' => $desdePublicacion ? (int) max(0, now()->diffInSeconds(Carbon::parse($desdePublicacion), true)) : null,
        ];
    }
}
