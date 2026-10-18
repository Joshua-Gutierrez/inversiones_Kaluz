<?php

namespace App\Http\Controllers\Finanzas;

use App\Http\Controllers\Controller;
use App\Models\Conductor;
use App\Models\PagoComision;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
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
        if ($request->boolean('demo') && app()->environment('local')) {
            return $this->vistaDemostracion($request);
        }

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
            'modoDemo' => false,
        ]);
    }

    private function vistaDemostracion(Request $request): View
    {
        $conductoresDemo = collect([
            ['Habilitado', 'al_dia', 45],
            ['Habilitado', 'al_dia', 18],
            ['Habilitado', 'al_dia', 60],
            ['Habilitado', 'al_dia', 90],
            ['Habilitado', 'al_dia', 14],
            ['Moroso', 'moroso', 45],
            ['Moroso', 'moroso', 20],
            ['Permiso vencido', 'al_dia', -5],
        ])->values()->map(function (array $estado, int $indice): Conductor {
            $hoy = now();
            $permiso = $hoy->copy()->addDays($estado[2])->toDateString();
            $vencimientoComision = $estado[1] === 'moroso'
                ? $hoy->copy()->subDays(2)
                : $hoy->copy()->addDay();
            $conductor = new Conductor;
            $conductor->setRawAttributes([
                'id' => $indice + 1,
                'nombre' => 'Conductor Demo '.str_pad((string) ($indice + 1), 2, '0', STR_PAD_LEFT),
                'dni' => str_pad((string) (70000000 + $indice + 1), 8, '0', STR_PAD_LEFT),
                'telefono_whatsapp' => '9000000'.str_pad((string) ($indice + 1), 3, '0', STR_PAD_LEFT),
                'placa' => 'DMO-'.str_pad((string) ($indice + 1), 3, '0', STR_PAD_LEFT),
                'modelo_vehiculo' => $indice === 6 ? 'Toyota Hiace (combi)' : 'Sedan demo',
                'tipo_flota' => $indice === 6 ? 'propia_combi' : 'afiliado',
                'estado_comision' => $estado[1],
                'fecha_vencimiento_comision' => $vencimientoComision,
                'fecha_vencimiento_permiso' => $permiso,
                'habilitado' => $estado[1] === 'al_dia' && $estado[2] >= 0,
            ], true);

            $pagoReciente = new PagoComision;
            $pagoReciente->setRawAttributes([
                'id' => $indice + 1,
                'conductor_id' => $indice + 1,
                'monto' => '1.00',
                'dias_pagados' => 1,
                'metodo_pago' => 'yape',
                'estado' => $indice === 5 ? 'pendiente' : 'aprobado',
                'fecha_registro' => $hoy->copy()->subHours($indice + 1),
                'fecha_aprobacion' => $indice === 5 ? null : $hoy->copy()->subHours($indice + 1),
            ], true);
            $pagoReciente->setRelation('conductor', $conductor);
            $conductor->setRelation('pagosComision', collect([$pagoReciente]));

            return $conductor;
        });

        $habilitado = fn (Conductor $conductor): bool => $conductor->habilitado
            && $conductor->estado_comision === 'al_dia'
            && $conductor->fecha_vencimiento_permiso
            && ! $conductor->fecha_vencimiento_permiso->isBefore(today())
            && $conductor->fecha_vencimiento_comision?->isFuture();
        $habilitados = $conductoresDemo->filter($habilitado)->count();
        $permisosPorVencer = $conductoresDemo->filter(fn (Conductor $conductor): bool => $conductor->fecha_vencimiento_permiso
            && ! $conductor->fecha_vencimiento_permiso->isBefore(today())
            && $conductor->fecha_vencimiento_permiso->lte(today()->addDays(30)))->count();

        $filtrados = $conductoresDemo;
        if ($request->filled('buscar')) {
            $termino = mb_strtolower(trim((string) $request->input('buscar')));
            $filtrados = $filtrados->filter(fn (Conductor $conductor): bool => str_contains(
                mb_strtolower(implode(' ', [
                    $conductor->nombre,
                    $conductor->dni,
                    $conductor->placa,
                ])),
                $termino
            ));
        }
        if ($request->input('estado') === 'habilitado') {
            $filtrados = $filtrados->filter($habilitado);
        } elseif ($request->input('estado') === 'bloqueado') {
            $filtrados = $filtrados->reject($habilitado);
        }

        $conductores = new LengthAwarePaginator(
            $filtrados->values(),
            $filtrados->count(),
            20,
            max(1, (int) $request->query('page', 1)),
            ['path' => route('finanzas.padron'), 'query' => $request->query()]
        );

        $pagosPendientes = $conductoresDemo
            ->flatMap(fn (Conductor $conductor) => $conductor->pagosComision)
            ->where('estado', 'pendiente')
            ->values();
        $pagosPendientes = new LengthAwarePaginator(
            $pagosPendientes,
            $pagosPendientes->count(),
            10,
            max(1, (int) $request->query('pagos_page', 1)),
            ['path' => route('finanzas.padron'), 'query' => $request->query(), 'pageName' => 'pagos_page']
        );

        return view('finanzas.padron', [
            'conductores' => $conductores,
            'pagosPendientes' => $pagosPendientes,
            'estadisticas' => [
                'total' => $conductoresDemo->count(),
                'habilitados' => $habilitados,
                'bloqueados' => $conductoresDemo->count() - $habilitados,
                'permisosPorVencer' => $permisosPorVencer,
            ],
            'filtros' => $request->only(['buscar', 'estado']),
            'modoDemo' => true,
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
