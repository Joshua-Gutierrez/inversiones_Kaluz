<?php

namespace App\Http\Controllers\Bot;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

/**
 * 👨‍💻 ZONA DEV 4 - Controlador del Tablero de KPIs y Home de la Torre de Control
 */
class TableroKpiController extends Controller
{
    public function index(): View
    {
        return view('bot.tablero-kpi');
    }
}
