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
    Route::get('/crear', [PaqueteTuristicoController::class, 'create'])->name('create');
    Route::post('/', [PaqueteTuristicoController::class, 'store'])->name('store');
    Route::get('/{id}/editar', [PaqueteTuristicoController::class, 'edit'])->whereNumber('id')->name('edit');
    Route::put('/{id}', [PaqueteTuristicoController::class, 'update'])->whereNumber('id')->name('update');
});

// Rutas públicas de rastreo GPS (/rastreo/{id} y alias /track/{id})
Route::get('/rastreo/{id}', [RastreoGpsController::class, 'mostrarMapa'])->name('turismo.rastreo');
Route::get('/track/{id}', [RastreoGpsController::class, 'mostrarMapa'])->whereNumber('id')->name('turismo.track');
Route::get('/api/track/{id}/posicion', [RastreoGpsController::class, 'posicion'])->whereNumber('id')->name('turismo.posicion');
Route::post('/api/track/{id}/simular', [RastreoGpsController::class, 'simular'])->whereNumber('id')->name('turismo.simular');
