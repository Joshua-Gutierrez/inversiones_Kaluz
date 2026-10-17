<?php

namespace App\Http\Controllers\Finanzas;

use App\Http\Controllers\Controller;
use App\Models\Conductor;
use App\Models\PagoComision;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * 👨‍💻 ZONA DEV 2 - Controlador del Padrón de Socios Conductores (Semáforo de 300 socios)
 */
class PadronController extends Controller
{
    public function index(Request $request): View
    {
        $conductores = $this->conductoresFiltrados($request)
            ->with(['pagosComision' => fn ($query) => $query->orderByDesc('fecha_registro')->limit(1)])
            ->orderBy('nombre')
            ->paginate(20)
            ->withQueryString();

        $pagosPendientes = PagoComision::query()
            ->with('conductor')
            ->where('estado', 'pendiente')
            ->orderByDesc('fecha_registro')
            ->paginate(10, ['*'], 'pagos_page')
            ->withQueryString();

        $total = Conductor::query()->count();
        $habilitados = Conductor::habilitados()->count();

        return view('finanzas.padron', [
            'conductores' => $conductores,
            'pagosPendientes' => $pagosPendientes,
            'estadisticas' => [
                'total' => $total,
                'habilitados' => $habilitados,
                'bloqueados' => max(0, $total - $habilitados),
                'permisosPorVencer' => Conductor::query()
                    ->whereNotNull('fecha_vencimiento_permiso')
                    ->whereDate('fecha_vencimiento_permiso', '>=', now()->toDateString())
                    ->whereDate('fecha_vencimiento_permiso', '<=', now()->addDays(30)->toDateString())
                    ->count(),
            ],
            'filtros' => $request->only(['buscar', 'estado']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:120'],
            'dni' => ['required', 'digits:8', 'unique:conductores,dni'],
            'telefono_whatsapp' => ['required', 'string', 'max:20', 'unique:conductores,telefono_whatsapp'],
            'placa' => ['nullable', 'string', 'max:15', 'unique:conductores,placa'],
            'modelo_vehiculo' => ['nullable', 'string', 'max:80'],
            'tipo_flota' => ['required', 'in:afiliado,propia_auto,propia_combi'],
            'fecha_vencimiento_permiso' => ['required', 'date'],
        ]);

        Conductor::query()->create([
            ...$datos,
            'estado_comision' => 'moroso',
            'fecha_vencimiento_comision' => now(),
            'habilitado' => false,
        ]);

        return redirect()->route('finanzas.padron')->with(
            'estado',
            'Conductor registrado. Permanecerá bloqueado hasta validar el primer pago de comisión.'
        );
    }

    public function actualizar(Request $request, Conductor $conductor): RedirectResponse
    {
        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:120'],
            'dni' => ['required', 'digits:8', 'unique:conductores,dni,'.$conductor->id],
            'telefono_whatsapp' => ['required', 'string', 'max:20', 'unique:conductores,telefono_whatsapp,'.$conductor->id],
            'placa' => ['nullable', 'string', 'max:15', 'unique:conductores,placa,'.$conductor->id],
            'modelo_vehiculo' => ['nullable', 'string', 'max:80'],
            'tipo_flota' => ['required', 'in:afiliado,propia_auto,propia_combi'],
            'fecha_vencimiento_permiso' => ['required', 'date'],
        ]);

        DB::transaction(function () use ($conductor, $datos): void {
            $conductor = Conductor::query()->lockForUpdate()->findOrFail($conductor->id);
            $permisoVigente = now()->toDateString() <= $datos['fecha_vencimiento_permiso'];
            $comisionVigente = $conductor->estado_comision === 'al_dia'
                && $conductor->fecha_vencimiento_comision
                && $conductor->fecha_vencimiento_comision->isFuture();

            $conductor->fill($datos);
            $conductor->habilitado = $permisoVigente && $comisionVigente;
            $conductor->save();
        });

        return redirect()->route('finanzas.padron')->with('estado', 'Datos del conductor actualizados.');
    }

    public function exportar(Request $request): StreamedResponse
    {
        $conductores = $this->conductoresFiltrados($request)
            ->orderBy('nombre')
            ->cursor();

        return response()->streamDownload(function () use ($conductores): void {
            $output = fopen('php://output', 'w');
            if ($output === false) {
                throw new \RuntimeException('No se pudo abrir el flujo de exportación.');
            }

            fputcsv($output, ['Nombre', 'DNI', 'Teléfono', 'Placa', 'Vehículo', 'Deuda (S/)', 'Permiso vence', 'Estado']);

            foreach ($conductores as $conductor) {
                fputcsv($output, [
                    $conductor->nombre,
                    $conductor->dni,
                    $conductor->telefono_whatsapp,
                    $conductor->placa,
                    $conductor->modelo_vehiculo,
                    number_format((float) $conductor->deuda_comision, 2, '.', ''),
                    $conductor->fecha_vencimiento_permiso?->format('Y-m-d'),
                    $conductor->estado_comision,
                ]);
            }

            fclose($output);
        }, 'padron-conductores.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function conductoresFiltrados(Request $request): Builder
    {
        return Conductor::query()
            ->when($request->filled('buscar'), function (Builder $query) use ($request): void {
                $termino = trim((string) $request->input('buscar'));
                $query->where(function (Builder $query) use ($termino): void {
                    $query->whereLike('nombre', "%{$termino}%")
                        ->orWhereLike('dni', "%{$termino}%")
                        ->orWhereLike('placa', "%{$termino}%");
                });
            })
            ->when($request->input('estado') === 'habilitado', fn (Builder $query) => $query->habilitados())
            ->when($request->input('estado') === 'bloqueado', function (Builder $query): void {
                $query->where(function (Builder $query): void {
                    $query->where('habilitado', false)
                        ->orWhere('estado_comision', 'moroso')
                        ->orWhere('fecha_vencimiento_comision', '<', now())
                        ->orWhereDate('fecha_vencimiento_permiso', '<', now()->toDateString())
                        ->orWhereNull('fecha_vencimiento_permiso');
                });
            });
    }
}
