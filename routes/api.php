<?php

use App\Http\Controllers\WhatsAppController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes - Webhook Oficial Meta WhatsApp Cloud API & OCR.space
|--------------------------------------------------------------------------
| Estas rutas tienen el prefijo automático "/api" (GET/POST /api/webhook).
*/

Route::get('/webhook', [WhatsAppController::class, 'verify'])->name('api.webhook.verify');
Route::post('/webhook', [WhatsAppController::class, 'handle'])->name('api.webhook.handle');
