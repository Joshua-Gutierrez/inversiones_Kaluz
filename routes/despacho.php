<?php

use App\Http\Controllers\Despacho\ConductorPwaController;
use App\Http\Controllers\Despacho\SubastaAtomicaController;
use App\Http\Middleware\CandadoFinancieroMiddleware;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| 👨‍💻 ZONA DEV 1: Rutas de Despacho (Subasta Atómica y PWA Conductor)
|--------------------------------------------------------------------------
*/

Route::prefix('despacho')->name('despacho.')->group(function () {
    Route::get('/pwa', [ConductorPwaController::class, 'index'])
        ->middleware(CandadoFinancieroMiddleware::class)
        ->name('pwa');

    Route::get('/pwa/bloqueado', [ConductorPwaController::class, 'bloqueado'])
        ->name('pwa.bloqueado');

    Route::post('/subasta/{solicitudId}/ofertar', [SubastaAtomicaController::class, 'ofertar'])
        ->middleware(CandadoFinancieroMiddleware::class)
        ->name('subasta.ofertar');
});
