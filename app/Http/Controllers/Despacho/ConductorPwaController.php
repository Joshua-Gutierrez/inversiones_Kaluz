<?php

namespace App\Http\Controllers\Despacho;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * 👨‍💻 ZONA DEV 1 - Controlador de la PWA del Conductor (Radar de viajes y estados)
 */
class ConductorPwaController extends Controller
{
    public function index(Request $request): View
    {
        return view('despacho.radar');
    }

    public function bloqueado(): View
    {
        return view('despacho.bloqueado');
    }
}
