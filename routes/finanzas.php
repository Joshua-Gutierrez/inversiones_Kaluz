<?php

use App\Http\Controllers\Finanzas\PadronController;
use App\Http\Controllers\Finanzas\ValidacionYapeController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| 👨‍💻 ZONA DEV 2: Rutas de Finanzas (Candado S/ 1.00, Padrón y Validación Yape)
|--------------------------------------------------------------------------
*/

Route::prefix('finanzas')->name('finanzas.')->group(function () {
    Route::get('/padron', [PadronController::class, 'index'])->name('padron');
    Route::post('/yape/validar', [ValidacionYapeController::class, 'validarComprobante'])->name('yape.validar');
});
