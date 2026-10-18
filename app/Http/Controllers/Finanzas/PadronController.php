<?php

namespace App\Http\Controllers\Finanzas;

use App\Http\Controllers\Controller;
use App\Services\Supabase\Supabase;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * ZONA DEV 2 - Controlador del Padrón de Socios Conductores, Candado Financiero y Vencimientos Documentarios
 */
class PadronController extends Controller
{
    private function tabla(string $table)
    {
        if (extension_loaded('pdo_pgsql')) {
            try {
                DB::connection()->getPdo();

                return DB::table($table);
            } catch (\Throwable) {
                // Fallback a Supabase REST
            }
        }

        return Supabase::table($table);
    }

    public function index(Request $request): View
    {
        $conductores = $this->tabla('conductores')
            ->orderBy('id')
            ->get();

        return view('finanzas.padron', [
            'conductores' => $conductores,
            'filtroInicial' => $request->query('filtro', 'todos'),
            'busquedaInicial' => $request->query('q', ''),
        ]);
    }

    public function cambiarCandado(Request $request, int $id): RedirectResponse
    {
        $accion = $request->input('accion', 'toggle');
        $conductor = $this->tabla('conductores')->where('id', $id)->first();

        if (! $conductor) {
            return back()->with('error', 'Conductor no encontrado.');
        }

        $nuevoHabilitado = match ($accion) {
            'bloquear' => false,
            'habilitar' => true,
            default => ! (bool) $conductor->habilitado,
        };

        $this->tabla('conductores')->where('id', $id)->update([
            'habilitado' => $nuevoHabilitado,
            'estado_comision' => $nuevoHabilitado ? 'al_dia' : 'moroso',
            'fecha_vencimiento_comision' => $nuevoHabilitado ? now()->addDay() : now()->subDay(),
            'updated_at' => now(),
        ]);

        $mensaje = $nuevoHabilitado
            ? "Acceso habilitado para {$conductor->nombre}. Candado liberado."
            : "Candado financiero activado para {$conductor->nombre}. Acceso a viajes bloqueado.";

        return back()->with('exito', $mensaje);
    }

    /**
     * POST /finanzas/conductor/{id}/renovar-documento
     * Confirma la renovación de Licencia, SOAT o Revisión Técnica (+1 año) y desbloquea al conductor.
     */
    public function renovarDocumento(Request $request, int $id): RedirectResponse
    {
        $documento = $request->input('documento', 'Documento');
        $nuevaFecha = $request->input('nueva_fecha') ?: now()->addYear()->toDateString();

        $conductor = $this->tabla('conductores')->where('id', $id)->first();

        if (! $conductor) {
            return back()->with('error', 'Conductor no encontrado.');
        }

        $nombreLimpio = preg_replace('/\s*\(SOAT vencido\)/i', '', (string) $conductor->nombre);

        $this->tabla('conductores')->where('id', $id)->update([
            'nombre' => $nombreLimpio,
            'fecha_vencimiento_permiso' => $nuevaFecha,
            'habilitado' => true,
            'estado_comision' => 'al_dia',
            'updated_at' => now(),
        ]);

        return back()->with(
            'exito',
            "{$documento} de {$nombreLimpio} marcado como RENOVADO (vigente hasta ".Carbon::parse($nuevaFecha)->format('d/m/Y').'). Conductor habilitado.'
        );
    }

    public function store(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:120'],
            'dni' => ['required', 'string', 'max:15'],
            'telefono_whatsapp' => ['required', 'string', 'max:20'],
            'placa' => ['required', 'string', 'max:15'],
            'modelo_vehiculo' => ['required', 'string', 'max:100'],
            'tipo_flota' => ['required', 'in:propia_combi,propia_auto,afiliado'],
            'estado_inicial' => ['nullable', 'in:habilitado,bloqueado'],
        ]);

        $ahora = now();
        $iniciaHabilitado = ($datos['estado_inicial'] ?? 'habilitado') === 'habilitado';

        $this->tabla('conductores')->insert([
            'nombre' => $datos['nombre'],
            'dni' => $datos['dni'],
            'telefono_whatsapp' => $datos['telefono_whatsapp'],
            'placa' => strtoupper($datos['placa']),
            'modelo_vehiculo' => $datos['modelo_vehiculo'],
            'tipo_flota' => $datos['tipo_flota'],
            'estado_comision' => $iniciaHabilitado ? 'al_dia' : 'moroso',
            'fecha_vencimiento_comision' => $iniciaHabilitado ? $ahora->copy()->addDays(15) : $ahora->copy()->subDay(),
            'fecha_vencimiento_permiso' => $ahora->copy()->addMonths(6)->toDateString(),
            'habilitado' => $iniciaHabilitado,
            'lat_actual' => -12.0651 + (mt_rand(-35, 35) / 10000),
            'lng_actual' => -75.2049 + (mt_rand(-35, 35) / 10000),
            'created_at' => $ahora,
            'updated_at' => $ahora,
        ]);

        $estadoTexto = $iniciaHabilitado ? 'HABILITADO' : 'BLOQUEADO (Moroso S/ 1.00 para prueba OCR Yape)';

        return back()->with('exito', "Asociado {$datos['nombre']} ({$datos['placa']}) registrado como {$estadoTexto}.");
    }
}
