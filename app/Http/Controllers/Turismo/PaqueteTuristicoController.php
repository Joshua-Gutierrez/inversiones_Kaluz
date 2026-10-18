<?php

namespace App\Http\Controllers\Turismo;

use App\Http\Controllers\Controller;
use App\Services\Turismo\PrioridadFlotaPropiaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * 👨‍💻 ZONA DEV 3 - Controlador de Paquetes Turísticos y Prioridad de Combis/Flota Propia
 */
class PaqueteTuristicoController extends Controller
{
    public function index(): View
    {
        $paquetes = DB::table('paquetes_turisticos')
            ->select([
                'id',
                'titulo',
                'descripcion',
                'imagen_url',
                'itinerario_paradas',
                'duracion_horas',
                'precio_cerrado',
                'vehiculo_recomendado',
                'activo',
            ])
            ->orderByDesc('activo')
            ->orderBy('titulo')
            ->get()
            ->map(fn (object $paquete): object => $this->normalizarPaquete($paquete));

        return view('turismo.index', compact('paquetes'));
    }

    public function create(): View
    {
        return view('turismo.formulario', [
            'paquete' => null,
            'paradas' => $this->paradasIniciales(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        DB::table('paquetes_turisticos')->insert([
            ...$this->datosPaquete($request),
            'created_at' => now(),
        ]);

        return redirect()->away('/turismo')->with('exito', 'El plan turistico fue creado correctamente.');
    }

    public function edit(int $id): View
    {
        $paquete = $this->buscarPaquete($id);

        return view('turismo.formulario', [
            'paquete' => $paquete,
            'paradas' => $paquete->itinerario_paradas,
        ]);
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $this->buscarPaquete($id);

        DB::table('paquetes_turisticos')->where('id', $id)->update($this->datosPaquete($request));

        return redirect()->away('/turismo')->with('exito', 'El plan turistico fue actualizado correctamente.');
    }

    public function reservar(Request $request, PrioridadFlotaPropiaService $prioridadFlotaPropiaService): View|RedirectResponse
    {
        $datos = $request->validate([
            'paquete_id' => ['required', 'integer', 'exists:paquetes_turisticos,id'],
            'cliente_nombre' => ['required', 'string', 'max:100'],
            'cliente_telefono' => ['required', 'string', 'max:20'],
            'fecha_hora' => ['required', 'date', 'after:now'],
            'pasajeros' => ['required', 'integer', 'min:1', 'max:20'],
        ], [
            'paquete_id.required' => 'Selecciona un paquete turistico.',
            'cliente_nombre.required' => 'Ingresa tu nombre completo.',
            'cliente_telefono.required' => 'Ingresa tu numero de WhatsApp.',
            'fecha_hora.required' => 'Selecciona la fecha y hora del tour.',
            'fecha_hora.after' => 'La fecha del tour debe ser futura.',
            'pasajeros.required' => 'Indica la cantidad de pasajeros.',
            'pasajeros.min' => 'La reserva requiere al menos un pasajero.',
        ]);

        $paquete = DB::table('paquetes_turisticos')->where('id', $datos['paquete_id'])->first();

        if ($paquete === null) {
            abort(404, 'El paquete turistico solicitado no existe.');
        }

        $paradas = json_decode((string) $paquete->itinerario_paradas, true, flags: JSON_THROW_ON_ERROR);
        $origen = $paradas[0];
        $destino = $paradas[array_key_last($paradas)];
        $viajeId = DB::table('solicitudes_viaje')->insertGetId([
            'cliente_telefono' => $datos['cliente_telefono'],
            'cliente_nombre' => $datos['cliente_nombre'],
            'origen_texto' => $origen['nombre'],
            'destino_texto' => $destino['nombre'],
            'origen_lat' => $origen['lat'],
            'origen_lng' => $origen['lng'],
            'destino_lat' => $destino['lat'],
            'destino_lng' => $destino['lng'],
            'tipo_servicio' => 'turismo',
            'precio_sugerido' => $paquete->precio_cerrado,
            'oferta_cliente' => $paquete->precio_cerrado,
            'precio_final' => $paquete->precio_cerrado,
            'estado' => 'pendiente',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $asignacion = $prioridadFlotaPropiaService->asignarUnidad([
            'id' => $viajeId,
            'origen_lat' => $origen['lat'],
            'origen_lng' => $origen['lng'],
            'vehiculo_recomendado' => $paquete->vehiculo_recomendado,
        ], $datos['pasajeros']);

        return view('turismo.confirmacion', [
            'viajeId' => $viajeId,
            'asignacion' => $asignacion,
            'fechaHora' => $datos['fecha_hora'],
        ]);
    }

    private function normalizarPaquete(object $paquete): object
    {
        $paquete->itinerario_paradas = json_decode(
            (string) $paquete->itinerario_paradas,
            true,
            flags: JSON_THROW_ON_ERROR,
        );
        $paquete->vehiculo_etiqueta = Str::of($paquete->vehiculo_recomendado)
            ->replace('propia_', '')
            ->upper()
            ->toString();

        return $paquete;
    }

    private function buscarPaquete(int $id): object
    {
        $paquete = DB::table('paquetes_turisticos')->where('id', $id)->first();

        if ($paquete === null) {
            abort(404, 'El paquete turistico solicitado no existe.');
        }

        return $this->normalizarPaquete($paquete);
    }

    /**
     * @return array{titulo: string, descripcion: string, imagen_url: string|null, itinerario_paradas: string, duracion_horas: int, precio_cerrado: float, vehiculo_recomendado: string, activo: bool}
     */
    private function datosPaquete(Request $request): array
    {
        $datos = $request->validate([
            'titulo' => ['required', 'string', 'max:150'],
            'descripcion' => ['required', 'string', 'max:1000'],
            'imagen_url' => ['nullable', 'url', 'max:2048'],
            'duracion_horas' => ['required', 'integer', 'min:1', 'max:24'],
            'precio_cerrado' => ['required', 'numeric', 'min:0'],
            'vehiculo_recomendado' => ['required', 'in:propia_combi,propia_auto'],
            'activo' => ['nullable', 'boolean'],
            'paradas' => ['required', 'array', 'min:2', 'max:15'],
            'paradas.*.nombre' => ['required', 'string', 'max:150'],
            'paradas.*.lat' => ['required', 'numeric', 'between:-90,90'],
            'paradas.*.lng' => ['required', 'numeric', 'between:-180,180'],
        ], [
            'titulo.required' => 'Ingresa el nombre del plan.',
            'descripcion.required' => 'Ingresa una descripcion para el chatbot.',
            'imagen_url.url' => 'La portada debe ser una URL valida.',
            'paradas.min' => 'El itinerario debe tener al menos dos paradas.',
        ]);

        return [
            'titulo' => $datos['titulo'],
            'descripcion' => $datos['descripcion'],
            'imagen_url' => $datos['imagen_url'] ?: null,
            'itinerario_paradas' => json_encode($datos['paradas'], JSON_THROW_ON_ERROR),
            'duracion_horas' => $datos['duracion_horas'],
            'precio_cerrado' => $datos['precio_cerrado'],
            'vehiculo_recomendado' => $datos['vehiculo_recomendado'],
            'activo' => $request->boolean('activo'),
        ];
    }

    /**
     * @return array<int, array{nombre: string, lat: float, lng: float}>
     */
    private function paradasIniciales(): array
    {
        return [
            ['nombre' => 'Huancayo', 'lat' => -12.0651, 'lng' => -75.2049],
            ['nombre' => '', 'lat' => 0.0, 'lng' => 0.0],
        ];
    }
}
