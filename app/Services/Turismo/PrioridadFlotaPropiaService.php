<?php

namespace App\Services\Turismo;

use Illuminate\Support\Facades\DB;

class PrioridadFlotaPropiaService
{
    /**
     * @param  array{id: int, origen_lat: float|int|string|null, origen_lng: float|int|string|null, vehiculo_recomendado: string}  $solicitud
     * @return array{conductor: object|null, mensaje: string}
     */
    public function asignarUnidad(array $solicitud, int $pasajeros): array
    {
        $vehiculoRecomendado = $pasajeros > 4 ? 'propia_combi' : $solicitud['vehiculo_recomendado'];
        $candidatos = $this->obtenerCandidatos($solicitud, $vehiculoRecomendado);

        foreach ($candidatos as $candidato) {
            $resultado = DB::transaction(function () use ($candidato, $solicitud): ?object {
                DB::table('conductores')->where('id', $candidato->id)->lockForUpdate()->first();

                $estaOcupado = DB::table('solicitudes_viaje')
                    ->where('conductor_id', $candidato->id)
                    ->whereNotIn('estado', ['completado', 'cancelado'])
                    ->where('id', '!=', $solicitud['id'])
                    ->exists();

                if ($estaOcupado) {
                    return null;
                }

                $asignado = DB::table('solicitudes_viaje')
                    ->where('id', $solicitud['id'])
                    ->where('estado', 'pendiente')
                    ->whereNull('conductor_id')
                    ->update([
                        'conductor_id' => $candidato->id,
                        'updated_at' => now(),
                    ]);

                return $asignado === 1 ? $candidato : null;
            });

            if ($resultado !== null) {
                return [
                    'conductor' => $resultado,
                    'mensaje' => $resultado->tipo_flota === 'afiliado'
                        ? 'Se asigno un afiliado habilitado por falta de flota propia disponible.'
                        : 'Se asigno una unidad propia habilitada de Taxi Kaluz.',
                ];
            }
        }

        return [
            'conductor' => null,
            'mensaje' => 'No hay unidades habilitadas disponibles para este recorrido.',
        ];
    }

    /**
     * @param  array{origen_lat: float|int|string|null, origen_lng: float|int|string|null}  $solicitud
     */
    private function obtenerCandidatos(array $solicitud, string $vehiculoRecomendado): iterable
    {
        $latitud = (float) ($solicitud['origen_lat'] ?? -12.0651);
        $longitud = (float) ($solicitud['origen_lng'] ?? -75.2049);
        $distancia = '(6371 * acos(least(1, greatest(-1, cos(radians(?)) * cos(radians(coalesce(lat_actual, ?))) * cos(radians(coalesce(lng_actual, ?)) - radians(?)) + sin(radians(?)) * sin(radians(coalesce(lat_actual, ?)))))))';

        return DB::table('conductores')
            ->select(['id', 'nombre', 'telefono_whatsapp', 'placa', 'modelo_vehiculo', 'tipo_flota', 'lat_actual', 'lng_actual'])
            ->selectRaw("{$distancia} as distancia_km", [$latitud, $latitud, $longitud, $longitud, $latitud, $latitud])
            ->where('habilitado', true)
            ->whereIn('tipo_flota', ['propia_combi', 'propia_auto', 'afiliado'])
            ->orderByRaw(
                "case
                    when tipo_flota = ? then 0
                    when tipo_flota in ('propia_combi', 'propia_auto') then 1
                    else 2
                end",
                [$vehiculoRecomendado],
            )
            ->orderBy('distancia_km')
            ->get();
    }
}
