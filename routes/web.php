<?php

use App\Services\Supabase\Supabase;
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
