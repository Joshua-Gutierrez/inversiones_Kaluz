<?php

namespace App\Http\Controllers\Bot;

use App\Http\Controllers\Controller;
use App\Services\Bot\AnalizadorWhatsAppService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * 👨‍💻 ZONA DEV 4 - Controlador del Webhook Oficial de Meta WhatsApp Cloud API
 */
class WebhookWhatsAppController extends Controller
{
    public function __construct(
        protected AnalizadorWhatsAppService $analizadorWhatsAppService
    ) {}

    /**
     * GET /api/webhook
     * Verificación inicial del Webhook requerida por Meta for Developers.
     * Meta envía: hub.mode=subscribe & hub.verify_token=kaluz2026 & hub.challenge=XXXX
     */
    public function verificarWebhook(Request $request): Response|JsonResponse
    {
        $mode = $request->query('hub_mode') ?? $request->query('hub.mode');
        $token = $request->query('hub_verify_token') ?? $request->query('hub.verify_token');
        $challenge = $request->query('hub_challenge') ?? $request->query('hub.challenge');

        $verifyTokenEsperado = env('WHATSAPP_VERIFY_TOKEN', 'kaluz2026');

        if ($mode === 'subscribe' && $token === $verifyTokenEsperado && $challenge !== null) {
            return response((string) $challenge, 200)
                ->header('Content-Type', 'text/plain');
        }

        return response()->json([
            'exito' => false,
            'mensaje' => 'Token de verificación inválido o parámetros incompletos.',
        ], 403);
    }

    /**
     * POST /api/webhook
     * Recibe tanto eventos reales de Meta WhatsApp Cloud API como del simulador interno.
     */
    public function recibirMensaje(Request $request): JsonResponse
    {
        // Soporte para payload oficial de Meta WhatsApp Cloud API
        $entryMessage = data_get($request->all(), 'entry.0.changes.0.value.messages.0');

        if (is_array($entryMessage)) {
            $telefono = (string) ($entryMessage['from'] ?? '');
            $mensaje = (string) (data_get($entryMessage, 'text.body') ?? '');
            $tipo = (string) ($entryMessage['type'] ?? 'text');
        } else {
            // Soporte para simulador local
            $mensaje = (string) $request->input('mensaje', '');
            $telefono = (string) $request->input('telefono', '');
            $tipo = 'text';
        }

        if ($mensaje === '' && $telefono === '') {
            // Confirmación 200 OK para eventos de estado de Meta (delivered, read, etc.)
            return response()->json(['status' => 'EVENT_RECEIVED'], 200);
        }

        $analisis = $this->analizadorWhatsAppService->analizarMensaje($mensaje, $telefono);

        return response()->json([
            'exito' => true,
            'tipo_mensaje' => $tipo,
            'datos_extraidos' => $analisis,
        ], 200);
    }
}
