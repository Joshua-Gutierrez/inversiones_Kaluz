<?php

use App\Http\Controllers\Turismo\PaqueteTuristicoController;
use App\Http\Controllers\Turismo\RastreoGpsController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| 👨‍💻 ZONA DEV 3: Rutas de Turismo (Combis, Paquetes Turísticos y Rastreo GPS)
|--------------------------------------------------------------------------
*/

Route::prefix('turismo')->name('turismo.')->group(function () {
    Route::get('/', [PaqueteTuristicoController::class, 'index'])->name('index');
});

// Rutas públicas de rastreo GPS (/rastreo/{id} y alias /track/{id})
Route::get('/rastreo/{id}', [RastreoGpsController::class, 'mostrarMapa'])->name('turismo.rastreo');
Route::get('/track/{id}', [RastreoGpsController::class, 'mostrarMapa'])->name('turismo.track');
