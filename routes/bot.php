<?php

use App\Http\Controllers\Bot\SimuladorChatController;
use App\Http\Controllers\Bot\TableroKpiController;
use App\Http\Controllers\Bot\WebhookWhatsAppController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| 👨‍💻 ZONA DEV 4: Rutas de Bot WhatsApp y Torre de Control (KPIs)
|--------------------------------------------------------------------------
*/

Route::prefix('bot')->name('bot.')->group(function () {
    Route::get('/tablero', [TableroKpiController::class, 'index'])->name('tablero');
    Route::get('/simulador', [SimuladorChatController::class, 'index'])->name('simulador');
    Route::get('/webhook/whatsapp', [WebhookWhatsAppController::class, 'verificarWebhook'])->name('webhook.whatsapp.verify');
    Route::post('/webhook/whatsapp', [WebhookWhatsAppController::class, 'recibirMensaje'])->name('webhook.whatsapp');
});
