<?php

namespace App\Services\Finanzas;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class ComprobanteStorageService
{
    public function subir(UploadedFile $archivo, int $conductorId, Carbon $fecha): string
    {
        $ruta = sprintf(
            'conductores/%d/%s/%s.%s',
            $conductorId,
            $fecha->format('Y/m'),
            Str::uuid(),
            $archivo->extension()
        );
        $contenido = $archivo->get();
        $contentType = $archivo->getMimeType();

        try {
            $respuesta = Http::withHeaders($this->headers($contentType))
                ->timeout(20)
                ->withBody($contenido, $contentType)
                ->post($this->urlObjeto($ruta));
        } catch (ConnectionException $exception) {
            throw new ComprobanteStorageException(
                'No se pudo conectar con el almacenamiento de comprobantes. Intenta nuevamente.',
                previous: $exception
            );
        }

        if (! $respuesta->successful()) {
            throw new ComprobanteStorageException(
                'Supabase Storage no aceptó el comprobante (HTTP '.$respuesta->status().').'
            );
        }

        return $ruta;
    }

    public function eliminar(string $ruta): void
    {
        $this->validarRuta($ruta);

        try {
            $respuesta = Http::withHeaders($this->headers())
                ->timeout(20)
                ->delete($this->urlObjeto($ruta));
        } catch (ConnectionException $exception) {
            throw new ComprobanteStorageException(
                'No se pudo limpiar el comprobante después de un error de registro.',
                previous: $exception
            );
        }

        if (! $respuesta->successful()) {
            throw new ComprobanteStorageException(
                'No se pudo limpiar el comprobante después de un error de registro (HTTP '.$respuesta->status().').'
            );
        }
    }

    public function urlFirmada(string $ruta): string
    {
        $this->validarRuta($ruta);

        try {
            $respuesta = Http::withHeaders($this->headers())
                ->timeout(15)
                ->post($this->urlObjeto($ruta, 'sign'), ['expiresIn' => 300]);
        } catch (ConnectionException $exception) {
            throw new ComprobanteStorageException(
                'No se pudo conectar con el almacenamiento para abrir el comprobante.',
                previous: $exception
            );
        }

        if (! $respuesta->successful() || ! is_string($respuesta->json('signedURL'))) {
            throw new ComprobanteStorageException(
                'No se pudo generar un enlace temporal para el comprobante (HTTP '.$respuesta->status().').'
            );
        }

        $urlFirmada = $respuesta->json('signedURL');

        if (filter_var($urlFirmada, FILTER_VALIDATE_URL)) {
            return $urlFirmada;
        }

        if (str_starts_with($urlFirmada, '/storage/v1/')) {
            return rtrim($this->supabaseUrl(), '/').$urlFirmada;
        }

        $base = rtrim($this->supabaseUrl(), '/').'/storage/v1';

        return $base.'/'.ltrim($urlFirmada, '/');
    }

    private function headers(?string $contentType = null): array
    {
        $clave = config('services.supabase.service_role_key');

        if (! is_string($clave) || $clave === '') {
            throw new ComprobanteStorageException(
                'Falta configurar SUPABASE_SERVICE_ROLE_KEY para almacenar comprobantes privados.'
            );
        }

        $headers = [
            'apikey' => $clave,
            'Authorization' => 'Bearer '.$clave,
        ];

        if ($contentType !== null) {
            $headers['Content-Type'] = $contentType;
            $headers['x-upsert'] = 'false';
        }

        return $headers;
    }

    private function urlObjeto(string $ruta, ?string $operacion = null): string
    {
        $bucket = config('services.supabase.storage_bucket');

        if (! is_string($bucket) || ! preg_match('/^[a-z0-9][a-z0-9._-]*$/', $bucket)) {
            throw new ComprobanteStorageException('El nombre del bucket de comprobantes no es válido.');
        }

        $segmentos = ['storage', 'v1', 'object'];
        if ($operacion !== null) {
            $segmentos[] = $operacion;
        }
        $segmentos[] = $bucket;
        $segmentos = array_merge($segmentos, explode('/', $ruta));

        return rtrim($this->supabaseUrl(), '/').'/'.implode('/', array_map('rawurlencode', $segmentos));
    }

    private function supabaseUrl(): string
    {
        $url = config('services.supabase.url');

        if (! is_string($url) || ! filter_var($url, FILTER_VALIDATE_URL)) {
            throw new ComprobanteStorageException('Falta configurar una SUPABASE_URL válida.');
        }

        return $url;
    }

    private function validarRuta(string $ruta): void
    {
        if (
            $ruta === ''
            || str_starts_with($ruta, '/')
            || str_contains($ruta, '..')
            || filter_var($ruta, FILTER_VALIDATE_URL)
        ) {
            throw new ComprobanteStorageException('La ruta del comprobante no es válida.');
        }
    }
}
