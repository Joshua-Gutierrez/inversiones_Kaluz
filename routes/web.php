<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('login');
});

Route::post('/login', function (Request $request) {
    $usuario = $request->input('usuario');
    $password = $request->input('password');

    $supabaseUrl = env('SUPABASE_URL');
    $supabaseKey = env('SUPABASE_KEY');

    $response = Http::withoutVerifying()->withHeaders([
        'apikey' => $supabaseKey,
        'Authorization' => 'Bearer ' . $supabaseKey,
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
    return view('dashboard');
});