<?php

namespace App\Http\Controllers\Bot;

use App\Http\Controllers\Controller;
use App\Services\Bot\AnalizadorWhatsAppService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * 👨‍💻 ZONA DEV 4 - Controlador del Webhook de WhatsApp
 */
class WebhookWhatsAppController extends Controller
{
    public function __construct(
        protected AnalizadorWhatsAppService $analizadorWhatsAppService
    ) {}

    public function recibirMensaje(Request $request): JsonResponse
    {
        $mensaje = (string) $request->input('mensaje', '');
        $telefono = (string) $request->input('telefono', '');

        $analisis = $this->analizadorWhatsAppService->analizarMensaje($mensaje, $telefono);

        return response()->json([
            'exito' => true,
            'datos_extraidos' => $analisis,
        ]);
    }
}
