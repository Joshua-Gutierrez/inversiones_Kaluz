<?php

namespace App\Services\Despacho;

use App\Models\Conductor;
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
    public function solicitudesDisponibles(Conductor $c)
    {
        $conductor = DB::table('conductores')
            ->where('id', $c->id)
            ->select('id', 'habilitado', 'tipo_flota')
            ->first();

        if (! $conductor || ! $conductor->habilitado) {
            return collect([]);
        }

        $esFlotaPropia = in_array($conductor->tipo_flota, ['propia_combi', 'propia_auto'], true);

        $query = DB::table('solicitudes_viaje')
            ->whereIn('estado', ['pendiente', 'en_subasta'])
            ->whereNull('conductor_id');

        if (! $esFlotaPropia) {
            $query->where('tipo_servicio', '!=', 'turismo');
        } else {
            // Flota propia ve primero turismo, luego el resto.
            $query->orderByRaw("CASE WHEN tipo_servicio = 'turismo' THEN 0 ELSE 1 END");
        }

        return $query->orderBy('created_at', 'asc')->get();
    }

    /**
     * Acepta el viaje AL PRECIO DEL CLIENTE (oferta_cliente) de forma atómica.
     *
     * Patrón obligatorio (CONTEXT.md Regla #1): UPDATE condicionado por estado = 'pendiente'
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

        return DB::transaction(function () use ($solicitudId, $conductorId) {
            $solicitud = DB::table('solicitudes_viaje')
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

            // UPDATE atómico: solo gana quien llega primero con estado pendiente y sin conductor.
            $afectadas = DB::table('solicitudes_viaje')
                ->where('id', $solicitudId)
                ->where('estado', 'pendiente')
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
            DB::table('ofertas_conductores')
                ->where('solicitud_id', $solicitudId)
                ->where('estado', 'pendiente')
                ->update(['estado' => 'rechazada']);

            return [
                'ok' => true,
                'mensaje' => 'Viaje aceptado. ¡Vamos!',
                'data' => DB::table('solicitudes_viaje')->where('id', $solicitudId)->first(),
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

        return DB::transaction(function () use ($solicitudId, $conductorId, $monto, $tiempoLlegadaMin) {
            $solicitud = DB::table('solicitudes_viaje')
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
            DB::table('ofertas_conductores')->upsert(
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

            $ofertaId = (int) DB::table('ofertas_conductores')
                ->where('solicitud_id', $solicitudId)
                ->where('conductor_id', $conductorId)
                ->value('id');

            // La solicitud pasa a subasta solo si seguía pendiente y sin conductor.
            DB::table('solicitudes_viaje')
                ->where('id', $solicitudId)
                ->where('estado', 'pendiente')
                ->whereNull('conductor_id')
                ->update(['estado' => 'en_subasta', 'updated_at' => now()]);

            return [
                'ok' => true,
                'mensaje' => 'Contraoferta enviada al pasajero.',
                'data' => DB::table('ofertas_conductores')->where('id', $ofertaId)->first(),
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
        return DB::transaction(function () use ($ofertaId) {
            $oferta = DB::table('ofertas_conductores')
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
            $afectadas = DB::table('solicitudes_viaje')
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
                DB::table('ofertas_conductores')
                    ->where('id', $ofertaId)
                    ->update(['estado' => 'rechazada']);

                return [
                    'ok' => false,
                    'mensaje' => 'La solicitud ya fue asignada a otro conductor.',
                    'data' => null,
                ];
            }

            DB::table('ofertas_conductores')
                ->where('id', $ofertaId)
                ->update(['estado' => 'aceptada']);

            DB::table('ofertas_conductores')
                ->where('solicitud_id', $solicitudId)
                ->where('id', '!=', $ofertaId)
                ->where('estado', 'pendiente')
                ->update(['estado' => 'rechazada']);

            return [
                'ok' => true,
                'mensaje' => 'Contraoferta aceptada. Viaje asignado.',
                'data' => DB::table('solicitudes_viaje')->where('id', $solicitudId)->first(),
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
        return DB::transaction(function () use ($solicitudId, $conductorId, $nuevoEstado) {
            $viaje = DB::table('solicitudes_viaje')
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

            $afectadas = DB::table('solicitudes_viaje')
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
                'data' => DB::table('solicitudes_viaje')->where('id', $solicitudId)->first(),
            ];
        });
    }

    /**
     * Guarda la posición GPS del conductor en lat_actual / lng_actual.
     * Dev 3 la consume para /track/{viaje_id}.
     */
    public function actualizarUbicacion(int $conductorId, float $lat, float $lng): bool
    {
        return DB::table('conductores')
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
        return DB::table('solicitudes_viaje')
            ->where('conductor_id', $conductorId)
            ->whereIn('estado', ['asignado', 'en_ruta'])
            ->orderBy('updated_at', 'desc')
            ->first();
    }

    /**
     * Doble seguro del candado financiero (además del middleware de Dev 2):
     * consulta directa a la columna habilitado del contrato.
     */
    private function conductorHabilitado(int $conductorId): bool
    {
        return DB::table('conductores')
            ->where('id', $conductorId)
            ->where('habilitado', true)
            ->exists();
    }
}
