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
        string $placaChofer = 'W3X-451',
        int $tiempoLlegadaMin = 5,
        int $ofertaId = 0
    ): bool {
        $bearerToken = (string) env('META_WA_BEARER_TOKEN');
        $phoneId = (string) env('META_WA_PHONE_NUMBER_ID');
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

/*
|--------------------------------------------------------------------------
| Rutas Modulares por Desarrollador (Cero Conflictos Git)
|--------------------------------------------------------------------------
*/
require __DIR__.'/despacho.php';  // 👨‍💻 Rutas Dev 1 (Despacho y PWA Conductor)
require __DIR__.'/finanzas.php';  // 👨‍💻 Rutas Dev 2 (Finanzas, Padrón y Candado S/ 1.00)
require __DIR__.'/turismo.php';   // 👨‍💻 Rutas Dev 3 (Turismo, Combis y Rastreo GPS)
require __DIR__.'/bot.php';       // 👨‍💻 Rutas Dev 4 (Bot WhatsApp y Tablero KPI)
