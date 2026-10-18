<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PaquetesTuristicosSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->paquetes() as $paquete) {
            DB::table('paquetes_turisticos')->updateOrInsert(
                ['titulo' => $paquete['titulo']],
                $paquete,
            );
        }
    }

    /**
     * @return array<int, array{titulo: string, descripcion: string, imagen_url: string, itinerario_paradas: string, duracion_horas: int, precio_cerrado: int, vehiculo_recomendado: string, activo: bool}>
     */
    private function paquetes(): array
    {
        return [
            [
                'titulo' => 'Valle del Mantaro Clasico',
                'descripcion' => 'Recorrido completo por artesania, historia y paisajes del Valle del Mantaro.',
                'imagen_url' => 'https://images.unsplash.com/photo-1464278533981-50106e6176b1?auto=format&fit=crop&w=1200&q=80',
                'itinerario_paradas' => json_encode([
                    ['nombre' => 'Huancayo', 'lat' => -12.0651, 'lng' => -75.2049],
                    ['nombre' => 'Cochas Chico', 'lat' => -11.9837, 'lng' => -75.2550],
                    ['nombre' => 'Cochas Grande', 'lat' => -11.9775, 'lng' => -75.2515],
                    ['nombre' => 'Concepcion', 'lat' => -11.9175, 'lng' => -75.3136],
                    ['nombre' => 'Convento de Ocopa', 'lat' => -11.9136, 'lng' => -75.2849],
                    ['nombre' => 'Jauja', 'lat' => -11.7758, 'lng' => -75.4976],
                    ['nombre' => 'Laguna de Paca', 'lat' => -11.7798, 'lng' => -75.5032],
                ], JSON_THROW_ON_ERROR),
                'duracion_horas' => 9,
                'precio_cerrado' => 360,
                'vehiculo_recomendado' => 'propia_combi',
                'activo' => true,
            ],
            [
                'titulo' => 'Ruta Cultural Huanca',
                'descripcion' => 'Experiencia cultural por los principales miradores y espacios identitarios de Huancayo.',
                'imagen_url' => 'https://images.unsplash.com/photo-1528127269322-539801943592?auto=format&fit=crop&w=1200&q=80',
                'itinerario_paradas' => json_encode([
                    ['nombre' => 'Torre Torre', 'lat' => -12.0399, 'lng' => -75.2388],
                    ['nombre' => 'Cerrito de la Libertad', 'lat' => -12.0487, 'lng' => -75.2088],
                    ['nombre' => 'Parque de la Identidad Huanca', 'lat' => -12.0576, 'lng' => -75.1944],
                    ['nombre' => 'Huancan', 'lat' => -12.1061, 'lng' => -75.2097],
                ], JSON_THROW_ON_ERROR),
                'duracion_horas' => 4,
                'precio_cerrado' => 180,
                'vehiculo_recomendado' => 'propia_auto',
                'activo' => true,
            ],
            [
                'titulo' => 'Tour Familiar en Combi',
                'descripcion' => 'Salida privada para familias y grupos que desean recorrer el valle con comodidad.',
                'imagen_url' => 'https://images.unsplash.com/photo-1503220317375-aaad61436b1b?auto=format&fit=crop&w=1200&q=80',
                'itinerario_paradas' => json_encode([
                    ['nombre' => 'Huancayo', 'lat' => -12.0651, 'lng' => -75.2049],
                    ['nombre' => 'Parque de la Identidad Huanca', 'lat' => -12.0576, 'lng' => -75.1944],
                    ['nombre' => 'Ingenio', 'lat' => -11.8908, 'lng' => -75.2761],
                    ['nombre' => 'Convento de Ocopa', 'lat' => -11.9136, 'lng' => -75.2849],
                ], JSON_THROW_ON_ERROR),
                'duracion_horas' => 6,
                'precio_cerrado' => 320,
                'vehiculo_recomendado' => 'propia_combi',
                'activo' => true,
            ],
            [
                'titulo' => 'City Tour VIP en Auto',
                'descripcion' => 'Tour privado y ejecutivo para conocer los lugares emblematicos de Huancayo.',
                'imagen_url' => 'https://images.unsplash.com/photo-1530789253388-582c481c54b0?auto=format&fit=crop&w=1200&q=80',
                'itinerario_paradas' => json_encode([
                    ['nombre' => 'Plaza Constitucion', 'lat' => -12.0663, 'lng' => -75.2056],
                    ['nombre' => 'Cerrito de la Libertad', 'lat' => -12.0487, 'lng' => -75.2088],
                    ['nombre' => 'Parque de la Identidad Huanca', 'lat' => -12.0576, 'lng' => -75.1944],
                    ['nombre' => 'Torre Torre', 'lat' => -12.0399, 'lng' => -75.2388],
                ], JSON_THROW_ON_ERROR),
                'duracion_horas' => 3,
                'precio_cerrado' => 160,
                'vehiculo_recomendado' => 'propia_auto',
                'activo' => true,
            ],
        ];
    }
}
