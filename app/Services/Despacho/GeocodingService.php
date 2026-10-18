<?php

namespace App\Services\Despacho;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

/**
 * 👨‍💻 ZONA DEV 1 - Geocodificación gratuita para el mapa del viaje.
 *
 * Usa Nominatim (OpenStreetMap) y un diccionario de lugares conocidos de
 * Huancayo para que la demo funcione incluso sin internet. Si no encuentra
 * la dirección, devuelve el centro de Huancayo como respaldo.
 */
class GeocodingService
{
    public const HUANCAYO = ['lat' => -12.0651, 'lng' => -75.2049];

    /**
     * Lugares usados por la demo y las solicitudes más comunes en Huancayo.
     */
    private const LUGARES = [
        'universidad continental' => ['lat' => -12.0475, 'lng' => -75.1989],
        'continental' => ['lat' => -12.0475, 'lng' => -75.1989],
        'real plaza' => ['lat' => -12.0674771, 'lng' => -75.2048808],
        'open plaza' => ['lat' => -12.0619, 'lng' => -75.2114],
        'parque de la identidad' => ['lat' => -12.0558, 'lng' => -75.1932],
        'identidad' => ['lat' => -12.0558, 'lng' => -75.1932],
        'el tambo' => ['lat' => -12.0571319, 'lng' => -75.2176609],
        'plaza constitucion' => ['lat' => -12.0680457, 'lng' => -75.2100731],
        'constitucion' => ['lat' => -12.0680457, 'lng' => -75.2100731],
        'chilca' => ['lat' => -12.0853, 'lng' => -75.2078],
        'cerrito de la libertad' => ['lat' => -12.0628, 'lng' => -75.1972],
        'terminal terrestre' => ['lat' => -12.0436, 'lng' => -75.2225],
        'uncp' => ['lat' => -12.0338, 'lng' => -75.2148],
        'universidad nacional' => ['lat' => -12.0338, 'lng' => -75.2148],
        'hospital carrion' => ['lat' => -12.0659, 'lng' => -75.2156],
        'ramiro priale' => ['lat' => -12.0451, 'lng' => -75.2183],
        'mil maravillas' => ['lat' => -12.0512, 'lng' => -75.2035],
        'san carlos' => ['lat' => -12.0595, 'lng' => -75.2010],
    ];

    /**
     * Calcula una tarifa sugerida en soles según distancia Haversine en Huancayo.
     */
    public function precioSugerido(float $origenLat, float $origenLng, float $destinoLat, float $destinoLng): float
    {
        $radioKm = 6371.0;
        $dLat = deg2rad($destinoLat - $origenLat);
        $dLng = deg2rad($destinoLng - $origenLng);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($origenLat)) * cos(deg2rad($destinoLat)) * sin($dLng / 2) ** 2;
        $distanciaKm = $radioKm * (2 * atan2(sqrt($a), sqrt(1 - $a)));

        $tarifa = 5.0 + ($distanciaKm * 1.1);

        return max(5.0, round($tarifa * 2) / 2);
    }

    /**
     * @return array{lat: float, lng: float, precision: string}
     */
    public function coordenadas(?string $direccion): array
    {
        $texto = trim((string) $direccion);

        if ($texto === '') {
            return $this->aproximada();
        }

        $normalizado = Str::lower(Str::ascii($texto));

        foreach (self::LUGARES as $lugar => $coordenadas) {
            if (str_contains($normalizado, $lugar)) {
                return $this->exacta($coordenadas['lat'], $coordenadas['lng']);
            }
        }

        return Cache::remember('geocode:'.sha1($normalizado), now()->addDays(7), function () use ($texto) {
            try {
                $respuesta = Http::withHeaders([
                    'User-Agent' => 'TaxiKaluz-Demo/1.0 (hackathon; contacto: dev@taxikaluz.pe)',
                    'Accept-Language' => 'es',
                ])->timeout(5)->get('https://nominatim.openstreetmap.org/search', [
                    'q' => $texto.', Huancayo, Perú',
                    'format' => 'jsonv2',
                    'limit' => 1,
                    'countrycodes' => 'pe',
                ]);

                $fila = $respuesta->json('0');

                if (is_array($fila) && isset($fila['lat'], $fila['lon'])) {
                    return $this->exacta((float) $fila['lat'], (float) $fila['lon']);
                }
            } catch (Throwable) {
                // Sin red o límite de Nominatim: usamos el respaldo.
            }

            return $this->aproximada();
        });
    }

    /**
     * @return array{lat: float, lng: float, precision: string}
     */
    private function exacta(float $lat, float $lng): array
    {
        return ['lat' => $lat, 'lng' => $lng, 'precision' => 'exacta'];
    }

    /**
     * @return array{lat: float, lng: float, precision: string}
     */
    private function aproximada(): array
    {
        return [
            'lat' => self::HUANCAYO['lat'],
            'lng' => self::HUANCAYO['lng'],
            'precision' => 'aproximada',
        ];
    }
}
