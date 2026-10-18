<?php

use App\Http\Controllers\Finanzas\PadronController;
use App\Http\Controllers\Finanzas\ValidacionYapeController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| ZONA DEV 2: Rutas de Finanzas (Candado S/ 1.00, Padrón, Documentos y Validación Yape)
|--------------------------------------------------------------------------
*/

Route::prefix('finanzas')->name('finanzas.')->group(function () {
    Route::get('/padron', [PadronController::class, 'index'])->name('padron');
    Route::post('/conductor/crear', [PadronController::class, 'store'])->name('conductor.store');
    Route::post('/conductor/{id}/candado', [PadronController::class, 'cambiarCandado'])->whereNumber('id')->name('conductor.candado');
    Route::post('/conductor/{id}/renovar-documento', [PadronController::class, 'renovarDocumento'])->whereNumber('id')->name('conductor.renovar');
    Route::post('/conductor/{id}/cobro-whatsapp', [PadronController::class, 'enviarCobroWhatsApp'])->whereNumber('id')->name('conductor.cobro-wa');

    Route::get('/yape', [ValidacionYapeController::class, 'index'])->name('yape');
    Route::post('/yape/validar', [ValidacionYapeController::class, 'validarComprobante'])->name('yape.validar');
});
