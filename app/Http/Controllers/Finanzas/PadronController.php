<?php

namespace App\Http\Controllers\Finanzas;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

/**
 * 👨‍💻 ZONA DEV 2 - Controlador del Padrón de Socios Conductores (Semáforo de 300 socios)
 */
class PadronController extends Controller
{
    public function index(): View
    {
        return view('finanzas.padron');
    }
}
