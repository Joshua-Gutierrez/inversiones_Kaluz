<?php

namespace App\Http\Controllers;

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
     * Procesa los mensajes entrantes de WhatsApp Cloud API (Candado Financiero OCR Yape/Plin).
     * Siempre devuelve HTTP 200 para evitar que Meta desactive el webhook en producción (Vercel).
     */
    public function handle(Request $request): JsonResponse
    {
        $tempFilePath = null;

        try {
            $payload = $request->all();

            $message = data_get($payload, 'entry.0.changes.0.value.messages.0');

            // Si es una notificación de estado de Meta (sent, delivered, read) o no contiene mensaje
            if (! is_array($message)) {
                return response()->json(['status' => 'EVENT_RECEIVED'], 200);
            }

            $remitente = (string) ($message['from'] ?? '');
            $tipo = (string) ($message['type'] ?? '');

            if ($remitente === '') {
                return response()->json(['status' => 'NO_SENDER'], 200);
            }

            // Si el conductor envía una imagen (captura de pantalla de Yape / Plin)
            if ($tipo === 'image') {
                $mediaId = (string) data_get($message, 'image.id', '');

                if ($mediaId === '') {
                    return response()->json(['status' => 'NO_MEDIA_ID'], 200);
                }

                // 1. Obtener la URL de descarga desde Meta Graph API v20.0
                $mediaUrl = $this->obtenerUrlMediaMeta($mediaId);

                if (! $mediaUrl) {
                    $this->enviarMensajeWhatsApp(
                        $remitente,
                        '⚠️ Taxi KALUZ: No pudimos descargar tu comprobante desde WhatsApp. Por favor, intenta enviarlo nuevamente.'
                    );

                    return response()->json(['status' => 'MEDIA_URL_ERROR'], 200);
                }

                // 2. Descargar el archivo binario en memoria y guardarlo temporalmente en /tmp (Serverless Vercel)
                $binarioImagen = $this->descargarBinarioMeta($mediaUrl);

                if (! $binarioImagen) {
                    $this->enviarMensajeWhatsApp(
                        $remitente,
                        '⚠️ Taxi KALUZ: Ocurrió un problema al leer la imagen del comprobante. Intenta enviarla de nuevo.'
                    );

                    return response()->json(['status' => 'MEDIA_DOWNLOAD_ERROR'], 200);
                }

                $tempFilePath = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.'voucher_'.uniqid('', true).'.jpg';
                file_put_contents($tempFilePath, $binarioImagen);

                // 3. Enviar archivo temporal a OCR.space API (idioma: español)
                $textoExtraido = $this->extraerTextoConOcrSpace($tempFilePath);

                // Eliminar inmediatamente el archivo temporal después de usar OCR.space
                if ($tempFilePath && file_exists($tempFilePath)) {
                    @unlink($tempFilePath);
                    $tempFilePath = null;
                }

                if ($textoExtraido === '') {
                    $this->enviarMensajeWhatsApp(
                        $remitente,
                        '❌ Taxi KALUZ: No pudimos leer el texto de la imagen. Asegúrate de enviar una captura nítida de tu pago de S/ 1.00 por Yape o Plin.'
                    );

                    return response()->json(['status' => 'OCR_EMPTY'], 200);
                }

                // 4. Validar las 3 condiciones del comprobante: (Yape/Plin + Monto 1.00 + Fecha de hoy)
                $validacion = $this->validarComprobanteOcr($textoExtraido);

                if ($validacion['es_valido']) {
                    // 5. Actualizar estado del conductor a habilitado en Supabase
                    $conductorActualizado = $this->habilitarConductorEnSupabase(
                        $remitente,
                        $validacion['metodo'],
                        $validacion['codigo_operacion'],
                        $textoExtraido
                    );

                    $nombreConductor = $conductorActualizado['nombre'] ?? 'Conductor';
                    $codigoOp = $validacion['codigo_operacion'] ?: 'VERIFICADO';

                    // 6. Responder al conductor por WhatsApp confirmando el desbloqueo
                    $this->enviarMensajeWhatsApp(
                        $remitente,
                        "✅ *¡CANDADO FINANCIERO DESBLOQUEADO!* 🚖\n\n".
                        "Hola *{$nombreConductor}*, validamos tu pago diario de *S/ 1.00* vía *{$validacion['metodo']}* (Op: #{$codigoOp}).\n\n".
                        'Tu cuenta ya está *HABILITADA* para recibir carreras en el Radar de Taxi KALUZ. ¡Buen turno!'
                    );

                    return response()->json([
                        'status' => 'PAYMENT_VALIDATED',
                        'conductor' => $nombreConductor,
                        'validacion' => $validacion,
                    ], 200);
                }

                // Si no cumplió alguna de las 3 reglas de validación OCR
                $motivos = implode(', ', $validacion['errores']);
                $this->enviarMensajeWhatsApp(
                    $remitente,
                    "⚠️ *Comprobante no válido - Taxi KALUZ*\n\n".
                    "No pudimos validar tu captura automáticamente ({$motivos}).\n\n".
                    "Verifica que la captura muestre claramente:\n".
                    "1️⃣ *Yape* o *Plin*\n".
                    "2️⃣ Monto exacto de *S/ 1.00*\n".
                    '3️⃣ Fecha del *día de hoy* ('.Carbon::now('America/Lima')->format('d/m/Y').')'
                );

                return response()->json([
                    'status' => 'PAYMENT_REJECTED',
                    'validacion' => $validacion,
                ], 200);
            }

            // Si envía texto u otro tipo de mensaje, responder con guía rápida
            if ($tipo === 'text') {
                $cuerpoTexto = trim((string) data_get($message, 'text.body', ''));

                $this->enviarMensajeWhatsApp(
                    $remitente,
                    "🚖 *Central Automatizada Taxi KALUZ*\n\n".
                    "• Si eres *Conductor*: envía aquí la *captura de pantalla* de tu pago diario de *S/ 1.00* (Yape o Plin) del día de hoy para desbloquear tu radar automáticamente.\n".
                    '• Mensaje recibido: "'.$cuerpoTexto.'"'
                );
            }

            return response()->json(['status' => 'EVENT_PROCESSED'], 200);
        } catch (Throwable $e) {
            Log::error('Error en WhatsAppController@handle: '.$e->getMessage(), [
                'exception' => $e,
            ]);

            // Siempre retornar 200 OK para que Meta no desactive el Webhook
            return response()->json([
                'status' => 'HANDLED_ERROR',
                'message' => 'Evento recibido con excepción controlada.',
            ], 200);
        } finally {
            if ($tempFilePath && file_exists($tempFilePath)) {
                @unlink($tempFilePath);
            }
        }
    }

    /**
     * Alias compatible con rutas previas.
     */
    public function recibirMensaje(Request $request): JsonResponse
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
            Log::warning('Meta Graph API error obteniendo media URL', [
                'media_id' => $mediaId,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return null;
        }

        return $response->json('url');
    }

    /**
     * Descarga el binario de la imagen desde la URL temporal de Meta.
     */
    protected function descargarBinarioMeta(string $mediaUrl): ?string
    {
        $token = (string) env('META_WA_BEARER_TOKEN', '');

        $response = Http::withoutVerifying()
            ->withToken($token)
            ->timeout(20)
            ->get($mediaUrl);

        if (! $response->successful()) {
            Log::warning('Error descargando archivo binario de Meta', [
                'status' => $response->status(),
            ]);

            return null;
        }

        return $response->body();
    }

    /**
     * Envía la imagen mediante Http::attach() a https://api.ocr.space/parse/image con language='spa'.
     */
    protected function extraerTextoConOcrSpace(string $filePath): string
    {
        $apiKey = (string) env('OCR_SPACE_API_KEY', 'K84914448788957');

        $response = Http::withoutVerifying()
            ->timeout(25)
            ->attach(
                'file',
                file_get_contents($filePath),
                basename($filePath)
            )
            ->post('https://api.ocr.space/parse/image', [
                'apikey' => $apiKey,
                'language' => 'spa',
                'isOverlayRequired' => 'false',
                'scale' => 'true',
                'OCREngine' => '2',
            ]);

        if (! $response->successful()) {
            Log::warning('Error HTTP en OCR.space', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return '';
        }

        $parsedText = (string) data_get($response->json(), 'ParsedResults.0.ParsedText', '');

        // Fallback a OCREngine 1 si OCREngine 2 devuelve vacío
        if (trim($parsedText) === '') {
            $retry = Http::withoutVerifying()
                ->timeout(25)
                ->attach(
                    'file',
                    file_get_contents($filePath),
                    basename($filePath)
                )
                ->post('https://api.ocr.space/parse/image', [
                    'apikey' => $apiKey,
                    'language' => 'spa',
                    'isOverlayRequired' => 'false',
                ]);

            $parsedText = (string) data_get($retry->json(), 'ParsedResults.0.ParsedText', '');
        }

        return trim($parsedText);
    }

    /**
     * Valida con expresiones regulares las 3 condiciones:
     * 1. Que exista la palabra "Yape" o "Plin".
     * 2. Que exista el monto exacto "1.00" (ej. S/ 1.00, 1.00, 1,00 o S/ 1).
     * 3. Que la fecha corresponda al día actual (America/Lima).
     */
    public function validarComprobanteOcr(string $texto): array
    {
        $errores = [];

        // 1. Validar palabra "Yape" o "Plin"
        $tieneYapeOPlin = (bool) preg_match('/\b(yape|yapeaste|plin|plineaste)\b/i', $texto, $matchMetodo);
        $metodoDetectado = 'YAPE';
        if ($tieneYapeOPlin) {
            $palabra = strtolower($matchMetodo[1]);
            $metodoDetectado = str_contains($palabra, 'plin') ? 'PLIN' : 'YAPE';
        } else {
            $errores[] = 'No se detectó Yape ni Plin';
        }

        // 2. Validar monto exacto "1.00" (acepta "1.00", "1,00", "S/ 1.00", "S/1.00", "S/ 1")
        $tieneMontoExacto = str_contains($texto, '1.00')
            || str_contains($texto, '1,00')
            || (bool) preg_match('/(?<!\d)1[\.,]00(?!\d)/', $texto)
            || (bool) preg_match('/S\/\.?\s*1(?:\b|[\.,]00)/i', $texto);

        if (! $tieneMontoExacto) {
            $errores[] = 'No se encontró el monto S/ 1.00';
        }

        // 3. Validar que la fecha corresponda al día actual (Zona horaria Perú: America/Lima)
        $hoy = Carbon::now('America/Lima');
        $diaSinCero = (string) $hoy->day;          // ej. "18" o "5"
        $diaConCero = $hoy->format('d');           // ej. "18" o "05"
        $mesConCero = $hoy->format('m');           // ej. "10"
        $mesSinCero = (string) $hoy->month;        // ej. "10"
        $anio4 = $hoy->format('Y');                // ej. "2026"
        $anio2 = $hoy->format('y');                // ej. "26"

        $mesesEs = [
            1 => 'ene(?:ro)?',
            2 => 'feb(?:rero)?',
            3 => 'mar(?:zo)?',
            4 => 'abr(?:il)?',
            5 => 'may(?:o)?',
            6 => 'jun(?:io)?',
            7 => 'jul(?:io)?',
            8 => 'ago(?:sto)?',
            9 => 'sep(?:tiembre)?|set(?:iembre)?',
            10 => 'oct(?:ubre)?',
            11 => 'nov(?:iembre)?',
            12 => 'dic(?:iembre)?',
        ];
        $patronMes = $mesesEs[$hoy->month];

        // Patrones de fecha que emiten Yape ("18 oct. 2026", "18 de octubre de 2026") y Plin ("18/10/2026", "18-10-2026", "2026-10-18")
        $patronesFechaHoy = [
            // Ej: "18 oct. 2026", "18 oct 2026", "18 de octubre 2026", "18 oct."
            '/\b0?'.$diaSinCero.'\s*(?:de\s+)?(?:'.$patronMes.')\.?(?:\s*(?:de\s+|del\s+)?'.$anio4.')?\b/i',
            // Ej: "18/10/2026", "18-10-2026", "18.10.2026", "18/10/26"
            '/\b(?:'.$diaConCero.'|'.$diaSinCero.')[\/\-\.](?:'.$mesConCero.'|'.$mesSinCero.')[\/\-\.](?:'.$anio4.'|'.$anio2.')\b/',
            // Ej: "2026-10-18" o "2026/10/18"
            '/\b'.$anio4.'[\/\-](?:'.$mesConCero.'|'.$mesSinCero.')[\/\-](?:'.$diaConCero.'|'.$diaSinCero.')\b/',
        ];

        $tieneFechaHoy = false;
        foreach ($patronesFechaHoy as $patron) {
            if (preg_match($patron, $texto)) {
                $tieneFechaHoy = true;
                break;
            }
        }

        if (! $tieneFechaHoy) {
            $errores[] = 'La fecha no coincide con hoy ('.$hoy->format('d/m/Y').')';
        }

        // Extraer código de operación / seguridad para trazabilidad en el panel web
        $codigoOperacion = null;
        if (preg_match('/(?:operaci[oó]n|seguridad|c[oó]digo|nro\.?)[\s:\.\-#]*([0-9]{3,10})/i', $texto, $matchCodigo)) {
            $codigoOperacion = $matchCodigo[1];
        } elseif (preg_match('/\b([0-9]{6,8})\b/', $texto, $matchNumero)) {
            $codigoOperacion = $matchNumero[1];
        }

        return [
            'es_valido' => $tieneYapeOPlin && $tieneMontoExacto && $tieneFechaHoy,
            'tiene_yape_plin' => $tieneYapeOPlin,
            'tiene_monto_exacto' => $tieneMontoExacto,
            'tiene_fecha_hoy' => $tieneFechaHoy,
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
        $supabaseUrl = rtrim((string) env('SUPABASE_URL'), '/');
        $supabaseKey = (string) env('SUPABASE_KEY');

        // Normalizar número: Meta envía "51964111222", en BD puede estar como "964111222" o "51964111222"
        $soloDigitos = preg_replace('/\D+/', '', $telefonoRemitente);
        $ultimos9 = strlen($soloDigitos) >= 9 ? substr($soloDigitos, -9) : $soloDigitos;

        $headers = [
            'apikey' => $supabaseKey,
            'Authorization' => 'Bearer '.$supabaseKey,
            'Content-Type' => 'application/json',
            'Prefer' => 'return=representation',
        ];

        // 1. Buscar al conductor por telefono_whatsapp (9 dígitos o número completo con código de país)
        $consulta = Http::withoutVerifying()
            ->withHeaders($headers)
            ->timeout(10)
            ->get("{$supabaseUrl}/rest/v1/conductores", [
                'or' => "(telefono_whatsapp.eq.{$soloDigitos},telefono_whatsapp.eq.{$ultimos9},telefono_whatsapp.ilike.*{$ultimos9}*)",
                'select' => '*',
                'limit' => 1,
            ]);

        $conductor = data_get($consulta->json(), '0');

        $ahoraLima = Carbon::now('America/Lima');
        $vencimientoComision = $ahoraLima->copy()->addDay()->toIso8601String();

        // Intentar actualizar incluyendo 'estado' => 'habilitado' + campos reales del esquema Supabase
        $filtroQuery = $conductor && isset($conductor['id'])
            ? 'id=eq.'.$conductor['id']
            : "or=(telefono_whatsapp.eq.{$soloDigitos},telefono_whatsapp.eq.{$ultimos9})";

        $updateResponse = Http::withoutVerifying()
            ->withHeaders($headers)
            ->timeout(10)
            ->patch("{$supabaseUrl}/rest/v1/conductores?{$filtroQuery}", [
                'estado' => 'habilitado',
                'habilitado' => true,
                'estado_comision' => 'al_dia',
                'fecha_vencimiento_comision' => $vencimientoComision,
                'updated_at' => $ahoraLima->toIso8601String(),
            ]);

        // Si la tabla remota de Supabase no tiene la columna 'estado' y usa 'habilitado' booleano + 'estado_comision'
        if (! $updateResponse->successful()) {
            $updateResponse = Http::withoutVerifying()
                ->withHeaders($headers)
                ->timeout(10)
                ->patch("{$supabaseUrl}/rest/v1/conductores?{$filtroQuery}", [
                    'habilitado' => true,
                    'estado_comision' => 'al_dia',
                    'fecha_vencimiento_comision' => $vencimientoComision,
                    'updated_at' => $ahoraLima->toIso8601String(),
                ]);
        }

        $conductorActualizado = data_get($updateResponse->json(), '0') ?? $conductor;

        // Registrar trazabilidad del pago en pagos_diarios (si existe el conductor)
        if ($conductorActualizado && isset($conductorActualizado['id'])) {
            try {
                Http::withoutVerifying()
                    ->withHeaders($headers)
                    ->timeout(8)
                    ->post("{$supabaseUrl}/rest/v1/pagos_diarios", [
                        'conductor_id' => $conductorActualizado['id'],
                        'monto' => 1.00,
                        'fecha_correspondiente' => $ahoraLima->toDateString(),
                        'codigo_operacion' => $codigoOperacion ?? 'OCR-'.strtoupper(substr(md5($textoOcr), 0, 6)),
                        'estado_verificacion' => 'verificado',
                        'metodo_pago' => strtolower($metodoPago),
                    ]);
            } catch (Throwable $e) {
                Log::info('Trazabilidad pagos_diarios omitida: '.$e->getMessage());
            }
        }

        return is_array($conductorActualizado) ? $conductorActualizado : null;
    }

    /**
     * Envía un mensaje de texto de WhatsApp al chofer usando el endpoint /messages de Meta.
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

        if (! $response->successful()) {
            Log::warning('Error enviando mensaje WhatsApp via Meta Cloud API', [
                'to' => $destinatario,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return false;
        }

        return true;
    }
}
