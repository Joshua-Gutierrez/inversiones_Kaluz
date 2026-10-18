<?php

namespace App\Http\Controllers\Turismo;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class RastreoGpsController extends Controller
{
    public function mostrarMapa(int $id): View
    {
        $viaje = $this->obtenerViaje($id);

        return view('turismo.track', [
            'viaje' => $viaje,
            'paradas' => $this->obtenerParadas($viaje),
            'modoDemo' => filter_var(env('APP_DEMO', false), FILTER_VALIDATE_BOOL),
        ]);
    }

    public function posicion(int $id): JsonResponse
    {
        $viaje = $this->obtenerViaje($id);

        return response()->json([
            'lat' => $viaje->lat_actual === null ? null : (float) $viaje->lat_actual,
            'lng' => $viaje->lng_actual === null ? null : (float) $viaje->lng_actual,
            'estado' => $viaje->estado,
            'conductor' => $viaje->conductor_id === null ? null : [
                'nombre' => $viaje->nombre,
                'placa' => $viaje->placa,
                'modelo' => $viaje->modelo_vehiculo,
            ],
            'actualizado_en' => $viaje->conductor_actualizado_en,
        ]);
    }

    public function simular(int $id): JsonResponse
    {
        abort_unless(filter_var(env('APP_DEMO', false), FILTER_VALIDATE_BOOL), 404);

        $viaje = $this->obtenerViaje($id);

        if ($viaje->conductor_id === null) {
            return response()->json(['message' => 'El viaje aun no tiene conductor asignado.'], 422);
        }

        $paradas = $this->obtenerParadas($viaje);
        $actual = [(float) $viaje->lat_actual, (float) $viaje->lng_actual];
        $indiceMasCercano = 0;
        $distanciaMasCercana = INF;

        foreach ($paradas as $indice => $parada) {
            $distancia = hypot($actual[0] - $parada['lat'], $actual[1] - $parada['lng']);

            if ($distancia < $distanciaMasCercana) {
                $indiceMasCercano = $indice;
                $distanciaMasCercana = $distancia;
            }
        }

        $siguiente = $paradas[min($indiceMasCercano + 1, count($paradas) - 1)];
        DB::table('conductores')->where('id', $viaje->conductor_id)->update([
            'lat_actual' => $siguiente['lat'],
            'lng_actual' => $siguiente['lng'],
            'updated_at' => now(),
        ]);

        return $this->posicion($id);
    }

    private function obtenerViaje(int $id): object
    {
        $viaje = DB::table('solicitudes_viaje as viaje')
            ->leftJoin('conductores as conductor', 'conductor.id', '=', 'viaje.conductor_id')
            ->select([
                'viaje.id',
                'viaje.origen_texto',
                'viaje.destino_texto',
                'viaje.origen_lat',
                'viaje.origen_lng',
                'viaje.destino_lat',
                'viaje.destino_lng',
                'viaje.tipo_servicio',
                'viaje.paquete_turistico_id',
                'viaje.conductor_id',
                'viaje.estado',
                'conductor.nombre',
                'conductor.placa',
                'conductor.modelo_vehiculo',
                'conductor.telefono_whatsapp',
                'conductor.lat_actual',
                'conductor.lng_actual',
                'conductor.updated_at as conductor_actualizado_en',
            ])
            ->where('viaje.id', $id)
            ->first();

        abort_if($viaje === null, 404, 'El viaje solicitado no existe.');

        return $viaje;
    }

    /**
     * @return array<int, array{nombre: string, lat: float, lng: float}>
     */
    private function obtenerParadas(object $viaje): array
    {
        if ($viaje->tipo_servicio === 'turismo') {
            if ($viaje->paquete_turistico_id !== null) {
                $paquete = DB::table('paquetes_turisticos')
                    ->where('id', $viaje->paquete_turistico_id)
                    ->value('itinerario_paradas');

                if ($paquete !== null) {
                    return json_decode((string) $paquete, true, flags: JSON_THROW_ON_ERROR);
                }
            }

            foreach (DB::table('paquetes_turisticos')->select('itinerario_paradas')->get() as $paquete) {
                $paradas = json_decode((string) $paquete->itinerario_paradas, true, flags: JSON_THROW_ON_ERROR);
                $ultimaParada = $paradas[array_key_last($paradas)];

                if ($paradas[0]['nombre'] === $viaje->origen_texto && $ultimaParada['nombre'] === $viaje->destino_texto) {
                    return $paradas;
                }
            }
        }

        return [
            ['nombre' => $viaje->origen_texto, 'lat' => (float) $viaje->origen_lat, 'lng' => (float) $viaje->origen_lng],
            ['nombre' => $viaje->destino_texto, 'lat' => (float) $viaje->destino_lat, 'lng' => (float) $viaje->destino_lng],
        ];
    }
}
