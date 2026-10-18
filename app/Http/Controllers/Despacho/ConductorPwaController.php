<?php

namespace App\Http\Controllers\Despacho;

use App\Http\Controllers\Controller;
use App\Services\Despacho\AtomicAssignmentService;
use App\Services\Despacho\GeocodingService;
use App\Services\Supabase\Supabase;
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
    private function tabla(string $table)
    {
        if (extension_loaded('pdo_pgsql')) {
            try {
                DB::connection()->getPdo();
                return DB::table($table);
            } catch (\Throwable) {
                // Si la conexión falla, cae a Supabase REST
            }
        }

        return Supabase::table($table);
    }

    /**
     * GET /conductor - Pantalla de entrada / selección de conductor (demo, sin auth compleja).
     */
    public function seleccion(Request $request): View
    {
        $conductores = $this->tabla('conductores')
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
            'conductor_id' => ['nullable', 'integer', 'exists:conductores,id'],
            'telefono_whatsapp' => ['nullable', 'string', 'max:20'],
        ], [], [
            'conductor_id' => 'conductor',
            'telefono_whatsapp' => 'teléfono',
        ]);

        $query = $this->tabla('conductores');

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

        $conductor = $this->tabla('conductores')
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

        $viajeActivo = app(AtomicAssignmentService::class)
            ->viajeActivoDe((int) $conductor->id);

        return view('despacho.radar', [
            'conductor' => $conductor,
            'viajeActivo' => $viajeActivo,
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

        $conductor = $this->tabla('conductores')
            ->where('id', $conductorId)
            ->select('id', 'nombre', 'placa', 'modelo_vehiculo', 'habilitado', 'lat_actual', 'lng_actual')
            ->first();

        if (! $conductor || ! $conductor->habilitado) {
            return redirect()->route('despacho.pwa.bloqueado');
        }

        $viajeActivo = app(AtomicAssignmentService::class)
            ->viajeActivoDe((int) $conductor->id);

        if (! $viajeActivo) {
            return redirect()->route('despacho.pwa');
        }

        $geocodificador = app(GeocodingService::class);

        return view('despacho.viaje-activo', [
            'conductor' => $conductor,
            'viajeActivo' => $viajeActivo,
            'ubicacionOrigen' => $geocodificador->coordenadas($viajeActivo->origen_texto),
            'ubicacionDestino' => $geocodificador->coordenadas($viajeActivo->destino_texto),
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
        $this->tabla('solicitudes_viaje')->insert([
            'cliente_telefono' => '+51999888777',
            'cliente_nombre' => 'María Quispe (demo)',
            'origen_texto' => 'Real Plaza Huancayo',
            'destino_texto' => 'El Tambo',
            'tipo_servicio' => 'urbano',
            'precio_sugerido' => 7.00,
            'oferta_cliente' => 6.00,
            'precio_final' => null,
            'conductor_id' => null,
            'estado' => 'pendiente',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return back()->with('demo_ok', 'Solicitud de ejemplo creada: Real Plaza → El Tambo por S/ 6.00.');
    }

    /**
     * GET /conductor/demo/sembrar-conductores - SOLO DEMO: si la tabla conductores está vacía,
     * inserta los 8 conductores semilla de Huancayo (5 al día, 3 morosos) según CONTEXT.md.
     */
    public function demoSembrarConductores(): RedirectResponse
    {
        $ahora = now();

        $semillas = [
            [
                'nombre' => 'Juan Carlos Quispe',
                'dni' => '45892301',
                'telefono_whatsapp' => '964111222',
                'placa' => 'W2X-780',
                'modelo_vehiculo' => 'Toyota HiAce Combi 2022',
                'tipo_flota' => 'propia_combi',
                'estado_comision' => 'al_dia',
                'fecha_vencimiento_comision' => $ahora->copy()->addDays(15),
                'fecha_vencimiento_permiso' => $ahora->copy()->addMonths(6)->toDateString(),
                'habilitado' => true,
                'lat_actual' => -12.0651,
                'lng_actual' => -75.2049,
                'created_at' => $ahora,
                'updated_at' => $ahora,
            ],
            [
                'nombre' => 'Luis Alberto Huamán',
                'dni' => '41209873',
                'telefono_whatsapp' => '964222333',
                'placa' => 'W1A-456',
                'modelo_vehiculo' => 'Toyota Yaris 2023',
                'tipo_flota' => 'propia_auto',
                'estado_comision' => 'al_dia',
                'fecha_vencimiento_comision' => $ahora->copy()->addDays(20),
                'fecha_vencimiento_permiso' => $ahora->copy()->addMonths(8)->toDateString(),
                'habilitado' => true,
                'lat_actual' => -12.0665,
                'lng_actual' => -75.2035,
                'created_at' => $ahora,
                'updated_at' => $ahora,
            ],
            [
                'nombre' => 'Carlos Mendoza Ramos',
                'dni' => '47890123',
                'telefono_whatsapp' => '964333444',
                'placa' => 'AF-7821',
                'modelo_vehiculo' => 'Nissan Versa 2021',
                'tipo_flota' => 'afiliado',
                'estado_comision' => 'al_dia',
                'fecha_vencimiento_comision' => $ahora->copy()->addDays(7),
                'fecha_vencimiento_permiso' => $ahora->copy()->addMonths(4)->toDateString(),
                'habilitado' => true,
                'lat_actual' => -12.0632,
                'lng_actual' => -75.2078,
                'created_at' => $ahora,
                'updated_at' => $ahora,
            ],
            [
                'nombre' => 'Marcos Soto Cárdenas',
                'dni' => '43019284',
                'telefono_whatsapp' => '964444555',
                'placa' => 'W3C-912',
                'modelo_vehiculo' => 'Kia Rio 2020',
                'tipo_flota' => 'afiliado',
                'estado_comision' => 'al_dia',
                'fecha_vencimiento_comision' => $ahora->copy()->addDays(10),
                'fecha_vencimiento_permiso' => $ahora->copy()->addMonths(5)->toDateString(),
                'habilitado' => true,
                'lat_actual' => -12.0680,
                'lng_actual' => -75.2012,
                'created_at' => $ahora,
                'updated_at' => $ahora,
            ],
            [
                'nombre' => 'Jorge David Rojas',
                'dni' => '48192039',
                'telefono_whatsapp' => '964555666',
                'placa' => 'AF-4410',
                'modelo_vehiculo' => 'Hyundai Accent 2019',
                'tipo_flota' => 'afiliado',
                'estado_comision' => 'al_dia',
                'fecha_vencimiento_comision' => $ahora->copy()->addDays(3),
                'fecha_vencimiento_permiso' => $ahora->copy()->addMonths(9)->toDateString(),
                'habilitado' => true,
                'lat_actual' => -12.0610,
                'lng_actual' => -75.2090,
                'created_at' => $ahora,
                'updated_at' => $ahora,
            ],
            // 3 morosos (candado activado):
            [
                'nombre' => 'Pedro Pablo Flores (Debe S/ 1)',
                'dni' => '42901823',
                'telefono_whatsapp' => '964777888',
                'placa' => 'W5E-201',
                'modelo_vehiculo' => 'Chevrolet Sail 2018',
                'tipo_flota' => 'afiliado',
                'estado_comision' => 'moroso',
                'fecha_vencimiento_comision' => $ahora->copy()->subDays(2),
                'fecha_vencimiento_permiso' => $ahora->copy()->addMonths(3)->toDateString(),
                'habilitado' => false,
                'lat_actual' => -12.0651,
                'lng_actual' => -75.2049,
                'created_at' => $ahora,
                'updated_at' => $ahora,
            ],
            [
                'nombre' => 'Raúl Gómez Baldeón (Debe S/ 3)',
                'dni' => '40918273',
                'telefono_whatsapp' => '964888999',
                'placa' => 'W2T-884',
                'modelo_vehiculo' => 'Toyota Corolla 2017',
                'tipo_flota' => 'afiliado',
                'estado_comision' => 'moroso',
                'fecha_vencimiento_comision' => $ahora->copy()->subDays(5),
                'fecha_vencimiento_permiso' => $ahora->copy()->addMonths(2)->toDateString(),
                'habilitado' => false,
                'lat_actual' => -12.0651,
                'lng_actual' => -75.2049,
                'created_at' => $ahora,
                'updated_at' => $ahora,
            ],
            [
                'nombre' => 'Víctor Chávez Meza (SOAT vencido)',
                'dni' => '46890124',
                'telefono_whatsapp' => '964999000',
                'placa' => 'AF-9011',
                'modelo_vehiculo' => 'Nissan Sentra 2016',
                'tipo_flota' => 'afiliado',
                'estado_comision' => 'moroso',
                'fecha_vencimiento_comision' => $ahora->copy()->subDays(1),
                'fecha_vencimiento_permiso' => $ahora->copy()->subDays(10)->toDateString(),
                'habilitado' => false,
                'lat_actual' => -12.0651,
                'lng_actual' => -75.2049,
                'created_at' => $ahora,
                'updated_at' => $ahora,
            ],
        ];

        foreach ($semillas as $s) {
            $existe = $this->tabla('conductores')->where('placa', $s['placa'])->exists();
            if (! $existe) {
                $this->tabla('conductores')->insert($s);
            }
        }

        return back()->with('demo_ok', 'Semilla completada: 5 conductores al día y 3 morosos registrados.');
    }
}
