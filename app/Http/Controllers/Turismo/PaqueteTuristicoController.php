<?php

namespace App\Http\Controllers\Turismo;

use App\Http\Controllers\Controller;
use App\Services\Turismo\PrioridadFlotaPropiaService;
use Illuminate\View\View;

/**
 * 👨‍💻 ZONA DEV 3 - Controlador de Paquetes Turísticos y Prioridad de Combis/Flota Propia
 */
class PaqueteTuristicoController extends Controller
{
    public function __construct(
        protected PrioridadFlotaPropiaService $prioridadFlotaPropiaService
    ) {}

    public function index(): View
    {
        return view('turismo.index');
    }
}
