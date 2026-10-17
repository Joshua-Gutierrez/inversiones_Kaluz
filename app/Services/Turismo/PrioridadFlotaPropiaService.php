<?php

namespace App\Services\Turismo;

use App\Models\Conductor;
use Illuminate\Support\Collection;

/**
 * 👨‍💻 ZONA DEV 3 - Servicio de Prioridad de Flota Propia (Combis y Tours de Kaluz)
 */
class PrioridadFlotaPropiaService
{
    /**
     * Obtiene conductores disponibles priorizando la flota propia de Kaluz para paquetes turísticos.
     */
    public function obtenerConductoresPriorizados(bool $soloFlotaPropia = false): Collection
    {
        try {
            $query = Conductor::habilitados();

            if ($soloFlotaPropia) {
                $query->flotaPropia();
            }

            return $query->orderByDesc('es_flota_propia')->get();
        } catch (\Throwable $e) {
            return collect([]);
        }
    }
}
