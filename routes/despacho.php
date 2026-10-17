<?php

use App\Http\Controllers\Despacho\ConductorPwaController;
use App\Http\Controllers\Despacho\SubastaAtomicaController;
use App\Http\Middleware\CandadoFinancieroMiddleware;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| 👨‍💻 ZONA DEV 1: Rutas de Despacho (Subasta Atómica y PWA Conductor)
|--------------------------------------------------------------------------
| Prefijo /conductor. El candado financiero (Dev 2) protege el radar y la API;
| el servicio además valida `habilitado` (doble seguro, Regla #2 CONTEXT.md).
*/

Route::prefix('conductor')->name('despacho.')->group(function () {

    // --- Entrada / sesión del conductor (demo, sin auth compleja) ---
    Route::get('/', [ConductorPwaController::class, 'seleccion'])->name('seleccion');
    Route::post('/entrar', [ConductorPwaController::class, 'entrar'])->name('entrar');
    Route::post('/salir', [ConductorPwaController::class, 'salir'])->name('salir');

    // --- Pantalla de bloqueo (candado S/ 1.00 o permiso vencido) ---
    Route::get('/bloqueado', [ConductorPwaController::class, 'bloqueado'])->name('pwa.bloqueado');

    // --- Demo: crear solicitud de ejemplo sin depender del bot (Dev 4) ---
    Route::get('/demo/crear-solicitud', [ConductorPwaController::class, 'demoCrearSolicitud'])
        ->name('demo.crear-solicitud');
    Route::get('/demo/sembrar-conductores', [ConductorPwaController::class, 'demoSembrarConductores'])
        ->name('demo.sembrar-conductores');

    // --- Zona protegida por el candado financiero de Dev 2 ---
    Route::middleware(CandadoFinancieroMiddleware::class)->group(function () {

        // Pantallas
        Route::get('/radar', [ConductorPwaController::class, 'radar'])->name('pwa');
        Route::get('/viaje', [ConductorPwaController::class, 'viaje'])->name('viaje');

        // API JSON (polling cada 3 s + acciones del conductor)
        Route::get('/api/solicitudes', [SubastaAtomicaController::class, 'solicitudes'])
            ->name('api.solicitudes');
        Route::post('/api/solicitudes/{id}/aceptar', [SubastaAtomicaController::class, 'aceptar'])
            ->name('api.solicitudes.aceptar');
        Route::post('/api/solicitudes/{id}/contraoferta', [SubastaAtomicaController::class, 'contraofertar'])
            ->name('api.solicitudes.contraoferta');
        Route::get('/api/viaje-activo', [SubastaAtomicaController::class, 'viajeActivo'])
            ->name('api.viaje-activo');
        Route::post('/api/viaje/{id}/estado', [SubastaAtomicaController::class, 'cambiarEstadoViaje'])
            ->name('api.viaje.estado');
        Route::post('/api/ubicacion', [SubastaAtomicaController::class, 'ubicacion'])
            ->name('api.ubicacion');
    });

    // --- Acción del PASAJERO vía Bot (Dev 4): aceptar una contraoferta ---
    // Va fuera del candado: no es una acción del conductor, es el cliente eligiendo.
    Route::post('/api/ofertas/{id}/aceptar', [SubastaAtomicaController::class, 'aceptarContraoferta'])
        ->name('api.ofertas.aceptar');
});

// --- Compatibilidad con la ruta vieja /despacho/pwa (links y bookmarks) ---
Route::redirect('/despacho/pwa', '/conductor/radar')->name('despacho.pwa.legacy');
Route::redirect('/despacho/pwa/bloqueado', '/conductor/bloqueado');
