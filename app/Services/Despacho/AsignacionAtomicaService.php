<?php

namespace App\Services\Despacho;

use App\Models\OfertaConductor;
use App\Models\SolicitudViaje;
use Illuminate\Support\Facades\DB;

/**
 * 👨‍💻 ZONA DEV 1 - Servicio de Asignación Atómica de Subastas
 * Garantiza que dos conductores no tomen el mismo viaje simultáneamente.
 */
class AsignacionAtomicaService
{
    /**
     * Asigna atómicamente un viaje a un conductor usando bloqueo pesimista (lockForUpdate).
     */
    public function asignarViajeAtomicamente(int $solicitudViajeId, int $conductorId, float $precioFinal): array
    {
        return DB::transaction(function () use ($solicitudViajeId, $conductorId, $precioFinal) {
            $viaje = SolicitudViaje::where('id', $solicitudViajeId)
                ->lockForUpdate()
                ->first();

            if (! $viaje) {
                return [
                    'exito' => false,
                    'mensaje' => 'La solicitud de viaje no existe.',
                ];
            }

            if (! in_array($viaje->estado, ['pendiente', 'en_subasta'])) {
                return [
                    'exito' => false,
                    'mensaje' => 'Este viaje ya fue tomado por otro conductor.',
                ];
            }

            $viaje->update([
                'conductor_id' => $conductorId,
                'precio_acordado' => $precioFinal,
                'estado' => 'asignado',
            ]);

            OfertaConductor::where('solicitud_viaje_id', $solicitudViajeId)
                ->where('conductor_id', '!=', $conductorId)
                ->update(['estado' => 'expirada']);

            return [
                'exito' => true,
                'mensaje' => 'Viaje asignado exitosamente.',
                'viaje' => $viaje,
            ];
        });
    }
}
