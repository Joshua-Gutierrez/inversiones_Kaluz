<?php

namespace Tests\Unit\Finanzas;

use App\Models\Conductor;
use App\Models\PagoComision;
use App\Services\Finanzas\ValidacionCandadoService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ValidacionCandadoServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('conductores', function (Blueprint $table): void {
            $table->id();
            $table->string('nombre');
            $table->string('dni')->nullable();
            $table->string('telefono_whatsapp')->nullable();
            $table->string('placa')->nullable();
            $table->string('modelo_vehiculo')->nullable();
            $table->string('tipo_flota')->default('afiliado');
            $table->string('estado_comision')->default('al_dia');
            $table->timestamp('fecha_vencimiento_comision')->nullable();
            $table->date('fecha_vencimiento_permiso')->nullable();
            $table->boolean('habilitado')->default(false);
            $table->decimal('lat_actual', 10, 7)->nullable();
            $table->decimal('lng_actual', 10, 7)->nullable();
            $table->timestamps();
        });

        Schema::create('pagos_comisiones', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('conductor_id');
            $table->decimal('monto', 8, 2)->default(1);
            $table->unsignedInteger('dias_pagados')->default(1);
            $table->string('metodo_pago')->nullable();
            $table->text('comprobante_url')->nullable();
            $table->string('estado')->default('pendiente');
            $table->timestamp('fecha_registro')->nullable();
            $table->timestamp('fecha_aprobacion')->nullable();
        });
    }

    public function test_approving_a_pending_payment_reduces_debt_and_unlocks_an_eligible_driver(): void
    {
        $conductor = $this->conductor([
            'estado_comision' => 'moroso',
            'fecha_vencimiento_comision' => now()->subDay(),
            'habilitado' => false,
        ]);
        $pago = PagoComision::query()->create([
            'conductor_id' => $conductor->id,
            'monto' => 1.00,
            'dias_pagados' => 1,
            'metodo_pago' => 'yape',
            'estado' => 'pendiente',
        ]);

        $service = app(ValidacionCandadoService::class);

        self::assertTrue($service->aprobarPago($pago->id));
        self::assertSame('aprobado', $pago->fresh()->estado);
        self::assertNotNull($pago->fresh()->fecha_aprobacion);
        self::assertSame('al_dia', $conductor->fresh()->estado_comision);
        self::assertTrue($conductor->fresh()->habilitado);
        self::assertTrue($service->verificarEstadoConductor($conductor->id)['habilitado']);
    }

    public function test_rejected_payments_do_not_change_driver_debt_and_cannot_be_processed_twice(): void
    {
        $conductor = $this->conductor([
            'estado_comision' => 'moroso',
            'fecha_vencimiento_comision' => now()->subDay(),
            'habilitado' => false,
        ]);
        $pago = PagoComision::query()->create([
            'conductor_id' => $conductor->id,
            'monto' => 1.00,
            'metodo_pago' => 'yape',
            'estado' => 'pendiente',
        ]);

        $service = app(ValidacionCandadoService::class);

        self::assertTrue($service->rechazarPago($pago->id));
        self::assertFalse($service->aprobarPago($pago->id));
        self::assertSame('rechazado', $pago->fresh()->estado);
        self::assertSame('moroso', $conductor->fresh()->estado_comision);
        self::assertFalse($conductor->fresh()->habilitado);
    }

    public function test_payment_amount_must_cover_the_days_claimed(): void
    {
        $conductor = $this->conductor([
            'estado_comision' => 'moroso',
            'fecha_vencimiento_comision' => now()->subDay(),
            'habilitado' => false,
        ]);
        $pago = PagoComision::query()->create([
            'conductor_id' => $conductor->id,
            'monto' => 0.50,
            'dias_pagados' => 1,
            'metodo_pago' => 'yape',
            'estado' => 'pendiente',
        ]);

        try {
            app(ValidacionCandadoService::class)->aprobarPago($pago->id);
            self::fail('No se debe aprobar un pago que no cubre la comisión diaria.');
        } catch (ValidationException) {
            self::assertSame('pendiente', $pago->fresh()->estado);
            self::assertSame('moroso', $conductor->fresh()->estado_comision);
            self::assertFalse($conductor->fresh()->habilitado);
        }
    }

    public function test_unknown_or_documentarily_invalid_drivers_are_blocked(): void
    {
        $service = app(ValidacionCandadoService::class);
        self::assertFalse($service->verificarEstadoConductor(999)['habilitado']);

        $conductor = $this->conductor(['fecha_vencimiento_permiso' => null]);
        self::assertFalse($service->verificarEstadoConductor($conductor->id)['habilitado']);
    }

    public function test_padron_displays_database_records_and_supports_search(): void
    {
        $this->conductor(['nombre' => 'Ana Conductora']);
        $this->conductor([
            'nombre' => 'Bruno Conductor',
            'estado_comision' => 'moroso',
            'fecha_vencimiento_comision' => now()->subDay(),
            'habilitado' => false,
        ]);

        $response = $this->get(route('finanzas.padron', ['buscar' => 'Ana']));

        $response->assertOk()
            ->assertSee('Ana Conductora')
            ->assertDontSee('Bruno Conductor')
            ->assertDontSee('Juan Pérez Huamán');
    }

    public function test_demo_mode_shows_only_synthetic_read_only_records(): void
    {
        $this->app['env'] = 'local';
        DB::enableQueryLog();
        DB::flushQueryLog();

        $this->get(route('finanzas.padron', ['demo' => 1]))
            ->assertOk()
            ->assertSee('Vista de demostración')
            ->assertSee('Conductor Demo 01')
            ->assertSee('Conductor Demo 08')
            ->assertSee('Solo lectura')
            ->assertDontSee('Registrar socio o unidad propia')
            ->assertDontSee('Guardar cambios')
            ->assertDontSee('Aprobar')
            ->assertDontSee('Rechazar')
            ->assertDontSee('Exportar CSV')
            ->assertSee('name="demo" value="1"', false)
            ->assertSee('demo=1', false);

        $this->get(route('finanzas.padron', ['demo' => 1, 'estado' => 'habilitado']))
            ->assertOk()
            ->assertSee('Conductor Demo 01')
            ->assertDontSee('Conductor Demo 07')
            ->assertSee('Vista de demostración');

        self::assertSame([], DB::getQueryLog(), 'La vista de demostración no debe consultar la base de datos.');
    }

    public function test_demo_query_parameter_is_ignored_outside_local_environment(): void
    {
        $this->app['env'] = 'production';

        $this->get(route('finanzas.padron', ['demo' => 1]))
            ->assertOk()
            ->assertDontSee('Vista de demostración')
            ->assertDontSee('Conductor Demo 01');
    }

    public function test_driver_id_in_query_string_cannot_bypass_the_financial_lock(): void
    {
        $conductor = $this->conductor();

        $this->get(route('despacho.pwa', ['conductor_id' => $conductor->id]))
            ->assertRedirect(route('despacho.pwa.bloqueado'));
    }

    public function test_new_conductors_start_with_the_financial_lock_enabled(): void
    {
        $this->post(route('finanzas.padron.conductores.store'), [
            'nombre' => 'Nuevo Socio',
            'dni' => '12345678',
            'telefono_whatsapp' => '999123456',
            'placa' => 'ABC-123',
            'modelo_vehiculo' => 'Sedan',
            'tipo_flota' => 'afiliado',
            'fecha_vencimiento_permiso' => today()->addYear()->toDateString(),
        ])->assertRedirect(route('finanzas.padron'));

        $conductor = Conductor::query()->where('dni', '12345678')->firstOrFail();
        self::assertSame('moroso', $conductor->estado_comision);
        self::assertFalse($conductor->habilitado);
    }

    public function test_updating_an_expired_permit_immediately_relocks_the_driver(): void
    {
        $conductor = $this->conductor();

        $this->patch(route('finanzas.padron.conductores.actualizar', $conductor), [
            'nombre' => $conductor->nombre,
            'dni' => '12345678',
            'telefono_whatsapp' => '999123456',
            'placa' => 'ABC-123',
            'modelo_vehiculo' => 'Sedan',
            'tipo_flota' => 'afiliado',
            'fecha_vencimiento_permiso' => today()->subDay()->toDateString(),
        ])->assertRedirect(route('finanzas.padron'));

        self::assertFalse($conductor->fresh()->habilitado);
    }

    private function conductor(array $attributes = []): Conductor
    {
        return Conductor::query()->create(array_merge([
            'nombre' => 'Conductor de prueba',
            'estado_comision' => 'al_dia',
            'fecha_vencimiento_comision' => now()->addDay(),
            'fecha_vencimiento_permiso' => today()->addDays(10),
            'habilitado' => true,
        ], $attributes));
    }
}
