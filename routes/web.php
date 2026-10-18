<?php

use App\Models\Conductor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;

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

    $supabaseUrl = config('services.supabase.url');
    $supabaseKey = config('services.supabase.key');

    $response = Http::withHeaders([
        'apikey' => $supabaseKey,
        'Authorization' => 'Bearer '.$supabaseKey,
    ])->get("{$supabaseUrl}/rest/v1/usuarios", [
        'usuario' => "eq.{$usuario}",
        'password' => "eq.{$password}",
        'select' => '*',
    ]);

    $usuarios = $response->json();

    if ($response->successful() && is_array($usuarios) && isset($usuarios[0])) {
        $user = $usuarios[0];
        $usuarioAutenticado = (string) $user['usuario'];
        $datosSesion = [
            'usuario' => $usuarioAutenticado,
            'nombre' => $user['nombre'] ?? $usuarioAutenticado,
        ];

        $request->session()->forget('conductor_id');
        if (preg_match('/^[0-9]{8}$/D', $usuarioAutenticado)) {
            $conductorId = Conductor::query()->where('dni', $usuarioAutenticado)->value('id');
            if ($conductorId !== null) {
                $datosSesion['conductor_id'] = (int) $conductorId;
            }
        }

        $request->session()->regenerate();
        $request->session()->put($datosSesion);

        return isset($datosSesion['conductor_id'])
            ? redirect()->route('despacho.pwa')
            : redirect()->route('dashboard');
    }

    return back()->withErrors(['error' => 'Usuario o contraseña incorrectos en Supabase.'])->withInput();
});

Route::get('/dashboard', function () {
    if (! session('usuario')) {
        return redirect()->route('login');
    }

    if (session('conductor_id')) {
        return redirect()->route('despacho.pwa');
    }

    $usuario = mb_strtolower(trim((string) session('usuario')));
    abort_unless(
        in_array($usuario, config('finanzas.admin_users', []), true),
        403,
        'No tienes permisos para acceder al panel administrativo.'
    );

    return view('dashboard');
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
