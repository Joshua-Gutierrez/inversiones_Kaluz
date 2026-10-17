<?php

namespace App\Services\Bot;

/**
 * 👨‍💻 ZONA DEV 4 - Servicio Analizador de Mensajes de WhatsApp (NLP / Reglas para solicitud de taxi)
 */
class AnalizadorWhatsAppService
{
    /**
     * Extrae intención, origen, destino y oferta sugerida desde un texto de WhatsApp.
     */
    public function analizarMensaje(string $mensaje, string $telefonoCliente = ''): array
    {
        $textoLimpio = trim($mensaje);

        // Detección simple del patrón "de X a Y"
        $origen = 'Por confirmar';
        $destino = 'Por confirmar';
        $precioSugerido = 7.00;

        if (preg_match('/de\s+(.+?)\s+a\s+(.+)/i', $textoLimpio, $coincidencias)) {
            $origen = trim($coincidencias[1]);
            $destino = trim($coincidencias[2]);
        }

        return [
            'intencion' => 'solicitar_viaje',
            'telefono' => $telefonoCliente,
            'origen_detectado' => $origen,
            'destino_detectado' => $destino,
            'precio_sugerido' => $precioSugerido,
            'mensaje_original' => $textoLimpio,
        ];
    }
}
