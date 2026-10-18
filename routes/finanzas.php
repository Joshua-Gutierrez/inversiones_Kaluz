<?php

use App\Http\Controllers\Finanzas\PadronController;
use App\Http\Controllers\Finanzas\ValidacionYapeController;
use App\Http\Middleware\EnsureFinanzasAdmin;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| 👨‍💻 ZONA DEV 2: Rutas de Finanzas (Candado S/ 1.00, Padrón y Validación Yape)
|--------------------------------------------------------------------------
*/

Route::prefix('finanzas')->name('finanzas.')
    ->middleware(EnsureFinanzasAdmin::class)
    ->group(function () {
        Route::get('/padron', [PadronController::class, 'index'])->name('padron');
        Route::post('/padron/conductores', [PadronController::class, 'store'])->name('padron.conductores.store');
        Route::patch('/padron/conductores/{conductor}', [PadronController::class, 'actualizar'])
            ->name('padron.conductores.actualizar');
        Route::post('/padron/conductores/{conductor}/mora', [PadronController::class, 'registrarMora'])
            ->name('padron.conductores.mora');
        Route::post('/padron/conductores/{conductor}/alerta-cuota', [PadronController::class, 'registrarAlertaCuota'])
            ->name('padron.conductores.alerta-cuota');
        Route::post('/yape/{pagoComision}/validar', [ValidacionYapeController::class, 'validarComprobante'])
            ->name('yape.validar');
        Route::post('/yape/{pagoComision}/rechazar', [ValidacionYapeController::class, 'rechazarComprobante'])
            ->name('yape.rechazar');
        Route::get('/padron/exportar', [PadronController::class, 'exportar'])->name('padron.exportar');
    });
