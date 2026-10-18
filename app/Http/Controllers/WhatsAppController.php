<?php

namespace App\Http\Controllers;

use App\Services\Supabase\Supabase;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class WhatsAppController extends Controller
{
    /**
     * GET /api/webhook
     * Verificación inicial del Webhook requerida por Meta WhatsApp Cloud API.
     */
    public function verify(Request $request): Response|JsonResponse
    {
        $mode = $request->query('hub_mode') ?? $request->query('hub.mode');
        $token = $request->query('hub_verify_token') ?? $request->query('hub.verify_token');
        $challenge = $request->query('hub_challenge') ?? $request->query('hub.challenge');

        $verifyToken = env('META_WEBHOOK_VERIFY_TOKEN', env('WHATSAPP_VERIFY_TOKEN', 'kaluz2026'));

        if ($mode === 'subscribe' && $token === $verifyToken && $challenge !== null) {
            return response((string) $challenge, 200)
                ->header('Content-Type', 'text/plain');
        }

        return response()->json([
            'status' => 'error',
            'message' => 'Token de verificación inválido.',
        ], 403);
    }

    /**
     * Alias compatible con rutas existentes.
     */
    public function verificarWebhook(Request $request): Response|JsonResponse
    {
        return $this->verify($request);
    }

    /**
     * POST /api/webhook
     * Procesa Flujo 1 (Pasajero: Pedir Taxi y Negociar) y Flujo 2 (Chofer: Candado Financiero OCR).
     * Siempre devuelve HTTP 200 para evitar que Meta desactive el webhook en Vercel.
     */
    public function handle(Request $request): Response|JsonResponse
    {
        try {
            $payload = $request->all();
            $message = data_get($payload, 'entry.0.changes.0.value.messages.0');

            if (! is_array($message)) {
                return response('EVENT_RECEIVED', 200);
            }

            $remitente = (string) ($message['from'] ?? '');
            $tipo = (string) ($message['type'] ?? '');

            if ($remitente === '') {
                return response('EVENT_RECEIVED', 200);
            }

            // Distinguir rol: verificar si el remitente es un Chofer registrado en Supabase
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
                $mediaId = (string) data_get($message, 'image.id', '');

                if ($mediaId === '') {
                    return response('EVENT_RECEIVED', 200);
                }

                // 1. Obtener la URL de descarga desde Meta Graph API v20.0
                $mediaUrl = $this->obtenerUrlMediaMeta($mediaId);

                if (! $mediaUrl) {
                    $this->enviarMensajeWhatsApp(
                        $remitente,
                        "❌ No pudimos validar tu comprobante.\n\n".
                        'Asegúrate de que la captura se vea nítida, muestre el logo de Yape/Plin, la fecha de hoy y el monto exacto de S/ 1.00. Vuelve a enviar la imagen, por favor.'
                    );

                    return response('EVENT_RECEIVED', 200);
                }

                // 2. Descargar el archivo binario de la imagen 100% en memoria (Serverless Vercel)
                $binarioImagen = $this->descargarBinarioMeta($mediaUrl);

                if (! $binarioImagen) {
                    $this->enviarMensajeWhatsApp(
                        $remitente,
                        "❌ No pudimos validar tu comprobante.\n\n".
                        'Asegúrate de que la captura se vea nítida, muestre el logo de Yape/Plin, la fecha de hoy y el monto exacto de S/ 1.00. Vuelve a enviar la imagen, por favor.'
                    );

                    return response('EVENT_RECEIVED', 200);
                }

                // 3. Enviar el binario en memoria mediante Http::attach() a OCR.space API (idioma: español)
                $textoExtraido = $this->extraerTextoMemoriaOcrSpace($binarioImagen);

                // 4. Validar comprobante (Yape/Plin + Monto 1.00)
                $validacion = $this->validarComprobanteOcr($textoExtraido);

                if ($validacion['es_valido']) {
                    // 5. Actualizar estado del conductor a habilitado en Supabase
                    $this->habilitarConductorEnSupabase(
                        $remitente,
                        $validacion['metodo'],
                        $validacion['codigo_operacion'],
                        $textoExtraido
                    );

                    // FLUJO 2 - Paso 3A: Validación Exitosa del OCR
                    $this->enviarMensajeWhatsApp(
                        $remitente,
                        "✅ ¡Pago validado exitosamente!\n\n".
                        'Tu Radar de Viajes ha sido desbloqueado. Ya puedes ingresar a tu panel web y empezar a recibir solicitudes. ¡Buen turno!'
                    );
                } else {
                    // FLUJO 2 - Paso 3B: Falla en la Validación del OCR
                    $this->enviarMensajeWhatsApp(
                        $remitente,
                        "❌ No pudimos validar tu comprobante.\n\n".
                        'Asegúrate de que la captura se vea nítida, muestre el logo de Yape/Plin, la fecha de hoy y el monto exacto de S/ 1.00. Vuelve a enviar la imagen, por favor.'
                    );
                }

                return response('EVENT_RECEIVED', 200);
            }

            /*
            |--------------------------------------------------------------------------
            | 🚖 FLUJO 1: EL PASAJERO (PEDIR UN TAXI Y NEGOCIAR ESTILO INDRIVE)
            |--------------------------------------------------------------------------
            */
            if ($tipo === 'location') {
                $latPasajero = (float) data_get($message, 'location.latitude', 0);
                $lonPasajero = (float) data_get($message, 'location.longitude', 0);
                $nombreContacto = (string) data_get($payload, 'entry.0.changes.0.value.contacts.0.profile.name', 'Pasajero WhatsApp');
                $direccionEnviada = (string) (data_get($message, 'location.name') ?: data_get($message, 'location.address') ?: "Ubicación GPS ({$latPasajero}, {$lonPasajero})");

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
                $this->enviarMensajeWhatsApp(
                    $remitente,
                    "🔎 Ubicación recibida. Estamos alertando a los taxis a tu alrededor con tu ruta.\n\n".
                    'En unos segundos te enviaré las ofertas de los conductores. ¡Atento!'
                );

                return response('EVENT_RECEIVED', 200);
            }

            if ($tipo === 'interactive') {
                $botonId = (string) ($message['interactive']['button_reply']['id'] ?? '');

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
                    $this->enviarMensajeWhatsApp(
                        $remitente,
                        "🎉 ¡Viaje confirmado!\n\n".
                        "🚗 Conductor: {$nombreConductor}\n".
                        "🚙 Vehículo: {$vehiculoConductor} ({$placaConductor})\n".
                        "💰 Tarifa acordada: S/ {$tarifaAcordada}\n\n".
                        'El conductor ya tiene tu ubicación y está en camino. ¡Gracias por usar KALUZ!'
                    );

                    return response('EVENT_RECEIVED', 200);
                }

                if ($botonId === 'btn_rechazar_oferta') {
                    if ($ultimaSolicitud) {
                        Supabase::table('solicitudes_viaje')->where('id', $ultimaSolicitud->id)->update([
                            'estado' => 'pendiente',
                            'updated_at' => now()->toIso8601String(),
                        ]);
                    }

                    // FLUJO 1 - Paso 5B: Cliente rechaza la oferta
                    $this->enviarMensajeWhatsApp(
                        $remitente,
                        'Entendido ⏳. Hemos rechazado esa tarifa. Seguimos buscando otros conductores cercanos con mejores ofertas...'
                    );

                    return response('EVENT_RECEIVED', 200);
                }
            }

            if ($tipo === 'text') {
                $cuerpoTexto = trim((string) data_get($message, 'text.body', ''));
                $nombreContacto = (string) data_get($payload, 'entry.0.changes.0.value.contacts.0.profile.name', 'Pasajero');

                $patronViaje = '/(?:desde\s*:?\s*|de\s+|estoy\s+en\s+)(.+?)\s+(?:a\s*:?\s*|hacia\s+|para\s+|destino\s*:?\s*|voy\s+a\s+|voy\s+para\s+)([^,\.\n]+?)(?:\s+(?:por|oferta\s*:?|y\s+ofrezco|ofrezco|s\/\.?)\s*(?:s\/\.?\s*)?(\d+(?:[\.,]\d+)?))?$/is';
                $parecePagoChofer = (bool) preg_match('/\b(pagu[eé]|yape|plin|comprobante|voucher|cuota|bloqueado|desbloquea)\b/i', $cuerpoTexto);

                // FLUJO 2 - Paso 2: Si el chofer envía texto en lugar de foto ("ya pagué")
                if (($esChoferMoroso && ! preg_match($patronViaje, $cuerpoTexto)) || $parecePagoChofer) {
                    $this->enviarMensajeWhatsApp(
                        $remitente,
                        '🤖 El sistema no detectó ninguna imagen. Por favor, adjunta la foto de la captura de Yape o Plin para poder validar tu pago.'
                    );

                    return response('EVENT_RECEIVED', 200);
                }

                // FLUJO 1 - Paso 2: El cliente responde con su ruta (ej. "De Real Plaza a Parque de la Identidad")
                if (preg_match($patronViaje, $cuerpoTexto, $m)) {
                    $origen = trim($m[1], " ,.:\n\r");
                    $destino = trim($m[2], " ,.:\n\r");
                    $oferta = 6.00;

                    if (! empty($m[3])) {
                        $oferta = (float) str_replace(',', '.', $m[3]);
                    } elseif (preg_match('/(?:s\/\.?\s*|por\s+|oferta\s*:?\s*|ofrezco\s+)(\d+(?:[\.,]\d+)?)/i', $cuerpoTexto, $mPrecio)) {
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

                    $this->enviarMensajeWhatsApp(
                        $remitente,
                        "¡Perfecto! 📍 Ahora necesito saber tu punto exacto.\n\n".
                        'Toca el ícono del clip (📎) o el "+" y envíame tu Ubicación actual. Así alertaré a los conductores que estén más cerca de ti.'
                    );

                    return response('EVENT_RECEIVED', 200);
                }

                // FLUJO 1 - Paso 1: El Saludo y la Solicitud (cuando el cliente escribe "Hola" o cualquier primer mensaje)
                $this->enviarMensajeWhatsApp(
                    $remitente,
                    "🚕 ¡Hola! Bienvenido a Taxi KALUZ Huancayo.\n\n".
                    "Para empezar, escríbeme en un solo mensaje desde dónde y hacia dónde vas.\n".
                    '(Ejemplo: De Real Plaza a Parque de la Identidad)'
                );
            }

            return response('EVENT_RECEIVED', 200);
        } catch (Throwable $e) {
            Log::error('Error en WhatsAppController@handle: '.$e->getMessage(), [
                'exception' => $e,
            ]);

            return response('EVENT_RECEIVED', 200);
        }
    }

    /**
     * Calcula la distancia en kilómetros entre el pasajero y un chofer usando la Fórmula de Haversine.
     */
    public function calcularDistanciaHaversine(float $latPasajero, float $lonPasajero, float $latChofer, float $lonChofer): float
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

    /**
     * Alias compatible con rutas previas.
     */
    public function recibirMensaje(Request $request): Response|JsonResponse
    {
        return $this->handle($request);
    }

    /**
     * Consulta https://graph.facebook.com/v20.0/{media_id} para obtener la URL de descarga.
     */
    protected function obtenerUrlMediaMeta(string $mediaId): ?string
    {
        $token = (string) env('META_WA_BEARER_TOKEN', '');

        $response = Http::withoutVerifying()
            ->withToken($token)
            ->timeout(15)
            ->get("https://graph.facebook.com/v20.0/{$mediaId}");

        if (! $response->successful()) {
            return null;
        }

        return $response->json('url');
    }

    /**
     * Descarga el binario de la imagen 100% en memoria desde la URL temporal de Meta.
     */
    protected function descargarBinarioMeta(string $mediaUrl): ?string
    {
        $token = (string) env('META_WA_BEARER_TOKEN', '');

        $response = Http::withoutVerifying()
            ->withToken($token)
            ->timeout(20)
            ->get($mediaUrl);

        if (! $response->successful()) {
            return null;
        }

        return $response->body();
    }

    /**
     * Envía el binario en memoria mediante Http::attach() a https://api.ocr.space/parse/image con language='spa'.
     */
    protected function extraerTextoMemoriaOcrSpace(string $imagenBinaria): string
    {
        $apiKey = (string) env('OCR_SPACE_API_KEY', 'K84914448788957');

        $response = Http::withoutVerifying()
            ->timeout(25)
            ->attach('file', $imagenBinaria, 'yape.jpg')
            ->post('https://api.ocr.space/parse/image', [
                'apikey' => $apiKey,
                'language' => 'spa',
            ]);

        if (! $response->successful()) {
            return '';
        }

        return trim((string) data_get($response->json(), 'ParsedResults.0.ParsedText', ''));
    }

    /**
     * Valida el texto extraído por OCR.space (Yape/Plin + Monto 1.00).
     */
    public function validarComprobanteOcr(string $texto): array
    {
        $textoMin = strtolower($texto);
        $errores = [];

        $tieneYapeOPlin = str_contains($textoMin, 'yape') || str_contains($textoMin, 'plin');
        $metodoDetectado = str_contains($textoMin, 'plin') ? 'PLIN' : 'YAPE';

        if (! $tieneYapeOPlin) {
            $errores[] = 'No se detectó Yape ni Plin';
        }

        $tieneMontoExacto = str_contains($textoMin, '1.00')
            || str_contains($textoMin, '1,00')
            || (bool) preg_match('/S\/\.?\s*1(?:\b|[\.,]00)/i', $texto);

        if (! $tieneMontoExacto) {
            $errores[] = 'No se encontró el monto S/ 1.00';
        }

        $codigoOperacion = null;
        if (preg_match('/(?:operaci[oó]n|seguridad|c[oó]digo|nro\.?)[\s:\.\-#]*([0-9]{3,10})/i', $texto, $matchCodigo)) {
            $codigoOperacion = $matchCodigo[1];
        } elseif (preg_match('/\b([0-9]{6,8})\b/', $texto, $matchNumero)) {
            $codigoOperacion = $matchNumero[1];
        }

        return [
            'es_valido' => $tieneYapeOPlin && $tieneMontoExacto,
            'tiene_yape_plin' => $tieneYapeOPlin,
            'tiene_monto_exacto' => $tieneMontoExacto,
            'metodo' => $metodoDetectado,
            'codigo_operacion' => $codigoOperacion,
            'errores' => $errores,
            'texto_ocr' => $texto,
        ];
    }

    /**
     * Actualiza en Supabase el estado del conductor correspondiente a ese número de WhatsApp a "habilitado".
     */
    protected function habilitarConductorEnSupabase(
        string $telefonoRemitente,
        string $metodoPago,
        ?string $codigoOperacion,
        string $textoOcr
    ): ?array {
        $soloDigitos = preg_replace('/\D+/', '', $telefonoRemitente);
        $ultimos9 = strlen($soloDigitos) >= 9 ? substr($soloDigitos, -9) : $soloDigitos;
        $ahoraLima = Carbon::now('America/Lima');

        Supabase::table('conductores')
            ->where('telefono_whatsapp', 'ilike', "%{$ultimos9}%")
            ->update([
                'habilitado' => true,
                'estado_comision' => 'al_dia',
                'fecha_vencimiento_comision' => $ahoraLima->copy()->addDay()->toIso8601String(),
                'updated_at' => $ahoraLima->toIso8601String(),
            ]);

        $conductor = Supabase::table('conductores')
            ->where('telefono_whatsapp', 'ilike', "%{$ultimos9}%")
            ->first();

        if ($conductor && isset($conductor->id)) {
            try {
                Supabase::table('pagos_diarios')->insert([
                    'conductor_id' => $conductor->id,
                    'monto' => 1.00,
                    'fecha_correspondiente' => $ahoraLima->toDateString(),
                    'codigo_operacion' => $codigoOperacion ?? 'OCR-'.strtoupper(substr(md5($textoOcr), 0, 6)),
                    'estado_verificacion' => 'verificado',
                ]);
            } catch (Throwable) {
                // Continuar si pagos_diarios tiene esquema distinto
            }
        }

        return $conductor ? (array) $conductor : null;
    }

    /**
     * Envía un mensaje de texto de WhatsApp usando el endpoint /messages de Meta.
     */
    protected function enviarMensajeWhatsApp(string $destinatario, string $texto): bool
    {
        $phoneNumberId = (string) env('META_WA_PHONE_NUMBER_ID', '1387228187808934');
        $token = (string) env('META_WA_BEARER_TOKEN', '');

        if ($token === '' || $phoneNumberId === '') {
            return false;
        }

        $response = Http::withoutVerifying()
            ->withToken($token)
            ->timeout(15)
            ->post("https://graph.facebook.com/v20.0/{$phoneNumberId}/messages", [
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
