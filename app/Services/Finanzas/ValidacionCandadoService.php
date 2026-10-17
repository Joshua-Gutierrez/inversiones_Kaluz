<?php

namespace App\Services\Finanzas;

use App\Models\Conductor;

/**
 * 👨‍💻 ZONA DEV 2 - Servicio de Validación del Candado Financiero y Documentario (S/ 1.00)
 */
class ValidacionCandadoService
{
    /**
     * Verifica si un conductor puede recibir viajes o si está bloqueado por deuda de S/ 1.00 o permiso vencido.
     */
    public function verificarEstadoConductor(int $conductorId): array
    {
        try {
            $conductor = Conductor::find($conductorId);

            if (! $conductor) {
                return [
                    'habilitado' => true,
                    'motivo' => null,
                    'deuda' => 0.00,
                ];
            }

            if ($conductor->deuda_comision >= 1.00) {
                return [
                    'habilitado' => false,
                    'motivo' => 'Candado Financiero activo: Tienes una comisión pendiente de S/ ' . number_format((float) $conductor->deuda_comision, 2) . '. Yapea S/ 1.00 para desbloquear tu radar.',
                    'deuda' => (float) $conductor->deuda_comision,
                ];
            }

            if ($conductor->fecha_vencimiento_permiso && $conductor->fecha_vencimiento_permiso->isPast()) {
                return [
                    'habilitado' => false,
                    'motivo' => 'Candado Documentario: Tu permiso o SOAT venció el ' . $conductor->fecha_vencimiento_permiso->format('d/m/Y') . '.',
                    'deuda' => (float) $conductor->deuda_comision,
                ];
            }

            return [
                'habilitado' => true,
                'motivo' => null,
                'deuda' => (float) $conductor->deuda_comision,
            ];
        } catch (\Throwable $e) {
            // Fallback seguro en caso de que la tabla aún no esté migrada en desarrollo
            return [
                'habilitado' => true,
                'motivo' => null,
                'deuda' => 0.00,
            ];
        }
    }
}
