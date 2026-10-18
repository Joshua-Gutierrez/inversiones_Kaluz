<?php

namespace App\Http\Controllers\Turismo;

use App\Http\Controllers\Controller;
use App\Services\Supabase\Supabase;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class RastreoGpsController extends Controller
{
    public function mostrarMapa(int $id): View
    {
        $viaje = $this->obtenerViaje($id);
        $ofertas = $this->obtenerOfertas((int) $viaje->id);

        return view('turismo.track', [
            'viaje' => $viaje,
            'paradas' => $this->obtenerParadas($viaje),
            'ofertasIniciales' => $ofertas,
            'modoDemo' => true,
        ]);
    }

    public function posicion(int $id): JsonResponse
    {
        $viaje = $this->obtenerViaje($id);
        $ofertas = $this->obtenerOfertas((int) $viaje->id);

        return response()->json([
            'id' => (int) $viaje->id,
            'lat' => $viaje->lat_actual === null ? null : (float) $viaje->lat_actual,
            'lng' => $viaje->lng_actual === null ? null : (float) $viaje->lng_actual,
            'origen_lat' => (float) ($viaje->origen_lat ?? -12.0651),
            'origen_lng' => (float) ($viaje->origen_lng ?? -75.2049),
            'destino_lat' => (float) ($viaje->destino_lat ?? -12.0565),
            'destino_lng' => (float) ($viaje->destino_lng ?? -75.2005),
            'estado' => $viaje->estado,
            'oferta_cliente' => (float) ($viaje->oferta_cliente ?? 6.00),
            'precio_final' => $viaje->precio_final !== null ? (float) $viaje->precio_final : null,
            'conductor' => $viaje->conductor_id === null ? null : [
                'id' => (int) $viaje->conductor_id,
                'nombre' => $viaje->nombre,
                'placa' => $viaje->placa,
                'modelo' => $viaje->modelo_vehiculo,
                'telefono' => $viaje->telefono_whatsapp,
            ],
            'ofertas' => $ofertas,
            'actualizado_en' => $viaje->conductor_actualizado_en,
        ])
        ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
        ->header('Pragma', 'no-cache')
        ->header('Expires', '0');
    }

    /**
     * Avanza o acerca el vehículo hacia el punto de recojo del cliente (si está asignado)
     * o hacia el destino (si está en_ruta), imitando el GPS en vivo de inDrive.
     */
    public function simular(int $id): JsonResponse
    {
        $viaje = $this->obtenerViaje($id);

        if ($viaje->conductor_id === null) {
            return response()->json(['message' => 'Primero elige una oferta de conductor para iniciar el seguimiento GPS.'], 422);
        }

        $latActual = (float) ($viaje->lat_actual ?? -12.0695);
        $lngActual = (float) ($viaje->lng_actual ?? -75.2105);

        $objetivoLat = $viaje->estado === 'en_ruta'
            ? (float) ($viaje->destino_lat ?? -12.0565)
            : (float) ($viaje->origen_lat ?? -12.0651);
        $objetivoLng = $viaje->estado === 'en_ruta'
            ? (float) ($viaje->destino_lng ?? -75.2005)
            : (float) ($viaje->origen_lng ?? -75.2049);

        $distancia = hypot($objetivoLat - $latActual, $objetivoLng - $lngActual);

        if ($distancia < 0.0005) {
            if ($viaje->estado === 'asignado') {
                $this->tabla('solicitudes_viaje')->where('id', $viaje->id)->update([
                    'estado' => 'en_ruta',
                    'updated_at' => now(),
                ]);
                $objetivoLat = (float) ($viaje->destino_lat ?? -12.0565);
                $objetivoLng = (float) ($viaje->destino_lng ?? -75.2005);
            }
        }

        $nuevaLat = $latActual + ($objetivoLat - $latActual) * 0.28;
        $nuevaLng = $lngActual + ($objetivoLng - $lngActual) * 0.28;

        $this->tabla('conductores')->where('id', $viaje->conductor_id)->update([
            'lat_actual' => round($nuevaLat, 6),
            'lng_actual' => round($nuevaLng, 6),
            'updated_at' => now(),
        ]);

        return $this->posicion($id);
    }

    /**
     * Simula que 3 conductores habilitados envían contraofertas simultáneas a esta solicitud
     * para que el pasajero pueda comparar y elegir la más conveniente.
     */
    public function simularOfertas(int $id): JsonResponse
    {
        $solicitud = $this->tabla('solicitudes_viaje')->where('id', $id)->first();

        if (! $solicitud) {
            $id = (int) $this->tabla('solicitudes_viaje')->insertGetId([
                'cliente_telefono' => '+51999888777',
                'cliente_nombre' => 'Pasajero Huancayo',
                'origen_texto' => 'Real Plaza Huancayo',
                'destino_texto' => 'Parque de la Identidad (El Tambo)',
                'origen_lat' => -12.0651,
                'origen_lng' => -75.2049,
                'destino_lat' => -12.0545,
                'destino_lng' => -75.1965,
                'tipo_servicio' => 'urbano',
                'precio_sugerido' => 6.00,
                'oferta_cliente' => 6.00,
                'precio_final' => null,
                'conductor_id' => null,
                'estado' => 'en_subasta',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $solicitud = $this->tabla('solicitudes_viaje')->where('id', $id)->first();
        } else {
            $this->tabla('solicitudes_viaje')->where('id', $id)->update([
                'estado' => 'en_subasta',
                'conductor_id' => null,
                'precio_final' => null,
                'updated_at' => now(),
            ]);
        }

        $basePrecio = (float) ($solicitud->oferta_cliente ?? 6.00);
        $conductores = $this->tabla('conductores')->where('habilitado', true)->limit(3)->get();

        $incrementos = [0.50, 1.00, 2.00];
        $tiempos = [4, 2, 3];

        foreach ($conductores->values() as $idx => $c) {
            $monto = round($basePrecio + ($incrementos[$idx] ?? 1.00), 2);
            $tiempo = $tiempos[$idx] ?? 3;

            $this->tabla('ofertas_conductores')->upsert(
                [[
                    'solicitud_id' => (int) $solicitud->id,
                    'conductor_id' => (int) $c->id,
                    'monto_contraoferta' => $monto,
                    'tiempo_llegada_min' => $tiempo,
                    'estado' => 'pendiente',
                    'created_at' => now(),
                ]],
                ['solicitud_id', 'conductor_id'],
                ['monto_contraoferta', 'tiempo_llegada_min', 'estado']
            );
        }

        return $this->posicion((int) $solicitud->id);
    }

    private function obtenerOfertas(int $solicitudId): array
    {
        $ofertas = $this->tabla('ofertas_conductores')
            ->where('solicitud_id', $solicitudId)
            ->where('estado', 'pendiente')
            ->orderBy('monto_contraoferta', 'asc')
            ->get();

        if ($ofertas->isEmpty()) {
            return [];
        }

        $conductores = $this->tabla('conductores')->get()->keyBy('id');

        return $ofertas->map(function ($o) use ($conductores) {
            $c = $conductores->get($o->conductor_id);

            return [
                'id' => (int) $o->id,
                'conductor_id' => (int) $o->conductor_id,
                'conductor_nombre' => $c->nombre ?? 'Conductor KALUZ',
                'placa' => $c->placa ?? 'W3X-451',
                'modelo' => $c->modelo_vehiculo ?? 'Toyota Yaris',
                'tipo_flota' => $c->tipo_flota ?? 'afiliado',
                'monto' => (float) $o->monto_contraoferta,
                'tiempo_min' => (int) $o->tiempo_llegada_min,
            ];
        })->values()->all();
    }

    private function tabla(string $table)
    {
        if (extension_loaded('pdo_pgsql')) {
            try {
                DB::connection()->getPdo();

                return DB::table($table);
            } catch (\Throwable) {
                // Fallback to Supabase REST
            }
        }

        return Supabase::table($table);
    }

    private function obtenerViaje(int $id): object
    {
        $solicitud = $this->tabla('solicitudes_viaje')->where('id', $id)->first()
            ?? $this->tabla('solicitudes_viaje')->orderByDesc('id')->first();

        if ($solicitud === null) {
            $paquete = $this->tabla('paquetes_turisticos')->where('id', $id)->first()
                ?? $this->tabla('paquetes_turisticos')->first();

            abort_if($paquete === null, 404, 'El viaje o paquete solicitado no existe.');

            $paradas = is_array($paquete->itinerario_paradas)
                ? $paquete->itinerario_paradas
                : json_decode((string) $paquete->itinerario_paradas, true);
            $origen = $paradas[0] ?? ['nombre' => 'Real Plaza Huancayo', 'lat' => -12.0651, 'lng' => -75.2049];
            $destino = $paradas[array_key_last($paradas)] ?? ['nombre' => $paquete->titulo, 'lat' => -12.0545, 'lng' => -75.1965];

            $conductor = $this->tabla('conductores')->where('habilitado', true)->first();

            return (object) [
                'id' => $id,
                'origen_texto' => $origen['nombre'],
                'destino_texto' => $destino['nombre'],
                'origen_lat' => $origen['lat'],
                'origen_lng' => $origen['lng'],
                'destino_lat' => $destino['lat'],
                'destino_lng' => $destino['lng'],
                'tipo_servicio' => 'turismo',
                'paquete_turistico_id' => $paquete->id,
                'oferta_cliente' => (float) $paquete->precio_cerrado,
                'precio_final' => (float) $paquete->precio_cerrado,
                'conductor_id' => $conductor?->id,
                'estado' => 'asignado',
                'nombre' => $conductor?->nombre ?? 'Juan Carlos Quispe',
                'placa' => $conductor?->placa ?? 'W2X-780',
                'modelo_vehiculo' => $conductor?->modelo_vehiculo ?? 'Toyota HiAce Combi',
                'telefono_whatsapp' => $conductor?->telefono_whatsapp ?? '964111222',
                'lat_actual' => $conductor?->lat_actual ?? ($origen['lat'] - 0.0035),
                'lng_actual' => $conductor?->lng_actual ?? ($origen['lng'] - 0.0040),
                'conductor_actualizado_en' => $conductor?->updated_at ?? now(),
            ];
        }

        $conductor = null;
        if (! empty($solicitud->conductor_id)) {
            $conductor = $this->tabla('conductores')->where('id', $solicitud->conductor_id)->first();
        }

        $origenLat = (float) ($solicitud->origen_lat ?? -12.0651);
        $origenLng = (float) ($solicitud->origen_lng ?? -75.2049);

        return (object) [
            'id' => $solicitud->id,
            'origen_texto' => $solicitud->origen_texto,
            'destino_texto' => $solicitud->destino_texto,
            'origen_lat' => $origenLat,
            'origen_lng' => $origenLng,
            'destino_lat' => (float) ($solicitud->destino_lat ?? -12.0545),
            'destino_lng' => (float) ($solicitud->destino_lng ?? -75.1965),
            'tipo_servicio' => $solicitud->tipo_servicio,
            'paquete_turistico_id' => $solicitud->paquete_turistico_id ?? null,
            'oferta_cliente' => (float) ($solicitud->oferta_cliente ?? 6.00),
            'precio_final' => $solicitud->precio_final !== null ? (float) $solicitud->precio_final : null,
            'conductor_id' => $solicitud->conductor_id ?? null,
            'estado' => $solicitud->estado,
            'nombre' => $conductor?->nombre,
            'placa' => $conductor?->placa,
            'modelo_vehiculo' => $conductor?->modelo_vehiculo,
            'telefono_whatsapp' => $conductor?->telefono_whatsapp,
            'lat_actual' => $conductor?->lat_actual !== null ? (float) $conductor->lat_actual : ($origenLat - 0.0032),
            'lng_actual' => $conductor?->lng_actual !== null ? (float) $conductor->lng_actual : ($origenLng - 0.0038),
            'conductor_actualizado_en' => $conductor?->updated_at ?? now(),
        ];
    }

    /**
     * @return array<int, array{nombre: string, lat: float, lng: float}>
     */
    private function obtenerParadas(object $viaje): array
    {
        if ($viaje->tipo_servicio === 'turismo' && $viaje->paquete_turistico_id !== null) {
            $paquete = $this->tabla('paquetes_turisticos')
                ->where('id', $viaje->paquete_turistico_id)
                ->value('itinerario_paradas');

            if ($paquete !== null) {
                return is_array($paquete) ? $paquete : json_decode((string) $paquete, true);
            }
        }

        return [
            ['nombre' => $viaje->origen_texto, 'lat' => (float) $viaje->origen_lat, 'lng' => (float) $viaje->origen_lng],
            ['nombre' => $viaje->destino_texto, 'lat' => (float) $viaje->destino_lat, 'lng' => (float) $viaje->destino_lng],
        ];
    }
}
