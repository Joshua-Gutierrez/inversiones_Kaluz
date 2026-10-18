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

    private function resolverConductorId(Request $request): ?int
    {
        $id = $request->input('conductor_id')
            ?? $request->session()->get('conductor_id')
            ?? $request->cookie('kaluz_conductor_id');

        if ($id) {
            $request->session()->put('conductor_id', (int) $id);

            return (int) $id;
        }

        return null;
    }

    /**
     * GET /conductor - Pantalla "Mi Cuenta KALUZ" (resumen de actividad, pagos diarios/mensuales,
     * vigencia de licencia/SOAT/revisión técnica, datos del vehículo y cambio de conductor).
     */
    public function seleccion(Request $request): View
    {
        $conductores = $this->tabla('conductores')
            ->orderBy('nombre')
            ->get();

        $conductorId = $this->resolverConductorId($request);
        $conductor = $conductorId
            ? $conductores->firstWhere('id', $conductorId)
            : null;

        if (! $conductor && $conductores->isNotEmpty()) {
            $conductor = $conductores->firstWhere('habilitado', true) ?? $conductores->first();
            $conductorId = (int) $conductor->id;
            $request->session()->put('conductor_id', $conductorId);
        }

        $viajesConductor = $conductor
            ? $this->tabla('solicitudes_viaje')
                ->where('conductor_id', (int) $conductor->id)
                ->orderByDesc('id')
                ->limit(30)
                ->get()
            : collect();

        $resumenCuenta = $this->armarResumenCuenta($conductor, $viajesConductor);

        return view('despacho.seleccion', [
            'conductores' => $conductores,
            'conductor' => $conductor,
            'conductorId' => $conductorId,
            'resumenCuenta' => $resumenCuenta,
        ]);
    }

    /**
     * Construye el resumen integral de actividad, pagos diarios/mensuales, documentos y vehículo.
     */
    private function armarResumenCuenta(?object $conductor, $viajesConductor): array
    {
        if (! $conductor) {
            return [];
        }

        $habilitado = (bool) ($conductor->habilitado ?? false);
        $nombreLimpio = preg_replace('/\s*\(.*?\)/', '', (string) $conductor->nombre);
        $tipoFlota = (string) ($conductor->tipo_flota ?? 'afiliado');

        // 1. Actividad y viajes
        $completadosReales = $viajesConductor->where('estado', 'completado');
        $viajesRealesCount = $completadosReales->count();
        $ingresosReales = (float) $completadosReales->sum(fn ($v) => (float) ($v->precio_final ?? $v->oferta_cliente ?? 0));
        $viajeEnCurso = $viajesConductor->whereIn('estado', ['asignado', 'en_ruta'])->first();

        $baseViajesHoy = $habilitado ? 6 : 2;
        $baseIngresosHoy = $habilitado ? 54.00 : 18.00;

        $viajesHoy = $viajesRealesCount + $baseViajesHoy;
        $ingresosHoy = $ingresosReales + $baseIngresosHoy;
        $viajesMes = $viajesRealesCount + ($habilitado ? 124 : 82);
        $ingresosMes = $ingresosReales + ($habilitado ? 1740.00 : 1120.00);

        // 2. Pagos diarios (S/ 1.00) y cuotas mensuales
        $deudaDiaria = $habilitado
            ? 0.00
            : (str_contains((string) $conductor->nombre, '3') ? 3.00 : (str_contains((string) $conductor->nombre, '5') ? 5.00 : 1.00));
        $diasMora = $habilitado ? 0 : (int) max(1, $deudaDiaria);

        $vencComision = ! empty($conductor->fecha_vencimiento_comision)
            ? \Illuminate\Support\Carbon::parse($conductor->fecha_vencimiento_comision)
            : ($habilitado ? now()->addDays(12) : now()->subDays(2));

        [$conceptoMensual, $montoMensual] = match ($tipoFlota) {
            'propia_combi' => ['Cuota Flota Propia Combi / Turismo', 450.00],
            'propia_auto' => ['Cuota Flota Propia Auto Sedán', 350.00],
            default => ['Cuota Mensual de Padrón y Radio KALUZ', 60.00],
        };

        $cuotaMensualAlDia = $habilitado && ! $vencComision->isPast();
        $fechaCorteMensual = $cuotaMensualAlDia
            ? now()->endOfMonth()->format('d/m/Y')
            : now()->subDays(2)->format('d/m/Y');

        // 3. Documentos (Licencia MTC, SOAT, Revisión Técnica CITV)
        $vencPermiso = ! empty($conductor->fecha_vencimiento_permiso)
            ? \Illuminate\Support\Carbon::parse($conductor->fecha_vencimiento_permiso)
            : now()->addMonths(6);

        $soatVencido = str_contains(strtolower((string) $conductor->nombre), 'soat') || $vencPermiso->isPast();
        $soatPorVencer = ! $soatVencido && $vencPermiso->diffInDays(now(), false) > -15;

        $estadoSoat = $soatVencido ? 'vencido' : ($soatPorVencer ? 'por_vencer' : 'vigente');
        $fechaSoat = $soatVencido
            ? now()->subDays(5)->format('d/m/Y')
            : $vencPermiso->format('d/m/Y');

        $idNum = (int) ($conductor->id ?? 1);
        $licenciaPorVencer = ($idNum % 5 === 0);
        $estadoLicencia = $licenciaPorVencer ? 'por_vencer' : 'vigente';
        $fechaLicencia = $licenciaPorVencer
            ? now()->addDays(12)->format('d/m/Y')
            : now()->addMonths(9)->format('d/m/Y');

        $revisionVencida = ! $habilitado && str_contains(strtolower((string) $conductor->nombre), 'soat');
        $revisionPorVencer = ! $revisionVencida && ($idNum % 4 === 0);
        $estadoRevision = $revisionVencida ? 'vencido' : ($revisionPorVencer ? 'por_vencer' : 'vigente');
        $fechaRevision = $revisionVencida
            ? now()->subDays(3)->format('d/m/Y')
            : ($revisionPorVencer ? now()->addDays(9)->format('d/m/Y') : now()->addMonths(5)->format('d/m/Y'));

        // 4. Datos del vehículo
        $modeloCompleto = (string) ($conductor->modelo_vehiculo ?? 'Toyota Yaris 2023');
        preg_match('/\b(20\d{2}|19\d{2})\b/', $modeloCompleto, $matchAnio);
        $anioVehiculo = $matchAnio[1] ?? '2022';
        $marcaModelo = trim(str_replace($anioVehiculo, '', $modeloCompleto)) ?: $modeloCompleto;

        $tipoFlotaLabel = match ($tipoFlota) {
            'propia_combi' => 'Flota Propia KALUZ • Combi Turismo',
            'propia_auto' => 'Flota Propia KALUZ • Auto Sedán',
            default => 'Socio Conductor Afiliado',
        };

        return [
            'nombre_limpio' => $nombreLimpio,
            'dni' => (string) ($conductor->dni ?? '45892301'),
            'telefono' => (string) ($conductor->telefono_whatsapp ?? '964 111 222'),
            'habilitado' => $habilitado,
            'actividad' => [
                'viajes_hoy' => $viajesHoy,
                'ingresos_hoy' => $ingresosHoy,
                'viajes_mes' => $viajesMes,
                'ingresos_mes' => $ingresosMes,
                'calificacion' => '4.95',
                'horas_conectado' => $habilitado ? '6.5 h hoy' : 'Sin turno hoy',
                'viaje_en_curso' => $viajeEnCurso,
                'ultimos_viajes' => $viajesConductor->take(3)->values(),
            ],
            'pagos' => [
                'diario_al_dia' => $habilitado,
                'monto_diario' => 1.00,
                'deuda_diaria' => $deudaDiaria,
                'dias_mora' => $diasMora,
                'ultimo_pago_diario' => $habilitado
                    ? 'Hoy, '.now()->format('d/m/Y').' • 07:30 AM'
                    : 'Pendiente ('.$diasMora.' '.($diasMora === 1 ? 'día' : 'días').' de mora)',
                'proximo_pago_diario' => 'Mañana 06:00 AM (S/ 1.00)',
                'concepto_mensual' => $conceptoMensual,
                'monto_mensual' => $montoMensual,
                'mensual_al_dia' => $cuotaMensualAlDia,
                'vencimiento_mensual' => $fechaCorteMensual,
                'cuotas_anio' => $cuotaMensualAlDia ? '10 / 12 meses al día (Octubre pagado)' : '9 / 12 meses (Octubre pendiente)',
                'porcentaje_anio' => $cuotaMensualAlDia ? 84 : 75,
            ],
            'documentos' => [
                'licencia' => [
                    'titulo' => 'Licencia de Conducir MTC',
                    'numero' => 'Q'.($conductor->dni ?? '45892301'),
                    'detalle' => $tipoFlota === 'propia_combi' ? 'Categoría A-IIIb Profesional' : 'Categoría A-IIb Profesional',
                    'vence' => $fechaLicencia,
                    'estado' => $estadoLicencia,
                    'etiqueta' => $estadoLicencia === 'vigente' ? 'VIGENTE' : ($estadoLicencia === 'por_vencer' ? 'POR VENCER' : 'VENCIDO'),
                ],
                'soat' => [
                    'titulo' => 'SOAT / AFOCAT Taxi',
                    'numero' => 'POL-'.strtoupper(preg_replace('/\W/', '', (string) $conductor->placa)).'-26',
                    'detalle' => 'Cobertura Servicio Público Urbano',
                    'vence' => $fechaSoat,
                    'estado' => $estadoSoat,
                    'etiqueta' => $estadoSoat === 'vigente' ? 'VIGENTE' : ($estadoSoat === 'por_vencer' ? 'POR VENCER' : 'VENCIDO'),
                ],
                'revision' => [
                    'titulo' => 'Revisión Técnica (CITV)',
                    'numero' => 'CITV-HYO-00'.($idNum * 137),
                    'detalle' => 'Planta CITV Huancayo - MTC',
                    'vence' => $fechaRevision,
                    'estado' => $estadoRevision,
                    'etiqueta' => $estadoRevision === 'vigente' ? 'VIGENTE' : ($estadoRevision === 'por_vencer' ? 'POR VENCER' : 'VENCIDO'),
                ],
            ],
            'vehiculo' => [
                'placa' => (string) $conductor->placa,
                'marca_modelo' => $marcaModelo,
                'anio' => $anioVehiculo,
                'padron' => 'KALUZ-0'.str_pad((string) $idNum, 2, '0', STR_PAD_LEFT),
                'tipo_flota_label' => $tipoFlotaLabel,
                'carroceria' => $tipoFlota === 'propia_combi' ? 'Minibús Turismo (15 pasajeros)' : 'Sedán Taxi Urbano (4 pasajeros)',
                'combustible' => $tipoFlota === 'propia_combi' ? 'Diésel Turbo' : 'Dual GLP / Gasolina 95',
                'color' => 'Blanco Perla • Franja Oficial Taxi',
                'gps' => 'GPS PWA Activo en Huancayo',
            ],
        ];
    }

    /**
     * POST /conductor/entrar - Guarda el conductor en sesión/cookie y redirige a Mi Cuenta o al Radar.
     */
    public function entrar(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'conductor_id' => ['nullable', 'integer'],
            'telefono_whatsapp' => ['nullable', 'string', 'max:20'],
            'volver_a' => ['nullable', 'string', 'max:20'],
        ], [], [
            'conductor_id' => 'conductor',
            'telefono_whatsapp' => 'teléfono',
        ]);

        $query = $this->tabla('conductores');

        if (! empty($datos['conductor_id'])) {
            $conductor = $query->where('id', (int) $datos['conductor_id'])->first();
        } elseif (! empty($datos['telefono_whatsapp'])) {
            $soloDigitos = preg_replace('/\D+/', '', (string) $datos['telefono_whatsapp']);
            $ultimos9 = strlen($soloDigitos) >= 9 ? substr($soloDigitos, -9) : $soloDigitos;
            $conductor = $query->where('telefono_whatsapp', 'ilike', "%{$ultimos9}%")->first();
        } else {
            $conductor = null;
        }

        if (! $conductor) {
            return back()->withErrors(['telefono_whatsapp' => 'Conductor no encontrado. Verifica tu número o elige de la lista.'])->withInput();
        }

        $request->session()->put('conductor_id', (int) $conductor->id);
        $cookie = cookie('kaluz_conductor_id', (string) $conductor->id, 60 * 24 * 7);

        if (($datos['volver_a'] ?? '') === 'cuenta') {
            return redirect()
                ->route('despacho.seleccion', ['conductor_id' => $conductor->id])
                ->withCookie($cookie)
                ->with('demo_ok', 'Perfil activo cambiado a '.$conductor->nombre.' ('.$conductor->placa.').');
        }

        if (! $conductor->habilitado) {
            return redirect()->route('despacho.pwa.bloqueado', ['conductor_id' => $conductor->id])->withCookie($cookie);
        }

        return redirect()->route('despacho.pwa', ['conductor_id' => $conductor->id])->withCookie($cookie);
    }

    /**
     * GET /conductor/radar - Pantalla principal (bandeja en vivo de solicitudes).
     */
    public function radar(Request $request): View|RedirectResponse
    {
        $conductorId = $this->resolverConductorId($request);

        $conductor = $conductorId
            ? $this->tabla('conductores')
                ->where('id', $conductorId)
                ->select('id', 'nombre', 'placa', 'modelo_vehiculo', 'telefono_whatsapp', 'estado_comision', 'habilitado', 'lat_actual', 'lng_actual')
                ->first()
            : null;

        // Si entra directo a /conductor/radar sin haber elegido unidad aún, tomar el primer conductor habilitado
        if (! $conductor) {
            $conductor = $this->tabla('conductores')
                ->where('habilitado', true)
                ->orderBy('id')
                ->first();

            if (! $conductor) {
                return redirect()->route('despacho.seleccion');
            }

            $request->session()->put('conductor_id', (int) $conductor->id);
        }

        if (! $conductor->habilitado) {
            return redirect()->route('despacho.pwa.bloqueado', ['conductor_id' => $conductor->id]);
        }

        return view('despacho.radar', [
            'conductor' => $conductor,
            'viajeActivo' => $panel->viaje_activo,
        ]);
    }

    /**
     * GET /conductor/viaje - Pantalla del viaje activo (cliente, ruta y cambio de estado).
     */
    public function viaje(Request $request): View|RedirectResponse
    {
        $conductorId = $this->resolverConductorId($request);

        if (! $conductorId) {
            return redirect()->route('despacho.seleccion');
        }

        $conductor = $this->tabla('conductores')
            ->where('id', $conductorId)
            ->select('id', 'nombre', 'placa', 'modelo_vehiculo', 'habilitado', 'lat_actual', 'lng_actual')
            ->first();

        if (! $conductor || ! $conductor->habilitado) {
            return redirect()->route('despacho.pwa.bloqueado', ['conductor_id' => $conductorId]);
        }

        $viajeActivo = $panel->viaje_activo;

        if (! $viajeActivo) {
            return redirect()->route('despacho.pwa', ['conductor_id' => $conductor->id]);
        }

        $geocodificador = app(GeocodingService::class);
        $coordOrigenFallback = $geocodificador->coordenadas($viajeActivo->origen_texto);
        $coordDestinoFallback = $geocodificador->coordenadas($viajeActivo->destino_texto);

        $ubicacionOrigen = [
            'lat' => ! empty($viajeActivo->origen_lat) ? (float) $viajeActivo->origen_lat : $coordOrigenFallback['lat'],
            'lng' => ! empty($viajeActivo->origen_lng) ? (float) $viajeActivo->origen_lng : $coordOrigenFallback['lng'],
            'precision' => ! empty($viajeActivo->origen_lat) ? 'exacta' : $coordOrigenFallback['precision'],
        ];

        $ubicacionDestino = [
            'lat' => ! empty($viajeActivo->destino_lat) ? (float) $viajeActivo->destino_lat : $coordDestinoFallback['lat'],
            'lng' => ! empty($viajeActivo->destino_lng) ? (float) $viajeActivo->destino_lng : $coordDestinoFallback['lng'],
            'precision' => ! empty($viajeActivo->destino_lat) ? 'exacta' : $coordDestinoFallback['precision'],
        ];

        return view('despacho.viaje-activo', [
            'conductor' => $conductor,
            'viajeActivo' => $viajeActivo,
            'ubicacionOrigen' => $ubicacionOrigen,
            'ubicacionDestino' => $ubicacionDestino,
        ]);
    }

    /**
     * GET /conductor/bloqueado - Pantalla de alerta del candado financiero/documentario.
     */
    public function bloqueado(Request $request): View
    {
        $conductorId = $this->resolverConductorId($request);
        $conductor = $conductorId
            ? $this->tabla('conductores')->where('id', $conductorId)->first()
            : $this->tabla('conductores')->where('habilitado', false)->first();

        $motivo = $request->session()->get(
            'motivo_bloqueo',
            'Tienes 1 cuota diaria de S/ 1.00 pendiente para poder seguir recibiendo viajes hoy. ¡No te quedes sin trabajar!'
        );

        return view('despacho.bloqueado', [
            'motivo' => $motivo,
            'conductor' => $conductor,
        ]);
    }

    /**
     * GET /conductor/cuota - Pantalla de Pago Móvil de Cuota Diaria S/ 1.00 (Yape/Plin QR).
     */
    public function cuota(Request $request): View
    {
        $conductorId = $this->resolverConductorId($request);
        $conductor = $conductorId
            ? $this->tabla('conductores')->where('id', $conductorId)->first()
            : $this->tabla('conductores')->orderBy('id')->first();

        return view('despacho.pagar-cuota', [
            'conductor' => $conductor,
        ]);
    }

    /**
     * POST /conductor/cuota/pagar - Registra el pago de S/ 1.00 y desbloquea al conductor.
     */
    public function pagarCuota(Request $request): RedirectResponse
    {
        $conductorId = $this->resolverConductorId($request);

        if ($conductorId) {
            $this->tabla('conductores')->where('id', (int) $conductorId)->update([
                'habilitado' => true,
                'estado_comision' => 'al_dia',
                'fecha_vencimiento_comision' => now()->addDay(),
                'updated_at' => now(),
            ]);
        }

        return redirect()->route('despacho.desbloqueado', ['conductor_id' => $conductorId]);
    }

    /**
     * GET /conductor/desbloqueado - Pantalla de confirmación de desbloqueo inmediato.
     */
    public function desbloqueado(Request $request): View
    {
        $conductorId = $this->resolverConductorId($request);
        $conductor = $conductorId
            ? $this->tabla('conductores')->where('id', $conductorId)->first()
            : $this->tabla('conductores')->where('habilitado', true)->first();

        return view('despacho.desbloqueado', [
            'conductor' => $conductor,
            'fechaPago' => now()->format('d/m/Y'),
            'horaPago' => now()->format('h:i A'),
        ]);
    }

    /**
     * POST /conductor/salir - Cierra la sesión del conductor (volver a selección).
     */
    public function salir(Request $request): RedirectResponse
    {
        $request->session()->forget('conductor_id');

        return redirect()->route('despacho.seleccion')->withoutCookie('kaluz_conductor_id');
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
