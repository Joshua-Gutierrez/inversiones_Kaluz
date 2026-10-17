<?php

namespace App\Http\Controllers\Bot;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

/**
 * 👨‍💻 ZONA DEV 4 - Controlador del Simulador Interactivo de Chat WhatsApp
 */
class SimuladorChatController extends Controller
{
    public function index(): View
    {
        return view('bot.simulador');
    }
}
