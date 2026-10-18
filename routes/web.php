<?php

use App\Services\Supabase\Supabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
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
        $bearerToken = (string) env('META_WA_BEARER_TOKEN');
        $phoneId = (string) env('META_WA_PHONE_NUMBER_ID');

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
        string $placaChofer = 'W3X-451'
    ): bool {
        $bearerToken = (string) env('META_WA_BEARER_TOKEN');
        $phoneId = (string) env('META_WA_PHONE_NUMBER_ID');
        $montoFormateado = number_format((float) $monto, 2, '.', '');

        // Nota técnica Meta API: el title de un botón interactivo permite máximo 20 caracteres
        $tituloAceptar = mb_substr("✅ Aceptar S/ {$montoFormateado}", 0, 20);
        $tituloRechazar = mb_substr('❌ Esperar otra', 0, 20);

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
                        'text' => "🚕 El conductor {$nombreChofer} (Auto: {$placaChofer}) ofrece llevarte por S/ {$montoFormateado}.\n\n¿Qué decides?",
                    ],
                    'action' => [
                        'buttons' => [
                            [
                                'type' => 'reply',
                                'reply' => [
                                    'id' => 'btn_aceptar_oferta',
                                    'title' => $tituloAceptar,
                                ],
                            ],
                            [
                                'type' => 'reply',
                                'reply' => [
                                    'id' => 'btn_rechazar_oferta',
                                    'title' => $tituloRechazar,
                                ],
                            ],
                        ],
                    ],
                ],
            ]);

        return $response->successful();
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

Route::get('/webhook', function (Request $request) {
    $mode = $request->query('hub_mode') ?? $request->query('hub.mode');
    $token = $request->query('hub_verify_token') ?? $request->query('hub.verify_token');
    $challenge = $request->query('hub_challenge') ?? $request->query('hub.challenge');

    if ($mode === 'subscribe' && $token === env('META_WEBHOOK_VERIFY_TOKEN', 'kaluz2026')) {
        return response((string) $challenge, 200)->header('Content-Type', 'text/plain');
    }

    return response('Forbidden', 403);
});

Route::post('/webhook', function (Request $request) {
    try {
        $body = $request->all();
        $mensaje = data_get($body, 'entry.0.changes.0.value.messages.0');

        if (! is_array($mensaje)) {
            return response('EVENT_RECEIVED', 200);
        }

        $remitente = (string) ($mensaje['from'] ?? '');
        $tipo = (string) ($mensaje['type'] ?? '');
        $bearerToken = (string) env('META_WA_BEARER_TOKEN');

        if ($remitente === '') {
            return response('EVENT_RECEIVED', 200);
        }

        // Identificar si el remitente es un Chofer registrado en Supabase (por los últimos 9 dígitos)
        $soloDigitos = preg_replace('/\D+/', '', $remitente);
        $ultimos9 = strlen($soloDigitos) >= 9 ? substr($soloDigitos, -9) : $soloDigitos;

        $choferRegistrado = Supabase::table('conductores')
            ->where('telefono_whatsapp', 'ilike', "%{$ultimos9}%")
            ->first();

        $esChoferMoroso = $choferRegistrado && ! (bool) ($choferRegistrado->habilitado ?? false);

        /*
        |--------------------------------------------------------------------------
        | 🔒 FLUJO 2: EL CHOFER (CANDADO FINANCIERO Y VALIDACIÓN OCR EN MEMORIA)
        |--------------------------------------------------------------------------
        */
        if ($tipo === 'image') {
            $mediaId = (string) data_get($mensaje, 'image.id', '');

            if ($mediaId !== '') {
                // 1. Obtener la URL de descarga desde Meta Graph API v20.0
                $metaMediaInfo = Http::withoutVerifying()
                    ->withToken($bearerToken)
                    ->get("https://graph.facebook.com/v20.0/{$mediaId}");

                $urlDescarga = $metaMediaInfo->json('url');

                if ($urlDescarga) {
                    // 2. Descargar el binario de la imagen 100% en memoria (Serverless Vercel)
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
                    $tieneMonto = str_contains($textoExtraido, '1.00') || str_contains($textoExtraido, '1,00');
                    $esValido = $tieneAppPago && $tieneMonto;

                    if ($esValido) {
                        // Desbloquear conductor en Supabase
                        Supabase::table('conductores')
                            ->where('telefono_whatsapp', 'ilike', "%{$ultimos9}%")
                            ->update([
                                'habilitado' => true,
                                'estado_comision' => 'al_dia',
                                'fecha_vencimiento_comision' => now()->addDay()->toIso8601String(),
                                'updated_at' => now()->toIso8601String(),
                            ]);

                        // FLUJO 2 - Paso 3A: Validación Exitosa del OCR
                        $textoRespuesta = "✅ ¡Pago validado exitosamente!\n\n".
                            'Tu Radar de Viajes ha sido desbloqueado. Ya puedes ingresar a tu panel web y empezar a recibir solicitudes. ¡Buen turno!';
                    } else {
                        // FLUJO 2 - Paso 3B: Falla en la Validación del OCR
                        $textoRespuesta = "❌ No pudimos validar tu comprobante.\n\n".
                            'Asegúrate de que la captura se vea nítida, muestre el logo de Yape/Plin, la fecha de hoy y el monto exacto de S/ 1.00. Vuelve a enviar la imagen, por favor.';
                    }

                    enviarTextoMetaWhatsApp($remitente, $textoRespuesta);
                }
            }

            return response('EVENT_RECEIVED', 200);
        }

        /*
        |--------------------------------------------------------------------------
        | 🚖 FLUJO 1: EL PASAJERO (PEDIR UN TAXI Y NEGOCIAR ESTILO INDRIVE)
        |--------------------------------------------------------------------------
        */
        if ($tipo === 'location') {
            $latPasajero = (float) data_get($mensaje, 'location.latitude', 0);
            $lonPasajero = (float) data_get($mensaje, 'location.longitude', 0);
            $nombreContacto = (string) data_get($body, 'entry.0.changes.0.value.contacts.0.profile.name', 'Pasajero WhatsApp');
            $direccionEnviada = (string) (data_get($mensaje, 'location.name') ?: data_get($mensaje, 'location.address') ?: "Ubicación GPS ({$latPasajero}, {$lonPasajero})");

            /*
             * EMPAREJAMIENTO HAVERSINE (< 2.0 KM) Y ACTUALIZACIÓN EN SUPABASE:
             * Si el pasajero ya registró su ruta en el Paso 2, asociamos su ubicación GPS exacta.
             */
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
            } else {
                Supabase::table('solicitudes_viaje')->insert([
                    'cliente_nombre' => $nombreContacto,
                    'cliente_telefono' => $remitente,
                    'origen_texto' => $direccionEnviada,
                    'origen_lat' => $latPasajero ?: -12.0681,
                    'origen_lng' => $lonPasajero ?: -75.2069,
                    'destino_texto' => 'Parque de la Identidad',
                    'destino_lat' => -12.0558,
                    'destino_lng' => -75.2185,
                    'tipo_servicio' => 'urbano',
                    'oferta_cliente' => 6.00,
                    'estado' => 'pendiente',
                    'created_at' => now()->toIso8601String(),
                    'updated_at' => now()->toIso8601String(),
                ]);
            }

            // FLUJO 1 - Paso 3: Confirmación de Búsqueda
            enviarTextoMetaWhatsApp(
                $remitente,
                "🔎 Ubicación recibida. Estamos alertando a los taxis a tu alrededor con tu ruta.\n\n".
                'En unos segundos te enviaré las ofertas de los conductores. ¡Atento!'
            );

            return response('EVENT_RECEIVED', 200);
        }

        if ($tipo === 'interactive') {
            $botonId = (string) ($mensaje['interactive']['button_reply']['id'] ?? '');

            $ultimaSolicitud = Supabase::table('solicitudes_viaje')
                ->where('cliente_telefono', $remitente)
                ->orderByDesc('id')
                ->first();

            if ($botonId === 'btn_aceptar_oferta') {
                $nombreConductor = 'Carlos Mendoza';
                $vehiculoConductor = 'Toyota Yaris';
                $placaConductor = 'W3X-451';
                $tarifaAcordada = '7.00';

                if ($ultimaSolicitud) {
                    $ultimaOferta = Supabase::table('ofertas_conductor')
                        ->where('solicitud_id', $ultimaSolicitud->id)
                        ->orderByDesc('id')
                        ->first();

                    $conductorIdAsignado = $ultimaOferta->conductor_id ?? $ultimaSolicitud->conductor_id;
                    $montoAcordado = $ultimaOferta->monto_contraoferta ?? $ultimaSolicitud->precio_final ?? $ultimaSolicitud->oferta_cliente ?? 7.00;
                    $tarifaAcordada = number_format((float) $montoAcordado, 2, '.', '');

                    if ($conductorIdAsignado) {
                        $chofer = Supabase::table('conductores')->where('id', $conductorIdAsignado)->first();
                        if ($chofer) {
                            $nombreConductor = (string) $chofer->nombre;
                            $vehiculoConductor = (string) $chofer->modelo_vehiculo;
                            $placaConductor = (string) $chofer->placa;
                        }
                    }

                    Supabase::table('solicitudes_viaje')->where('id', $ultimaSolicitud->id)->update([
                        'estado' => 'aceptado',
                        'conductor_id' => $conductorIdAsignado,
                        'precio_final' => (float) $montoAcordado,
                        'updated_at' => now()->toIso8601String(),
                    ]);
                }

                // FLUJO 1 - Paso 5A: Cliente acepta el viaje
                enviarTextoMetaWhatsApp(
                    $remitente,
                    "🎉 ¡Viaje confirmado!\n\n".
                    "🚗 Conductor: {$nombreConductor}\n".
                    "🚙 Vehículo: {$vehiculoConductor} ({$placaConductor})\n".
                    "💰 Tarifa acordada: S/ {$tarifaAcordada}\n\n".
                    'El conductor ya tiene tu ubicación y está en camino. ¡Gracias por usar KALUZ!'
                );
            } elseif ($botonId === 'btn_rechazar_oferta') {
                if ($ultimaSolicitud) {
                    Supabase::table('solicitudes_viaje')->where('id', $ultimaSolicitud->id)->update([
                        'estado' => 'pendiente',
                        'updated_at' => now()->toIso8601String(),
                    ]);
                }

                // FLUJO 1 - Paso 5B: Cliente rechaza la oferta
                enviarTextoMetaWhatsApp(
                    $remitente,
                    'Entendido ⏳. Hemos rechazado esa tarifa. Seguimos buscando otros conductores cercanos con mejores ofertas...'
                );
            }

            return response('EVENT_RECEIVED', 200);
        }

        if ($tipo === 'text') {
            $textoCliente = trim((string) data_get($mensaje, 'text.body', ''));
            $nombreContacto = (string) data_get($body, 'entry.0.changes.0.value.contacts.0.profile.name', 'Pasajero');

            // Detectar si el mensaje tiene formato de ruta de pasajero ("De Real Plaza a Parque de la Identidad")
            $patronViaje = '/(?:desde\s*:?\s*|de\s+|estoy\s+en\s+)(.+?)\s+(?:a\s*:?\s*|hacia\s+|para\s+|destino\s*:?\s*|voy\s+a\s+|voy\s+para\s+)([^,\.\n]+?)(?:\s+(?:por|oferta\s*:?|y\s+ofrezco|ofrezco|s\/\.?)\s*(?:s\/\.?\s*)?(\d+(?:[\.,]\d+)?))?$/is';
            $parecePagoChofer = (bool) preg_match('/\b(pagu[eé]|yape|plin|comprobante|voucher|cuota|bloqueado|desbloquea)\b/i', $textoCliente);

            // FLUJO 2 - Paso 2: Si el remitente es un chofer moroso (o dice "ya pagué") pero envía texto en lugar de foto
            if (($esChoferMoroso && ! preg_match($patronViaje, $textoCliente)) || $parecePagoChofer) {
                enviarTextoMetaWhatsApp(
                    $remitente,
                    '🤖 El sistema no detectó ninguna imagen. Por favor, adjunta la foto de la captura de Yape o Plin para poder validar tu pago.'
                );

                return response('EVENT_RECEIVED', 200);
            }

            // FLUJO 1 - Paso 2: El cliente responde con su ruta (ej. "De Real Plaza a Parque de la Identidad")
            if (preg_match($patronViaje, $textoCliente, $m)) {
                $origen = trim($m[1], " ,.:\n\r");
                $destino = trim($m[2], " ,.:\n\r");
                $oferta = 6.00;

                if (! empty($m[3])) {
                    $oferta = (float) str_replace(',', '.', $m[3]);
                } elseif (preg_match('/(?:s\/\.?\s*|por\s+|oferta\s*:?\s*|ofrezco\s+)(\d+(?:[\.,]\d+)?)/i', $textoCliente, $mPrecio)) {
                    $oferta = (float) str_replace(',', '.', $mPrecio[1]);
                }

                Supabase::table('solicitudes_viaje')->insert([
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

                enviarTextoMetaWhatsApp(
                    $remitente,
                    "¡Perfecto! 📍 Ahora necesito saber tu punto exacto.\n\n".
                    'Toca el ícono del clip (📎) o el "+" y envíame tu Ubicación actual. Así alertaré a los conductores que estén más cerca de ti.'
                );

                return response('EVENT_RECEIVED', 200);
            }

            // FLUJO 1 - Paso 1: El Saludo y la Solicitud (cuando el cliente escribe "Hola" o cualquier primer mensaje)
            enviarTextoMetaWhatsApp(
                $remitente,
                "🚕 ¡Hola! Bienvenido a Taxi KALUZ Huancayo.\n\n".
                "Para empezar, escríbeme en un solo mensaje desde dónde y hacia dónde vas.\n".
                '(Ejemplo: De Real Plaza a Parque de la Identidad)'
            );
        }
    } catch (Throwable $e) {
        Log::error('Error en Webhook WhatsApp: '.$e->getMessage());
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
