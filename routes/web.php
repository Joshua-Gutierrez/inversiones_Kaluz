<?php

use App\Http\Controllers\WhatsAppController;
use App\Services\Supabase\Supabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Funciones Auxiliares del Ecosistema WhatsApp Cloud API (Meta v20.0)
|--------------------------------------------------------------------------
*/

if (! function_exists('calcularDistanciaHaversine')) {
    /**
     * Calcula la distancia en kilómetros entre dos coordenadas GPS usando la Fórmula de Haversine.
     */
    function calcularDistanciaHaversine(float $latPasajero, float $lonPasajero, float $latChofer, float $lonChofer): float
    {
        $radioTierraKm = 6371.0;

        $dLat = deg2rad($latChofer - $latPasajero);
        $dLon = deg2rad($lonChofer - $lonPasajero);

        $a = sin($dLat / 2) * sin($dLat / 2)
            + cos(deg2rad($latPasajero)) * cos(deg2rad($latChofer))
            * sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return round($radioTierraKm * $c, 2);
    }
}

if (! function_exists('enviarTextoMetaWhatsApp')) {
    /**
     * Envía un mensaje de texto plano mediante Meta WhatsApp Cloud API v20.0.
     */
    function enviarTextoMetaWhatsApp(string $destinatario, string $texto): bool
    {
        $bearerToken = \App\Http\Controllers\WhatsAppController::obtenerTokenMeta();
        $phoneId = (string) env('META_WA_PHONE_NUMBER_ID', '1387228187808934');

        if ($bearerToken === '' || $phoneId === '' || $destinatario === '') {
            return false;
        }

        $response = Http::withoutVerifying()
            ->withToken($bearerToken)
            ->timeout(15)
            ->post("https://graph.facebook.com/v20.0/{$phoneId}/messages", [
                'messaging_product' => 'whatsapp',
                'recipient_type' => 'individual',
                'to' => $destinatario,
                'type' => 'text',
                'text' => [
                    'preview_url' => false,
                    'body' => $texto,
                ],
            ]);

        if (! $response->successful()) {
            \Illuminate\Support\Facades\Log::error('Meta WA texto falló a '.$destinatario.': '.$response->body());
        }

        return $response->successful();
    }
}

if (! function_exists('enviarContraofertaInteractiva')) {
    /**
     * FLUJO 1 - PASO 4: Envía la contraoferta del chofer al pasajero con botones interactivos (Meta API v20.0).
     */
    function enviarContraofertaInteractiva(
        string $numeroPasajero,
        string $nombreChofer,
        float|string $monto,
        string $placaChofer = 'W3X-451',
        int $tiempoLlegadaMin = 5,
        int $ofertaId = 0
    ): bool {
        $bearerToken = \App\Http\Controllers\WhatsAppController::obtenerTokenMeta();
        $phoneId = (string) env('META_WA_PHONE_NUMBER_ID', '1387228187808934');
        $montoFormateado = number_format((float) $monto, 2, '.', '');

        // Nota técnica Meta API: el title de un botón interactivo permite máximo 20 caracteres UTF-16
        $tituloAceptar = mb_substr("✅ Aceptar S/{$montoFormateado}", 0, 20);
        $tituloRechazar = mb_substr('❌ Esperar otra', 0, 20);

        $idAceptar = $ofertaId > 0 ? "btn_aceptar_oferta_{$ofertaId}" : 'btn_aceptar_oferta';
        $idRechazar = $ofertaId > 0 ? "btn_rechazar_oferta_{$ofertaId}" : 'btn_rechazar_oferta';

        $textoCuerpo = "🚕 El conductor {$nombreChofer} (Auto: {$placaChofer}) ofrece llevarte por S/ {$montoFormateado} (llega en ~{$tiempoLlegadaMin} min).\n\n¿Qué decides?";

        $response = Http::withoutVerifying()
            ->withToken($bearerToken)
            ->timeout(15)
            ->post("https://graph.facebook.com/v20.0/{$phoneId}/messages", [
                'messaging_product' => 'whatsapp',
                'recipient_type' => 'individual',
                'to' => $numeroPasajero,
                'type' => 'interactive',
                'interactive' => [
                    'type' => 'button',
                    'body' => [
                        'text' => $textoCuerpo,
                    ],
                    'action' => [
                        'buttons' => [
                            [
                                'type' => 'reply',
                                'reply' => [
                                    'id' => $idAceptar,
                                    'title' => $tituloAceptar,
                                ],
                            ],
                            [
                                'type' => 'reply',
                                'reply' => [
                                    'id' => $idRechazar,
                                    'title' => $tituloRechazar,
                                ],
                            ],
                        ],
                    ],
                ],
            ]);

        if ($response->successful()) {
            return true;
        }

        \Illuminate\Support\Facades\Log::warning('Meta WA interactivo falló a '.$numeroPasajero.': '.$response->body().'. Reintentando en texto plano.');

        // Respaldo en texto plano si la sesión interactiva tuviese alguna restricción
        return enviarTextoMetaWhatsApp(
            $numeroPasajero,
            $textoCuerpo."\n\nResponde *ACEPTAR* para confirmar o *RECHAZAR* para esperar otra oferta."
        );
    }
}

if (! function_exists('enviarCobroMatutinoChofer')) {
    /**
     * FLUJO 2 - PASO 1: Envía la alerta de cobro matutino (S/ 1.00) al WhatsApp del chofer.
     */
    function enviarCobroMatutinoChofer(string $numeroChofer): bool
    {
        $soloDigitos = preg_replace('/\D+/', '', $numeroChofer);
        if (strlen($soloDigitos) === 9) {
            $soloDigitos = '51'.$soloDigitos;
        }

        $mensajeCobro = "⚠️ Alerta de Sistema KALUZ\n\n".
            "Hola, tu saldo diario para recibir viajes está inactivo. Tienes una cuota pendiente de S/ 1.00 por el uso de la plataforma de hoy.\n\n".
            "📱 Yape/Plin: 999 888 777\n\n".
            'Por favor, envía la captura de pantalla de tu pago a este chat para habilitar tu Radar de Viajes automáticamente.';

        return enviarTextoMetaWhatsApp($soloDigitos, $mensajeCobro);
    }
}

/*
|--------------------------------------------------------------------------
| Webhook Oficial Meta WhatsApp Cloud API (Flujo 1 Pasajero + Flujo 2 Chofer)
|--------------------------------------------------------------------------
*/

Route::get('/webhook', [WhatsAppController::class, 'verify']);
Route::post('/webhook', [WhatsAppController::class, 'handle']);

/*
|--------------------------------------------------------------------------
| Autenticación Base y Panel General (Supabase Login Intacto)
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return view('login');
})->name('login');

Route::post('/login', function (Request $request) {
    $usuario = $request->input('usuario');
    $password = $request->input('password');

    $supabaseUrl = env('SUPABASE_URL');
    $supabaseKey = env('SUPABASE_KEY');

    $response = Http::withoutVerifying()->withHeaders([
        'apikey' => $supabaseKey,
        'Authorization' => 'Bearer '.$supabaseKey,
    ])->get("{$supabaseUrl}/rest/v1/usuarios", [
        'usuario' => "eq.{$usuario}",
        'password' => "eq.{$password}",
        'select' => '*',
    ]);

    if ($response->successful() && count($response->json()) > 0) {
        $user = $response->json()[0];
        session(['usuario' => $user['usuario'], 'nombre' => $user['nombre'] ?? $user['usuario']]);

        return redirect('/dashboard');
    }

    return back()->withErrors(['error' => 'Usuario o contraseña incorrectos en Supabase.'])->withInput();
});

if (! function_exists('armarFlotaEnVivoKaluz')) {
    /**
     * Construye el estado GPS en tiempo real de toda la flota KALUZ cruzando conductores y viajes activos.
     */
    function armarFlotaEnVivoKaluz($conductores, $solicitudes): array
    {
        $geocodificador = app(\App\Services\Despacho\GeocodingService::class);

        $viajesActivosPorConductor = $solicitudes
            ->whereIn('estado', ['asignado', 'en_ruta'])
            ->whereNotNull('conductor_id')
            ->keyBy(fn ($s) => (int) $s->conductor_id);

        // Puntos estratégicos en el centro de Huancayo (radio ~1 km para visualización nítida en zoom 15)
        $puntosReferenciaHuancayo = [
            [-12.0651, -75.2049], // Parque Constitución
            [-12.0672, -75.2078], // Real Plaza Huancayo
            [-12.0628, -75.2092], // El Tambo / Av. Mariscal Castilla
            [-12.0608, -75.2018], // Av. San Carlos / U. Continental
            [-12.0688, -75.2032], // Calle Real / Chilca
            [-12.0640, -75.2115], // Av. Ferrocarril / Open Plaza
            [-12.0618, -75.2055], // Jr. Puno / Centro Cívico
            [-12.0665, -75.2015], // Av. Giráldez
        ];

        return $conductores->values()->map(function ($c, $idx) use ($viajesActivosPorConductor, $puntosReferenciaHuancayo, $geocodificador) {
            $id = (int) $c->id;
            $habilitado = (bool) $c->habilitado;
            $viaje = $viajesActivosPorConductor->get($id);

            $latRaw = isset($c->lat_actual) ? (float) $c->lat_actual : null;
            $lngRaw = isset($c->lng_actual) ? (float) $c->lng_actual : null;

            $esCoordenadaDefaultRepetida = $idx > 0
                && $latRaw !== null
                && abs($latRaw - (-12.0651)) < 0.0001
                && abs($lngRaw - (-75.2049)) < 0.0001;

            // Si la coordenada está fuera del casco urbano de Huancayo (>2 km), ubicarla en su sector de Huancayo
            $fueraCascoUrbano = $latRaw !== null
                && (abs($latRaw - (-12.0651)) > 0.020 || abs($lngRaw - (-75.2049)) > 0.020);

            if ($latRaw === null || $lngRaw === null || $esCoordenadaDefaultRepetida || $fueraCascoUrbano) {
                $ref = $puntosReferenciaHuancayo[$idx % count($puntosReferenciaHuancayo)];
                $lat = $ref[0];
                $lng = $ref[1];
            } else {
                $lat = $latRaw;
                $lng = $lngRaw;
            }

            $haceSegundos = ! empty($c->updated_at)
                ? (int) max(0, now()->diffInSeconds(\Illuminate\Support\Carbon::parse($c->updated_at)))
                : 120;

            $pwaGpsActivo = $haceSegundos <= 600 || $viaje !== null || $habilitado;

            $datosViaje = null;
            if ($viaje) {
                $coordOrigen = (! empty($viaje->origen_lat) && ! empty($viaje->origen_lng))
                    ? ['lat' => (float) $viaje->origen_lat, 'lng' => (float) $viaje->origen_lng]
                    : $geocodificador->coordenadas((string) $viaje->origen_texto);

                $coordDestino = (! empty($viaje->destino_lat) && ! empty($viaje->destino_lng))
                    ? ['lat' => (float) $viaje->destino_lat, 'lng' => (float) $viaje->destino_lng]
                    : $geocodificador->coordenadas((string) $viaje->destino_texto);

                $datosViaje = [
                    'id' => (int) $viaje->id,
                    'estado' => (string) $viaje->estado,
                    'etiqueta' => $viaje->estado === 'en_ruta' ? 'EN RUTA AL DESTINO' : 'YENDO A RECOGER PASAJERO',
                    'cliente_nombre' => (string) ($viaje->cliente_nombre ?: 'Pasajero WhatsApp'),
                    'cliente_telefono' => (string) ($viaje->cliente_telefono ?? ''),
                    'origen_texto' => (string) $viaje->origen_texto,
                    'destino_texto' => (string) $viaje->destino_texto,
                    'origen_lat' => (float) $coordOrigen['lat'],
                    'origen_lng' => (float) $coordOrigen['lng'],
                    'destino_lat' => (float) $coordDestino['lat'],
                    'destino_lng' => (float) $coordDestino['lng'],
                    'tarifa' => (float) ($viaje->precio_final ?? $viaje->oferta_cliente ?? 0),
                    'url_rastreo' => url('/rastreo/'.$viaje->id),
                ];
            }

            return [
                'id' => $id,
                'nombre' => preg_replace('/\s*\(.*?\)/', '', (string) $c->nombre),
                'placa' => (string) $c->placa,
                'modelo_vehiculo' => (string) $c->modelo_vehiculo,
                'tipo_flota' => (string) ($c->tipo_flota ?? 'afiliado'),
                'telefono' => (string) ($c->telefono_whatsapp ?? ''),
                'habilitado' => $habilitado,
                'en_servicio' => $viaje !== null,
                'pwa_gps_activo' => $pwaGpsActivo,
                'hace_segundos' => $haceSegundos,
                'lat' => round($lat, 6),
                'lng' => round($lng, 6),
                'viaje' => $datosViaje,
            ];
        })->all();
    }
}

Route::get('/dashboard', function () {
    $conductores = Supabase::table('conductores')->orderBy('id')->get();
    $solicitudes = Supabase::table('solicitudes_viaje')->orderByDesc('id')->limit(25)->get();

    $conductorPorValidar = $conductores->firstWhere('habilitado', false) ?? $conductores->first();
    $flotaEnVivo = armarFlotaEnVivoKaluz($conductores, $solicitudes);

    return view('dashboard', [
        'conductores' => $conductores,
        'solicitudes' => $solicitudes,
        'conductorPorValidar' => $conductorPorValidar,
        'flotaEnVivo' => $flotaEnVivo,
    ]);
})->name('dashboard');

Route::get('/api/torre-control/flota', function () {
    $conductores = Supabase::table('conductores')->orderBy('id')->get();
    $solicitudes = Supabase::table('solicitudes_viaje')->orderByDesc('id')->limit(25)->get();
    $flota = armarFlotaEnVivoKaluz($conductores, $solicitudes);

    return response()->json([
        'ok' => true,
        'flota' => $flota,
        'resumen' => [
            'total_flota' => count($flota),
            'en_viaje' => collect($flota)->where('en_servicio', true)->count(),
            'en_radar_gps' => collect($flota)->where('habilitado', true)->where('en_servicio', false)->count(),
            'bloqueados' => collect($flota)->where('habilitado', false)->count(),
        ],
    ]);
})->name('dashboard.api.flota');

Route::post('/api/torre-control/whatsapp-token', function (Request $request) {
    $token = trim((string) $request->input('token', ''));
    if ($token === '') {
        return response()->json(['ok' => false, 'mensaje' => 'El token no puede estar vacío.'], 422);
    }

    try {
        $existe = Supabase::table('usuarios')->where('usuario', 'meta_wa_token')->first();
        if ($existe) {
            Supabase::table('usuarios')->where('usuario', 'meta_wa_token')->update([
                'password' => $token,
                'nombre' => 'Token Meta Actualizado '.now('America/Lima')->format('d/m/Y H:i'),
            ]);
        } else {
            Supabase::table('usuarios')->insert([
                'usuario' => 'meta_wa_token',
                'password' => $token,
                'nombre' => 'Token Meta Actualizado '.now('America/Lima')->format('d/m/Y H:i'),
            ]);
        }

        return response()->json([
            'ok' => true,
            'mensaje' => 'Token de WhatsApp actualizado exitosamente en Supabase.',
        ]);
    } catch (\Throwable $e) {
        return response()->json(['ok' => false, 'mensaje' => 'Error al guardar token: '.$e->getMessage()], 500);
    }
})->name('dashboard.api.whatsapp-token');


Route::get('/general', function () {
    return view('general.index');
})->name('general.index');

Route::get('/manifest.json', function () {
    $path = public_path('manifest.json');
    if (file_exists($path)) {
        return response(file_get_contents($path), 200)
            ->header('Content-Type', 'application/manifest+json');
    }

    return response()->json([
        'name' => 'Taxi KALUZ Conductor',
        'short_name' => 'KALUZ',
        'start_url' => '/conductor/radar',
        'display' => 'standalone',
        'background_color' => '#081124',
        'theme_color' => '#0A1329',
    ]);
});

Route::get('/sw.js', function () {
    $path = public_path('sw.js');
    $content = file_exists($path) ? file_get_contents($path) : 'self.addEventListener("fetch", () => {});';

    return response($content, 200)
        ->header('Content-Type', 'application/javascript');
});

// Rutas públicas para servir imágenes de Paquetes Turísticos en Web y en Meta WhatsApp Cloud API
Route::get('/storage/turismo/{archivo}', function (string $archivo) {
    $pathStorage = storage_path('app/public/turismo/'.basename($archivo));
    $pathFallback = public_path('images/turismo-huancayo.jpg');
    $pathFinal = file_exists($pathStorage) ? $pathStorage : $pathFallback;

    if (file_exists($pathFinal)) {
        return response(file_get_contents($pathFinal), 200)
            ->header('Content-Type', 'image/jpeg')
            ->header('Cache-Control', 'public, max-age=86400');
    }

    abort(404);
});

Route::get('/turismo/imagen/{id}.jpg', function (int $id) {
    $paquete = Supabase::table('paquetes_turisticos')->where('id', $id)->first();
    $imgUrl = (string) ($paquete->imagen_url ?? '');

    if (str_starts_with($imgUrl, 'data:image/')) {
        if (preg_match('/^data:(image\/[a-zA-Z0-9\+\-\.]+);base64,(.+)$/s', $imgUrl, $matches)) {
            $binario = base64_decode($matches[2], true);
            if ($binario !== false) {
                return response($binario, 200)
                    ->header('Content-Type', $matches[1])
                    ->header('Cache-Control', 'public, max-age=3600');
            }
        }
    }

    if (str_starts_with($imgUrl, 'http://') || str_starts_with($imgUrl, 'https://')) {
        return redirect()->away($imgUrl);
    }

    $pathFallback = public_path('images/turismo-huancayo.jpg');
    if (file_exists($pathFallback)) {
        return response(file_get_contents($pathFallback), 200)
            ->header('Content-Type', 'image/jpeg')
            ->header('Cache-Control', 'public, max-age=86400');
    }

    abort(404);
})->whereNumber('id')->name('turismo.imagen');

/*
|--------------------------------------------------------------------------
| Rutas Modulares por Desarrollador (Cero Conflictos Git)
|--------------------------------------------------------------------------
*/
require __DIR__.'/despacho.php';  // 👨‍💻 Rutas Dev 1 (Despacho y PWA Conductor)
require __DIR__.'/finanzas.php';  // 👨‍💻 Rutas Dev 2 (Finanzas, Padrón y Candado S/ 1.00)
require __DIR__.'/turismo.php';   // 👨‍💻 Rutas Dev 3 (Turismo, Combis y Rastreo GPS)
require __DIR__.'/bot.php';       // 👨‍💻 Rutas Dev 4 (Bot WhatsApp y Tablero KPI)
