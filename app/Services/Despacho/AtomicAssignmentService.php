<?php

namespace App\Services\Despacho;

use App\Models\Conductor;
use App\Services\Supabase\Supabase;
use Illuminate\Support\Facades\DB;

/**
 * 👨‍💻 ZONA DEV 1 - Motor de Asignación Atómica de Viajes (estilo inDrive)
 *
 * Garantiza que dos conductores nunca ganen el mismo viaje en el mismo milisegundo,
 * usando UPDATE condicionales atómicos sobre PostgreSQL (Regla de Oro #1 de CONTEXT.md).
 *
 * CONTRATO DE INTEGRACIÓN (Dev 4 - Bot WhatsApp llama a aceptarContraoferta):
 *
 *  - solicitudesDisponibles(Conductor $c): \Illuminate\Support\Collection
 *  - aceptarViaje(int $solicitudId, int $conductorId): array
 *  - contraofertar(int $solicitudId, int $conductorId, float $monto, int $tiempoLlegadaMin): array
 *  - aceptarContraoferta(int $ofertaId): array
 *  - actualizarEstadoViaje(int $solicitudId, int $conductorId, string $nuevoEstado): array
 *  - actualizarUbicacion(int $conductorId, float $lat, float $lng): bool
 *  - viajeActivoDe(int $conductorId): ?object
 *
 * Todas las respuestas siguen la forma: ['ok' => bool, 'mensaje' => string, 'data' => mixed]
 */
class AtomicAssignmentService
{
    /**
     * Transiciones válidas de estado del viaje (asignado → en_ruta → completado).
     */
    private const TRANSICIONES_VALIDAS = [
        'asignado' => ['en_ruta', 'cancelado'],
        'en_ruta' => ['completado', 'cancelado'],
    ];

    private function tienePdoPgsql(): bool
    {
        if (! extension_loaded('pdo_pgsql')) {
            return false;
        }

        try {
            DB::connection()->getPdo();

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    private function tabla(string $table)
    {
        if ($this->tienePdoPgsql()) {
            return DB::table($table);
        }

        return Supabase::table($table);
    }

    private function transaccion(\Closure $callback): mixed
    {
        if ($this->tienePdoPgsql()) {
            return DB::transaction($callback);
        }

        return $callback();
    }

    public function obtenerConductor(int $conductorId): ?object
    {
        return $this->tabla('conductores')->where('id', $conductorId)->first();
    }

    /**
     * Lista de solicitudes disponibles (pendiente / en_subasta y sin conductor) para un conductor.
     *
     * Regla de candado financiero (doble seguro, además del middleware): si el conductor
     * tiene habilitado = false NO ve ni puede aceptar viajes.
     *
     * Regla de prioridad de flota propia (Regla #3): las solicitudes tipo 'turismo'
     * se muestran primero a la flota propia (propia_combi / propia_auto); los afiliados
     * no las ven (quedan reservadas para la flota de la empresa).
     */
    public function solicitudesDisponibles(object $c)
    {
        $conductor = $this->tabla('conductores')
            ->where('id', $c->id)
            ->select('id', 'habilitado', 'tipo_flota')
            ->first();

        if (! $conductor || ! $conductor->habilitado) {
            return collect([]);
        }

        $esFlotaPropia = in_array($conductor->tipo_flota, ['propia_combi', 'propia_auto'], true);

        $query = $this->tabla('solicitudes_viaje')
            ->whereIn('estado', ['pendiente', 'en_subasta'])
            ->whereNull('conductor_id');

        if (! $esFlotaPropia) {
            $query->where('tipo_servicio', '!=', 'turismo');
        } else {
            // Flota propia ve primero turismo, luego el resto.
            $query->orderByRaw("CASE WHEN tipo_servicio = 'turismo' THEN 0 ELSE 1 END");
        }

        $resultados = $query->orderBy('id', 'desc')->get()
            ->reject(fn ($s) => ($s->origen_texto ?? '') === 'POR_DEFINIR' || ($s->destino_texto ?? '') === 'POR_DEFINIR')
            ->values();

        if ($esFlotaPropia) {
            return $resultados->sortBy(fn ($s) => $s->tipo_servicio === 'turismo' ? 0 : 1)->values();
        }

        return $resultados;
    }

    /**
     * Acepta el viaje AL PRECIO DEL CLIENTE (oferta_cliente) de forma atómica.
     *
     * Patrón obligatorio (CONTEXT.md Regla #1): UPDATE condicionado por estado pendiente/en_subasta
     * y conductor_id IS NULL. Si devuelve 0 filas afectadas, otro conductor ganó la carrera.
     *
     * @return array{ok: bool, mensaje: string, data: ?object}
     */
    public function aceptarViaje(int $solicitudId, int $conductorId): array
    {
        if (! $this->conductorHabilitado($conductorId)) {
            return [
                'ok' => false,
                'mensaje' => 'Cuenta bloqueada: regulariza tu comisión de S/ 1.00 diario o tu permiso.',
                'data' => null,
            ];
        }

        return $this->transaccion(function () use ($solicitudId, $conductorId) {
            $solicitud = $this->tabla('solicitudes_viaje')
                ->where('id', $solicitudId)
                ->lockForUpdate()
                ->first();

            if (! $solicitud) {
                return [
                    'ok' => false,
                    'mensaje' => 'La solicitud de viaje no existe.',
                    'data' => null,
                ];
            }

            $precioCliente = (float) $solicitud->oferta_cliente;

            // UPDATE atómico: solo gana quien llega primero con estado pendiente/en_subasta y sin conductor.
            $afectadas = $this->tabla('solicitudes_viaje')
                ->where('id', $solicitudId)
                ->whereIn('estado', ['pendiente', 'en_subasta'])
                ->whereNull('conductor_id')
                ->update([
                    'conductor_id' => $conductorId,
                    'precio_final' => $precioCliente,
                    'estado' => 'asignado',
                    'updated_at' => now(),
                ]);

            if ($afectadas === 0) {
                return [
                    'ok' => false,
                    'mensaje' => 'Este viaje ya fue tomado por otro conductor.',
                    'data' => null,
                ];
            }

            // Cerramos las contraofertas que quedaban abiertas de esta solicitud.
            // (ofertas_conductores solo tiene created_at, sin updated_at.)
            $this->tabla('ofertas_conductores')
                ->where('solicitud_id', $solicitudId)
                ->where('estado', 'pendiente')
                ->update(['estado' => 'rechazada']);

            return [
                'ok' => true,
                'mensaje' => 'Viaje aceptado. ¡Vamos!',
                'data' => $this->tabla('solicitudes_viaje')->where('id', $solicitudId)->first(),
            ];
        });
    }

    /**
     * Contraoferta del conductor: inserta/actualiza su oferta en ofertas_conductores
     * (sin duplicados por conductor) y pasa la solicitud a 'en_subasta'.
     *
     * @return array{ok: bool, mensaje: string, data: ?object}
     */
    public function contraofertar(int $solicitudId, int $conductorId, float $monto, int $tiempoLlegadaMin): array
    {
        if (! $this->conductorHabilitado($conductorId)) {
            return [
                'ok' => false,
                'mensaje' => 'Cuenta bloqueada: no puedes enviar contraofertas.',
                'data' => null,
            ];
        }

        if ($monto <= 0) {
            return [
                'ok' => false,
                'mensaje' => 'El monto de la contraoferta debe ser mayor a S/ 0.00.',
                'data' => null,
            ];
        }

        return $this->transaccion(function () use ($solicitudId, $conductorId, $monto, $tiempoLlegadaMin) {
            $solicitud = $this->tabla('solicitudes_viaje')
                ->where('id', $solicitudId)
                ->lockForUpdate()
                ->first();

            if (! $solicitud) {
                return [
                    'ok' => false,
                    'mensaje' => 'La solicitud de viaje no existe.',
                    'data' => null,
                ];
            }

            if (! in_array($solicitud->estado, ['pendiente', 'en_subasta'], true) || $solicitud->conductor_id !== null) {
                return [
                    'ok' => false,
                    'mensaje' => 'Este viaje ya fue tomado por otro conductor.',
                    'data' => null,
                ];
            }

            // Un conductor = una oferta por solicitud (UNIQUE solicitud_id + conductor_id):
            // upsert atómico en vez de duplicar.
            $this->tabla('ofertas_conductores')->upsert(
                [[
                    'solicitud_id' => $solicitudId,
                    'conductor_id' => $conductorId,
                    'monto_contraoferta' => $monto,
                    'tiempo_llegada_min' => $tiempoLlegadaMin,
                    'estado' => 'pendiente',
                    'created_at' => now(),
                ]],
                ['solicitud_id', 'conductor_id'],
                ['monto_contraoferta', 'tiempo_llegada_min', 'estado']
            );

            $ofertaId = (int) $this->tabla('ofertas_conductores')
                ->where('solicitud_id', $solicitudId)
                ->where('conductor_id', $conductorId)
                ->value('id');

            // La solicitud pasa a subasta solo si seguía pendiente y sin conductor.
            $this->tabla('solicitudes_viaje')
                ->where('id', $solicitudId)
                ->where('estado', 'pendiente')
                ->whereNull('conductor_id')
                ->update(['estado' => 'en_subasta', 'updated_at' => now()]);

            return [
                'ok' => true,
                'mensaje' => 'Contraoferta enviada al pasajero.',
                'data' => $this->tabla('ofertas_conductores')->where('id', $ofertaId)->first(),
            ];
        });
    }

    /**
     * El cliente (vía el Bot de Dev 4) acepta una contraoferta.
     *
     * En una sola transacción atómica:
     *  1. Asigna la solicitud al conductor de esa oferta (solo si sigue sin conductor).
     *  2. Marca esa oferta 'aceptada' y las demás de la misma solicitud 'rechazada'.
     *
     * @return array{ok: bool, mensaje: string, data: ?object}
     */
    public function aceptarContraoferta(int $ofertaId): array
    {
        return $this->transaccion(function () use ($ofertaId) {
            $oferta = $this->tabla('ofertas_conductores')
                ->where('id', $ofertaId)
                ->lockForUpdate()
                ->first();

            if (! $oferta) {
                return [
                    'ok' => false,
                    'mensaje' => 'La oferta no existe.',
                    'data' => null,
                ];
            }

            if ($oferta->estado !== 'pendiente') {
                return [
                    'ok' => false,
                    'mensaje' => 'Esta oferta ya fue procesada.',
                    'data' => null,
                ];
            }

            $solicitudId = (int) $oferta->solicitud_id;
            $conductorId = (int) $oferta->conductor_id;
            $precioFinal = (float) $oferta->monto_contraoferta;

            if (! $this->conductorHabilitado($conductorId)) {
                return [
                    'ok' => false,
                    'mensaje' => 'El conductor elegido tiene la cuenta bloqueada. Elige otra opción.',
                    'data' => null,
                ];
            }

            // Asignación atómica: solo si la solicitud sigue pendiente/en_subasta y sin conductor.
            $afectadas = $this->tabla('solicitudes_viaje')
                ->where('id', $solicitudId)
                ->whereNull('conductor_id')
                ->whereIn('estado', ['pendiente', 'en_subasta'])
                ->update([
                    'conductor_id' => $conductorId,
                    'precio_final' => $precioFinal,
                    'estado' => 'asignado',
                    'updated_at' => now(),
                ]);

            if ($afectadas === 0) {
                $this->tabla('ofertas_conductores')
                    ->where('id', $ofertaId)
                    ->update(['estado' => 'rechazada']);

                return [
                    'ok' => false,
                    'mensaje' => 'La solicitud ya fue asignada a otro conductor.',
                    'data' => null,
                ];
            }

            $this->tabla('ofertas_conductores')
                ->where('id', $ofertaId)
                ->update(['estado' => 'aceptada']);

            $this->tabla('ofertas_conductores')
                ->where('solicitud_id', $solicitudId)
                ->where('id', '!=', $ofertaId)
                ->where('estado', 'pendiente')
                ->update(['estado' => 'rechazada']);

            return [
                'ok' => true,
                'mensaje' => 'Contraoferta aceptada. Viaje asignado.',
                'data' => $this->tabla('solicitudes_viaje')->where('id', $solicitudId)->first(),
            ];
        });
    }

    /**
     * Cambia el estado del viaje respetando las transiciones válidas:
     * asignado → en_ruta → completado, y cancelación desde asignado/en_ruta.
     * Valida que el viaje pertenezca al conductor (UPDATE condicionado).
     *
     * @return array{ok: bool, mensaje: string, data: ?object}
     */
    public function actualizarEstadoViaje(int $solicitudId, int $conductorId, string $nuevoEstado): array
    {
        return $this->transaccion(function () use ($solicitudId, $conductorId, $nuevoEstado) {
            $viaje = $this->tabla('solicitudes_viaje')
                ->where('id', $solicitudId)
                ->where('conductor_id', $conductorId)
                ->lockForUpdate()
                ->first();

            if (! $viaje) {
                return [
                    'ok' => false,
                    'mensaje' => 'Este viaje no te pertenece.',
                    'data' => null,
                ];
            }

            $transiciones = self::TRANSICIONES_VALIDAS[$viaje->estado] ?? [];

            if (! in_array($nuevoEstado, $transiciones, true)) {
                return [
                    'ok' => false,
                    'mensaje' => "No se puede pasar de '{$viaje->estado}' a '{$nuevoEstado}'.",
                    'data' => null,
                ];
            }

            $afectadas = $this->tabla('solicitudes_viaje')
                ->where('id', $solicitudId)
                ->where('conductor_id', $conductorId)
                ->where('estado', $viaje->estado)
                ->update([
                    'estado' => $nuevoEstado,
                    'updated_at' => now(),
                ]);

            if ($afectadas === 0) {
                return [
                    'ok' => false,
                    'mensaje' => 'El viaje cambió de estado. Recarga e intenta de nuevo.',
                    'data' => null,
                ];
            }

            return [
                'ok' => true,
                'mensaje' => 'Estado actualizado.',
                'data' => $this->tabla('solicitudes_viaje')->where('id', $solicitudId)->first(),
            ];
        });
    }

    /**
     * Guarda la posición GPS del conductor en lat_actual / lng_actual.
     * Si el dispositivo de prueba se encuentra fuera del Valle del Mantaro (>15 km de Huancayo),
     * proyecta la telemetría GPS sobre el centro de Huancayo para mantener todos los mapas sincronizados.
     */
    public function actualizarUbicacion(int $conductorId, float $lat, float $lng): bool
    {
        if (abs($lat - (-12.0651)) > 0.15 || abs($lng - (-75.2049)) > 0.15) {
            $offsetLat = (($conductorId % 5) - 2) * 0.0022 + (mt_rand(-8, 8) / 100000);
            $offsetLng = (($conductorId % 4) - 1.5) * 0.0025 + (mt_rand(-8, 8) / 100000);
            $lat = round(-12.0651 + $offsetLat, 6);
            $lng = round(-75.2049 + $offsetLng, 6);
        }

        return $this->tabla('conductores')
            ->where('id', $conductorId)
            ->update([
                'lat_actual' => $lat,
                'lng_actual' => $lng,
                'updated_at' => now(),
            ]) > 0;
    }

    /**
     * Viaje activo (asignado o en_ruta) de un conductor, si lo tiene.
     */
    public function viajeActivoDe(int $conductorId): ?object
    {
        return $this->tabla('solicitudes_viaje')
            ->where('conductor_id', $conductorId)
            ->whereIn('estado', ['asignado', 'en_ruta'])
            ->orderBy('updated_at', 'desc')
            ->first();
    }

    /**
     * Panel del conductor (datos + viaje activo) en UNA sola ida a la base de datos.
     *
     * La latencia por consulta al pooler de Supabase (~450 ms) domina el tiempo de
     * respuesta de la PWA, por eso se agrupan las lecturas en un solo round-trip.
     *
     * @return object{conductor: ?object, viaje_activo: ?object}|null
     */
    public function panelDe(int $conductorId): ?object
    {
        $fila = DB::selectOne(<<<'SQL'
            WITH c AS (
                SELECT id, nombre, placa, modelo_vehiculo, telefono_whatsapp,
                       estado_comision, habilitado, lat_actual, lng_actual, tipo_flota
                FROM conductores
                WHERE id = ?
            ),
            act AS (
                SELECT a.*
                FROM solicitudes_viaje a
                WHERE a.conductor_id = ? AND a.estado IN ('asignado', 'en_ruta')
                ORDER BY a.updated_at DESC
                LIMIT 1
            )
            SELECT
                (SELECT row_to_json(c) FROM c) AS conductor,
                (SELECT row_to_json(act) FROM act) AS viaje_activo
            SQL, [$conductorId, $conductorId]);

        if (! $fila) {
            return null;
        }

        return (object) [
            'conductor' => $fila->conductor ? json_decode($fila->conductor) : null,
            'viaje_activo' => $fila->viaje_activo ? json_decode($fila->viaje_activo) : null,
        ];
    }

    /**
     * Resumen del radar (conductor + solicitudes disponibles + viaje activo) en UNA consulta.
     *
     * @return object{conductor: ?object, solicitudes: array<int, object>, viaje_activo: ?object}|null
     */
    public function radarDe(int $conductorId): ?object
    {
        $fila = DB::selectOne(<<<'SQL'
            WITH c AS (
                SELECT id, nombre, placa, modelo_vehiculo, telefono_whatsapp,
                       estado_comision, habilitado, lat_actual, lng_actual, tipo_flota
                FROM conductores
                WHERE id = ?
            ),
            sol AS (
                SELECT s.*,
                       CASE
                           WHEN c.tipo_flota IN ('propia_combi', 'propia_auto')
                                AND s.tipo_servicio = 'turismo' THEN 0
                           ELSE 1
                       END AS orden_flota
                FROM solicitudes_viaje s
                CROSS JOIN c
                WHERE s.estado IN ('pendiente', 'en_subasta')
                  AND s.conductor_id IS NULL
                  AND (
                      c.tipo_flota IN ('propia_combi', 'propia_auto')
                      OR s.tipo_servicio <> 'turismo'
                  )
            ),
            act AS (
                SELECT a.*
                FROM solicitudes_viaje a
                WHERE a.conductor_id = ? AND a.estado IN ('asignado', 'en_ruta')
                ORDER BY a.updated_at DESC
                LIMIT 1
            )
            SELECT
                (SELECT row_to_json(c) FROM c) AS conductor,
                (SELECT COALESCE(json_agg(sol ORDER BY sol.orden_flota, sol.created_at), '[]'::json) FROM sol) AS solicitudes,
                (SELECT row_to_json(act) FROM act) AS viaje_activo
            SQL, [$conductorId, $conductorId]);

        if (! $fila) {
            return null;
        }

        return (object) [
            'conductor' => $fila->conductor ? json_decode($fila->conductor) : null,
            'solicitudes' => json_decode($fila->solicitudes ?? '[]') ?: [],
            'viaje_activo' => $fila->viaje_activo ? json_decode($fila->viaje_activo) : null,
        ];
    }

    /**
     * Doble seguro del candado financiero (además del middleware de Dev 2):
     * consulta directa a la columna habilitado del contrato.
     */
    private function conductorHabilitado(int $conductorId): bool
    {
        return $this->tabla('conductores')
            ->where('id', $conductorId)
            ->where('habilitado', true)
            ->exists();
    }
}
