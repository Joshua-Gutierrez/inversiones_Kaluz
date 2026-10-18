<?php

use App\Services\Supabase\Supabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Webhook Oficial Meta WhatsApp Cloud API + OCR.space (100% En Memoria / Serverless)
|--------------------------------------------------------------------------
*/

Route::get('/webhook', function (Request $request) {
    $mode = $request->query('hub_mode') ?? $request->query('hub.mode');
    $token = $request->query('hub_verify_token') ?? $request->query('hub.verify_token');
    $challenge = $request->query('hub_challenge') ?? $request->query('hub.challenge');

    if ($mode === 'subscribe' && $token === env('META_WEBHOOK_VERIFY_TOKEN', 'kaluz2026')) {
        return response((string) $challenge, 200)->header('Content-Type', 'text/plain');
    }

    return response('Forbidden', 403);
});

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

if (! function_exists('enviarContraofertaInteractiva')) {
    /**
     * Envía una contraoferta estilo inDrive al pasajero por WhatsApp con botones interactivos (Meta API v20.0).
     */
    function enviarContraofertaInteractiva(string $numeroPasajero, string $nombreChofer, float|string $monto): bool
    {
        $bearerToken = (string) env('META_WA_BEARER_TOKEN');
        $phoneId = (string) env('META_WA_PHONE_NUMBER_ID');
        $montoFormateado = number_format((float) $monto, 2, '.', '');

        $response = Http::withoutVerifying()
            ->withToken($bearerToken)
            ->post("https://graph.facebook.com/v20.0/{$phoneId}/messages", [
                'messaging_product' => 'whatsapp',
                'recipient_type' => 'individual',
                'to' => $numeroPasajero,
                'type' => 'interactive',
                'interactive' => [
                    'type' => 'button',
                    'body' => [
                        'text' => "🚕 El conductor {$nombreChofer} ofrece llevarte por S/ {$montoFormateado}. ¿Qué decides?",
                    ],
                    'action' => [
                        'buttons' => [
                            [
                                'type' => 'reply',
                                'reply' => [
                                    'id' => 'btn_aceptar_oferta',
                                    'title' => '✅ Aceptar',
                                ],
                            ],
                            [
                                'type' => 'reply',
                                'reply' => [
                                    'id' => 'btn_rechazar_oferta',
                                    'title' => '❌ Rechazar',
                                ],
                            ],
                        ],
                    ],
                ],
            ]);

        return $response->successful();
    }
}

Route::post('/webhook', function (Request $request) {
    try {
        $body = $request->all();
        $mensaje = data_get($body, 'entry.0.changes.0.value.messages.0');

        if (is_array($mensaje) && ($mensaje['type'] ?? '') === 'image') {
            $remitente = (string) ($mensaje['from'] ?? '');
            $mediaId = (string) data_get($mensaje, 'image.id', '');
            $bearerToken = (string) env('META_WA_BEARER_TOKEN');
            $phoneId = (string) env('META_WA_PHONE_NUMBER_ID');

            if ($remitente !== '' && $mediaId !== '') {
                // 1. Obtener la URL de descarga desde Meta Graph API v20.0
                $metaMediaInfo = Http::withoutVerifying()
                    ->withToken($bearerToken)
                    ->get("https://graph.facebook.com/v20.0/{$mediaId}");

                $urlDescarga = $metaMediaInfo->json('url');

                if ($urlDescarga) {
                    // 2. Descargar el archivo binario de la imagen 100% en memoria (sin tocar disco en Vercel)
                    $imagenBinaria = Http::withoutVerifying()
                        ->withToken($bearerToken)
                        ->get($urlDescarga)
                        ->body();

                    // 3. Enviar el binario en memoria a OCR.space en español
                    $ocrResponse = Http::withoutVerifying()
                        ->attach('file', $imagenBinaria, 'yape.jpg')
                        ->post('https://api.ocr.space/parse/image', [
                            'apikey' => env('OCR_SPACE_API_KEY'),
                            'language' => 'spa',
                        ]);

                    $textoExtraido = strtolower((string) data_get($ocrResponse->json(), 'ParsedResults.0.ParsedText', ''));

                    // 4. Validar que contenga "yape" (o "plin") y el monto "1.00"
                    $tieneAppPago = str_contains($textoExtraido, 'yape') || str_contains($textoExtraido, 'plin');
                    $tieneMonto = str_contains($textoExtraido, '1.00');
                    $esValido = $tieneAppPago && $tieneMonto;

                    if ($esValido) {
                        // Actualizar estado del conductor en Supabase (si está registrado con ese número)
                        $ultimos9 = strlen($remitente) >= 9 ? substr(preg_replace('/\D+/', '', $remitente), -9) : $remitente;
                        Supabase::table('conductores')
                            ->where('telefono_whatsapp', 'ilike', "%{$ultimos9}%")
                            ->update([
                                'habilitado' => true,
                                'estado_comision' => 'al_dia',
                            ]);

                        $textoRespuesta = '✅ ¡Pago validado exitosamente! Tu radar de viajes está desbloqueado.';
                    } else {
                        $textoRespuesta = "❌ No pudimos validar el pago. Asegúrate de que la captura muestre la palabra 'Yape' y el monto de 'S/ 1.00'.";
                    }

                    // 5. Responder al conductor por WhatsApp Cloud API
                    Http::withoutVerifying()
                        ->withToken($bearerToken)
                        ->post("https://graph.facebook.com/v20.0/{$phoneId}/messages", [
                            'messaging_product' => 'whatsapp',
                            'to' => $remitente,
                            'type' => 'text',
                            'text' => [
                                'body' => $textoRespuesta,
                            ],
                        ]);
                }
            }
        } elseif (is_array($mensaje) && ($mensaje['type'] ?? '') === 'location') {
            $remitente = (string) ($mensaje['from'] ?? '');
            $latPasajero = (float) data_get($mensaje, 'location.latitude', 0);
            $lonPasajero = (float) data_get($mensaje, 'location.longitude', 0);
            $bearerToken = (string) env('META_WA_BEARER_TOKEN');
            $phoneId = (string) env('META_WA_PHONE_NUMBER_ID');

            /*
             * =========================================================================
             * LÓGICA DE EMPAREJAMIENTO GEOESPACIAL CON SUPABASE (RADIO < 2.0 KM)
             * =========================================================================
             * 1. Consultar a Supabase todos los choferes con estado = 'habilitado':
             *    $choferesResponse = Http::withHeaders([
             *        'apikey' => env('SUPABASE_KEY'),
             *        'Authorization' => 'Bearer ' . env('SUPABASE_KEY'),
             *    ])->get(env('SUPABASE_URL') . '/rest/v1/conductores', [
             *        'estado' => 'eq.habilitado',
             *        'select' => 'id,nombre,telefono_whatsapp,lat_actual,lng_actual',
             *    ]);
             *
             * 2. Iterar sobre los choferes habilitados y filtrar usando Haversine (< 2.0 km):
             *    $choferesCercanos = [];
             *    foreach ($choferesResponse->json() ?? [] as $chofer) {
             *        $distanciaKm = calcularDistanciaHaversine(
             *            $latPasajero,
             *            $lonPasajero,
             *            (float) $chofer['lat_actual'],
             *            (float) $chofer['lng_actual']
             *        );
             *
             *        if ($distanciaKm < 2.0) {
             *            $chofer['distancia_km'] = $distanciaKm;
             *            $choferesCercanos[] = $chofer;
             *        }
             *    }
             *
             * 3. Registrar la solicitud de viaje en Supabase asociada al pasajero y choferes cercanos:
             *    Http::withHeaders([
             *        'apikey' => env('SUPABASE_KEY'),
             *        'Authorization' => 'Bearer ' . env('SUPABASE_KEY'),
             *        'Content-Type' => 'application/json',
             *    ])->post(env('SUPABASE_URL') . '/rest/v1/solicitudes_viaje', [
             *        'cliente_telefono' => $remitente,
             *        'origen_lat' => $latPasajero,
             *        'origen_lng' => $lonPasajero,
             *        'estado' => 'pendiente',
             *        'choferes_notificados' => array_column($choferesCercanos, 'id'),
             *    ]);
             */

            // Si el cliente ya envió hace poco su texto "Desde: ... A: ...", actualizamos esa solicitud con sus coordenadas GPS reales
            $nombreContacto = (string) data_get($body, 'entry.0.changes.0.value.contacts.0.profile.name', 'Pasajero WhatsApp');
            $direccionEnviada = (string) (data_get($mensaje, 'location.name') ?: data_get($mensaje, 'location.address') ?: "Ubicación GPS ({$latPasajero}, {$lonPasajero})");

            $solicitudPendiente = Supabase::table('solicitudes_viaje')
                ->where('cliente_telefono', $remitente)
                ->where('estado', 'pendiente')
                ->orderByDesc('id')
                ->first();

            if ($solicitudPendiente) {
                Supabase::table('solicitudes_viaje')->where('id', $solicitudPendiente->id)->update([
                    'origen_lat' => $latPasajero ?: -12.0681,
                    'origen_lng' => $lonPasajero ?: -75.2069,
                    'updated_at' => now()->toIso8601String(),
                ]);
                $idRastreo = $solicitudPendiente->id;
            } else {
                $viajeCreado = Supabase::table('solicitudes_viaje')->insert([
                    'cliente_nombre' => $nombreContacto,
                    'cliente_telefono' => $remitente,
                    'origen_texto' => $direccionEnviada,
                    'origen_lat' => $latPasajero ?: -12.0681,
                    'origen_lng' => $lonPasajero ?: -75.2069,
                    'destino_texto' => 'Centro de Huancayo (Ubicación WhatsApp)',
                    'destino_lat' => -12.0651,
                    'destino_lng' => -75.2049,
                    'tipo_servicio' => 'urbano',
                    'oferta_cliente' => 6.00,
                    'estado' => 'pendiente',
                    'created_at' => now()->toIso8601String(),
                    'updated_at' => now()->toIso8601String(),
                ]);
                $idRastreo = $viajeCreado->id ?? 1;
            }

            $linkRastreo = url('/rastreo/'.$idRastreo);

            // 4. Confirmar recepción de ubicación al pasajero por WhatsApp Cloud API
            if ($remitente !== '') {
                Http::withoutVerifying()
                    ->withToken($bearerToken)
                    ->post("https://graph.facebook.com/v20.0/{$phoneId}/messages", [
                        'messaging_product' => 'whatsapp',
                        'to' => $remitente,
                        'type' => 'text',
                        'text' => [
                            'body' => "📍 Ubicación recibida. Buscando conductores a menos de 2km...\n\n🗺️ Ver ofertas y GPS en vivo:\n{$linkRastreo}",
                        ],
                    ]);
            }
        } elseif (is_array($mensaje) && ($mensaje['type'] ?? '') === 'interactive') {
            $remitente = (string) ($mensaje['from'] ?? '');
            $botonId = (string) ($mensaje['interactive']['button_reply']['id'] ?? '');
            $bearerToken = (string) env('META_WA_BEARER_TOKEN');
            $phoneId = (string) env('META_WA_PHONE_NUMBER_ID');

            $ultimaSolicitud = Supabase::table('solicitudes_viaje')
                ->where('cliente_telefono', $remitente)
                ->orderByDesc('id')
                ->first();

            if ($botonId === 'btn_aceptar_oferta') {
                /*
                 * SIMULACIÓN DE ACTUALIZACIÓN EN SUPABASE (ESTADO = 'viaje_aceptado'):
                 * Http::withHeaders([
                 *     'apikey' => env('SUPABASE_KEY'),
                 *     'Authorization' => 'Bearer ' . env('SUPABASE_KEY'),
                 *     'Content-Type' => 'application/json',
                 * ])->patch(env('SUPABASE_URL') . '/rest/v1/solicitudes_viaje?cliente_telefono=eq.' . $remitente, [
                 *     'estado' => 'viaje_aceptado',
                 * ]);
                 */
                if ($ultimaSolicitud) {
                    $ultimaOferta = Supabase::table('ofertas_conductor')
                        ->where('solicitud_id', $ultimaSolicitud->id)
                        ->orderByDesc('id')
                        ->first();

                    Supabase::table('solicitudes_viaje')->where('id', $ultimaSolicitud->id)->update([
                        'estado' => 'aceptado',
                        'conductor_id' => $ultimaOferta->conductor_id ?? $ultimaSolicitud->conductor_id,
                        'precio_final' => $ultimaOferta->monto_contraoferta ?? $ultimaSolicitud->oferta_cliente,
                        'updated_at' => now()->toIso8601String(),
                    ]);
                }

                $linkGps = url('/rastreo/'.($ultimaSolicitud->id ?? 1));

                Http::withoutVerifying()
                    ->withToken($bearerToken)
                    ->post("https://graph.facebook.com/v20.0/{$phoneId}/messages", [
                        'messaging_product' => 'whatsapp',
                        'to' => $remitente,
                        'type' => 'text',
                        'text' => [
                            'body' => "🎉 ¡Viaje confirmado! El taxi está en camino.\n\n📍 Sigue el GPS en tiempo real aquí:\n{$linkGps}",
                        ],
                    ]);
            } elseif ($botonId === 'btn_rechazar_oferta') {
                /*
                 * SIMULACIÓN DE ACTUALIZACIÓN EN SUPABASE (ESTADO = 'buscando'):
                 * Http::withHeaders([
                 *     'apikey' => env('SUPABASE_KEY'),
                 *     'Authorization' => 'Bearer ' . env('SUPABASE_KEY'),
                 *     'Content-Type' => 'application/json',
                 * ])->patch(env('SUPABASE_URL') . '/rest/v1/solicitudes_viaje?cliente_telefono=eq.' . $remitente, [
                 *     'estado' => 'buscando',
                 * ]);
                 */
                if ($ultimaSolicitud) {
                    Supabase::table('solicitudes_viaje')->where('id', $ultimaSolicitud->id)->update([
                        'estado' => 'pendiente',
                        'updated_at' => now()->toIso8601String(),
                    ]);
                }

                Http::withoutVerifying()
                    ->withToken($bearerToken)
                    ->post("https://graph.facebook.com/v20.0/{$phoneId}/messages", [
                        'messaging_product' => 'whatsapp',
                        'to' => $remitente,
                        'type' => 'text',
                        'text' => [
                            'body' => 'Buscando otros conductores con tu tarifa original...',
                        ],
                    ]);
            }
        } elseif (is_array($mensaje) && ($mensaje['type'] ?? '') === 'text') {
            $remitente = (string) ($mensaje['from'] ?? '');
            $textoCliente = trim((string) data_get($mensaje, 'text.body', ''));
            $nombreContacto = (string) data_get($body, 'entry.0.changes.0.value.contacts.0.profile.name', 'Pasajero');
            $bearerToken = (string) env('META_WA_BEARER_TOKEN');
            $phoneId = (string) env('META_WA_PHONE_NUMBER_ID');

            if ($remitente !== '' && $textoCliente !== '') {
                // Detectar si el cliente ya está enviando el formato de viaje ("Desde: X A: Y" o "De X a Y")
                $patronViaje = '/(?:desde\s*:?\s*|de\s+|estoy\s+en\s+)(.+?)\s+(?:a\s*:?\s*|hacia\s+|para\s+|destino\s*:?\s*|voy\s+a\s+|voy\s+para\s+)([^,\.\n]+?)(?:\s+(?:por|oferta\s*:?|y\s+ofrezco|ofrezco|s\/\.?)\s*(?:s\/\.?\s*)?(\d+(?:[\.,]\d+)?))?$/is';

                if (preg_match($patronViaje, $textoCliente, $m)) {
                    $origen = trim($m[1], " ,.:\n\r");
                    $destino = trim($m[2], " ,.:\n\r");
                    $oferta = 6.00;

                    if (! empty($m[3])) {
                        $oferta = (float) str_replace(',', '.', $m[3]);
                    } elseif (preg_match('/(?:s\/\.?\s*|por\s+|oferta\s*:?\s*|ofrezco\s+)(\d+(?:[\.,]\d+)?)/i', $textoCliente, $mPrecio)) {
                        $oferta = (float) str_replace(',', '.', $mPrecio[1]);
                    }

                    $viajeCreado = Supabase::table('solicitudes_viaje')->insert([
                        'cliente_nombre' => $nombreContacto,
                        'cliente_telefono' => $remitente,
                        'origen_texto' => $origen,
                        'origen_lat' => -12.0681,
                        'origen_lng' => -75.2069,
                        'destino_texto' => $destino,
                        'destino_lat' => -12.0558,
                        'destino_lng' => -75.2185,
                        'tipo_servicio' => 'urbano',
                        'oferta_cliente' => $oferta,
                        'estado' => 'pendiente',
                        'created_at' => now()->toIso8601String(),
                        'updated_at' => now()->toIso8601String(),
                    ]);

                    $linkRastreo = url('/rastreo/'.($viajeCreado->id ?? 1));
                    $ofertaFmt = number_format($oferta, 2, '.', '');

                    Http::withoutVerifying()
                        ->withToken($bearerToken)
                        ->post("https://graph.facebook.com/v20.0/{$phoneId}/messages", [
                            'messaging_product' => 'whatsapp',
                            'to' => $remitente,
                            'type' => 'text',
                            'text' => [
                                'body' => "🚖 *¡Pedido registrado en Taxi KALUZ!*\n\n".
                                    "📍 *Desde:* {$origen}\n".
                                    "🏁 *A:* {$destino}\n".
                                    "💵 *Oferta:* S/ {$ofertaFmt}\n\n".
                                    "👉 *Ahora envíanos tu UBICACIÓN actual* (tocando el clip 📎 ➔ *Ubicación*) para asignarte a los conductores a menos de 2km.\n\n".
                                    "🗺️ Ver ofertas y mapa GPS en vivo:\n{$linkRastreo}",
                            ],
                        ]);
                } else {
                    // Mensaje inicial / saludo cordial indicando cómo pedir su taxi y enviar ubicación
                    Http::withoutVerifying()
                        ->withToken($bearerToken)
                        ->post("https://graph.facebook.com/v20.0/{$phoneId}/messages", [
                            'messaging_product' => 'whatsapp',
                            'to' => $remitente,
                            'type' => 'text',
                            'text' => [
                                'body' => "👋 ¡Hola *{$nombreContacto}*! Bienvenido a *Taxi KALUZ Huancayo* 🚖✨\n\n".
                                    "Para pedir tu taxi al instante y negociar tu tarifa, por favor respóndenos indicando tu ruta así:\n\n".
                                    "📍 *Desde:* [Tu punto de partida]\n".
                                    "🏁 *A:* [Tu destino]\n".
                                    "💵 *Oferta:* S/ [Tu precio]\n\n".
                                    "*Ejemplo:*\n".
                                    "_Desde: Real Plaza A: El Tambo Oferta: S/ 6.00_\n\n".
                                    "📌 *Y envíanos tu UBICACIÓN actual* (tocando el ícono del clip 📎 ➔ *Ubicación*) para conectar con los conductores más cercanos.\n\n".
                                    '_(Si eres socio conductor, envía aquí la captura de tu Yape de S/ 1.00 para desbloquear tu radar)_.',
                            ],
                        ]);
                }
            }
        }
    } catch (Throwable $e) {
        Log::error('Error en Webhook WhatsApp OCR: '.$e->getMessage());
    }

    return response('EVENT_RECEIVED', 200);
});

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

Route::get('/dashboard', function () {
    $conductores = Supabase::table('conductores')->orderBy('id')->get();
    $solicitudes = Supabase::table('solicitudes_viaje')->orderByDesc('created_at')->limit(12)->get();

    $conductorPorValidar = $conductores->firstWhere('habilitado', false) ?? $conductores->first();

    return view('dashboard', [
        'conductores' => $conductores,
        'solicitudes' => $solicitudes,
        'conductorPorValidar' => $conductorPorValidar,
    ]);
})->name('dashboard');

Route::get('/general', function () {
    return view('general.index');
})->name('general.index');

/*
|--------------------------------------------------------------------------
| Rutas Modulares por Desarrollador (Cero Conflictos Git)
|--------------------------------------------------------------------------
*/
require __DIR__.'/despacho.php';  // 👨‍💻 Rutas Dev 1 (Despacho y PWA Conductor)
require __DIR__.'/finanzas.php';  // 👨‍💻 Rutas Dev 2 (Finanzas, Padrón y Candado S/ 1.00)
require __DIR__.'/turismo.php';   // 👨‍💻 Rutas Dev 3 (Turismo, Combis y Rastreo GPS)
require __DIR__.'/bot.php';       // 👨‍💻 Rutas Dev 4 (Bot WhatsApp y Tablero KPI)
