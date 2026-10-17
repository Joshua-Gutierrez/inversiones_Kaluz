<?php

namespace App\Http\Controllers\Turismo;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

/**
 * 👨‍💻 ZONA DEV 3 - Controlador de Rastreo GPS en Tiempo Real (/rastreo/{id} y /track/{id})
 */
class RastreoGpsController extends Controller
{
    public function mostrarMapa(string $id): View
    {
        return view('turismo.rastreo', ['viajeId' => $id]);
    }
}
