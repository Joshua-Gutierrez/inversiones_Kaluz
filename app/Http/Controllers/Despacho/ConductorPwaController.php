<?php

namespace App\Http\Controllers\Despacho;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * 👨‍💻 ZONA DEV 1 - Controlador de la PWA del Conductor
 *
 * Pantallas: selección de conductor (demo), radar de viajes, viaje activo y bloqueado.
 * La lógica de negocio vive en Services\Despacho\AtomicAssignmentService.
 */
class ConductorPwaController extends Controller
{
    /**
     * GET /conductor - Pantalla de entrada / selección de conductor (demo, sin auth compleja).
     */
    public function seleccion(Request $request): View
    {
        $conductores = DB::table('conductores')
            ->select('id', 'nombre', 'placa', 'modelo_vehiculo', 'tipo_flota', 'estado_comision', 'habilitado')
            ->orderBy('nombre')
            ->get();

        return view('despacho.seleccion', [
            'conductores' => $conductores,
            'conductorId' => $request->session()->get('conductor_id'),
        ]);
    }

    /**
     * POST /conductor/entrar - Guarda el conductor en sesión y lo manda al radar.
     * Acepta conductor_id (lista) o telefono_whatsapp (ingreso manual).
     */
    public function entrar(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'conductor_id'        => ['nullable', 'integer', 'exists:conductores,id'],
            'telefono_whatsapp'   => ['nullable', 'string', 'max:20'],
        ], [], [
            'conductor_id' => 'conductor',
            'telefono_whatsapp' => 'teléfono',
        ]);

        $query = DB::table('conductores');

        if (! empty($datos['conductor_id'])) {
            $conductor = $query->where('id', $datos['conductor_id'])->first();
        } elseif (! empty($datos['telefono_whatsapp'])) {
            $conductor = $query->where('telefono_whatsapp', trim($datos['telefono_whatsapp']))->first();
        } else {
            $conductor = null;
        }

        if (! $conductor) {
            return back()->withErrors(['telefono_whatsapp' => 'Conductor no encontrado. Verifica tu número o elige de la lista.'])->withInput();
        }

        $request->session()->put('conductor_id', (int) $conductor->id);

        if (! $conductor->habilitado) {
            return redirect()->route('despacho.pwa.bloqueado');
        }

        return redirect()->route('despacho.pwa');
    }

    /**
     * GET /conductor/radar - Pantalla principal (bandeja en vivo de solicitudes).
     */
    public function radar(Request $request): View|RedirectResponse
    {
        $conductorId = $request->session()->get('conductor_id');

        if (! $conductorId) {
            return redirect()->route('despacho.seleccion');
        }

        $conductor = DB::table('conductores')
            ->where('id', $conductorId)
            ->select('id', 'nombre', 'placa', 'modelo_vehiculo', 'telefono_whatsapp', 'estado_comision', 'habilitado', 'lat_actual', 'lng_actual')
            ->first();

        if (! $conductor) {
            $request->session()->forget('conductor_id');
            return redirect()->route('despacho.seleccion');
        }

        if (! $conductor->habilitado) {
            return redirect()->route('despacho.pwa.bloqueado');
        }

        $viajeActivo = app(\App\Services\Despacho\AtomicAssignmentService::class)
            ->viajeActivoDe((int) $conductor->id);

        return view('despacho.radar', [
            'conductor'    => $conductor,
            'viajeActivo'  => $viajeActivo,
        ]);
    }

    /**
     * GET /conductor/viaje - Pantalla del viaje activo (cliente, ruta y cambio de estado).
     */
    public function viaje(Request $request): View|RedirectResponse
    {
        $conductorId = $request->session()->get('conductor_id');

        if (! $conductorId) {
            return redirect()->route('despacho.seleccion');
        }

        $conductor = DB::table('conductores')
            ->where('id', $conductorId)
            ->select('id', 'nombre', 'placa', 'modelo_vehiculo', 'habilitado')
            ->first();

        if (! $conductor || ! $conductor->habilitado) {
            return redirect()->route('despacho.pwa.bloqueado');
        }

        $viajeActivo = app(\App\Services\Despacho\AtomicAssignmentService::class)
            ->viajeActivoDe((int) $conductor->id);

        if (! $viajeActivo) {
            return redirect()->route('despacho.pwa');
        }

        return view('despacho.viaje-activo', [
            'conductor'   => $conductor,
            'viajeActivo' => $viajeActivo,
        ]);
    }

    /**
     * GET /conductor/bloqueado - Pantalla roja del candado financiero/documentario.
     */
    public function bloqueado(Request $request): View
    {
        $motivo = $request->session()->get(
            'motivo_bloqueo',
            'Cuenta bloqueada: regulariza tu comisión de S/ 1.00 diario o tu permiso municipal para volver a recibir viajes.'
        );

        return view('despacho.bloqueado', ['motivo' => $motivo]);
    }

    /**
     * POST /conductor/salir - Cierra la sesión del conductor (volver a selección).
     */
    public function salir(Request $request): RedirectResponse
    {
        $request->session()->forget('conductor_id');

        return redirect()->route('despacho.seleccion');
    }

    /**
     * GET /conductor/demo/crear-solicitud - SOLO DEMO: inserta una solicitud de ejemplo
     * ("Real Plaza" → "El Tambo", oferta S/ 6.00) para probar el radar sin depender del bot.
     */
    public function demoCrearSolicitud(): RedirectResponse
    {
        DB::table('solicitudes_viaje')->insert([
            'cliente_telefono' => '+51999888777',
            'cliente_nombre'   => 'María Quispe (demo)',
            'origen_texto'     => 'Real Plaza Huancayo',
            'destino_texto'    => 'El Tambo',
            'tipo_servicio'    => 'urbano',
            'precio_sugerido'  => 7.00,
            'oferta_cliente'   => 6.00,
            'precio_final'     => null,
            'conductor_id'     => null,
            'estado'           => 'pendiente',
            'created_at'       => now(),
            'updated_at'       => now(),
        ]);

        return back()->with('demo_ok', 'Solicitud de ejemplo creada: Real Plaza → El Tambo por S/ 6.00.');
    }
}
