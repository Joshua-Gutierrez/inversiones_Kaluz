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
