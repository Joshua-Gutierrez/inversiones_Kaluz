<?php

namespace Tests\Unit\Finanzas;

use App\Models\Conductor;
use App\Models\PagoComision;
use App\Services\Finanzas\ValidacionCandadoService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
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

        Schema::create('alertas_financieras', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('conductor_id')->constrained('conductores')->cascadeOnDelete();
            $table->string('tipo', 30);
            $table->text('mensaje');
            $table->string('estado', 20)->default('registrada');
            $table->timestamps();
        });

        config(['finanzas.admin_users' => ['kaluz-admin']]);
        $this->withSession(['usuario' => 'kaluz-admin']);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_approving_a_pending_payment_reduces_debt_and_unlocks_an_eligible_driver(): void
    {
        Carbon::setTestNow(now()->endOfMonth()->setTime(12, 0));
        $conductor = $this->conductor([
            'estado_comision' => 'moroso',
            'fecha_vencimiento_comision' => now()->subDay(),
            'habilitado' => false,
        ]);
        $conductor->forceFill(['created_at' => today()->startOfMonth()])->save();
        $pago = PagoComision::query()->create([
            'conductor_id' => $conductor->id,
            'monto' => today()->daysInMonth,
            'dias_pagados' => today()->daysInMonth,
            'metodo_pago' => 'yape',
            'comprobante_url' => 'conductores/'.$conductor->id.'/2026/10/captura.png',
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
        Carbon::setTestNow(now()->endOfMonth()->setTime(12, 0));
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
            'comprobante_url' => 'conductores/'.$conductor->id.'/2026/10/captura.png',
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

    public function test_admin_edit_form_opens_as_a_dialog(): void
    {
        $this->conductor();

        $this->get(route('finanzas.padron'))
            ->assertOk()
            ->assertSee('data-dialog-open="editar-conductor-', false)
            ->assertSee('<dialog id="editar-conductor-', false)
            ->assertSee('Editar conductor')
            ->assertSee('Guardar cambios');

    }

    public function test_padron_uses_database_records_even_when_demo_query_parameter_is_present(): void
    {
        $this->get(route('finanzas.padron', ['demo' => 1]))
            ->assertOk()
            ->assertDontSee('Vista de demostración')
            ->assertDontSee('Conductor Demo')
            ->assertSee('Flota y afiliados')
            ->assertSee('Estado financiero')
            ->assertSee('Añadir conductor')
            ->assertSee('Bandeja de validación Yape');
    }

    public function test_finance_routes_require_an_admin_session(): void
    {
        $this->flushSession();
        $this->get(route('finanzas.padron'))->assertRedirect(route('login'));

        $this->withSession(['usuario' => 'conductor'])
            ->get(route('finanzas.padron'))
            ->assertForbidden();
    }

    public function test_driver_can_submit_the_end_of_month_payment_with_a_private_receipt(): void
    {
        Carbon::setTestNow(now()->endOfMonth()->setTime(12, 0));
        $conductor = $this->conductor();
        $conductor->forceFill(['created_at' => today()->startOfMonth()])->save();
        config([
            'services.supabase.url' => 'https://kaluz-test.supabase.co',
            'services.supabase.service_role_key' => 'test-service-role-key',
            'services.supabase.storage_bucket' => 'comprobantes-yape',
        ]);
        Http::fake();

        $this->withSession(['conductor_id' => $conductor->id])
            ->post(route('finanzas.pagos.store'), [
                'metodo_pago' => 'yape',
                'comprobante' => UploadedFile::fake()->image('captura.png'),
            ])
            ->assertRedirect(route('despacho.pwa.bloqueado'))
            ->assertSessionHas('estado');

        $pago = PagoComision::query()->firstOrFail();
        self::assertSame('pendiente', $pago->estado);
        self::assertSame('yape', $pago->metodo_pago);
        self::assertSame((float) today()->daysInMonth, (float) $pago->monto);
        self::assertSame(today()->daysInMonth, $pago->dias_pagados);
        self::assertStringStartsWith('conductores/'.$conductor->id.'/', $pago->comprobante_url);
        Http::assertSent(fn ($request): bool => $request->method() === 'POST'
            && str_contains($request->url(), '/storage/v1/object/comprobantes-yape/')
            && $request->hasHeader('Authorization', 'Bearer test-service-role-key')
            && $request->body() !== '');
    }

    public function test_driver_cannot_register_monthly_payment_before_month_end(): void
    {
        Carbon::setTestNow(now()->startOfMonth()->addDay()->setTime(12, 0));
        $conductor = $this->conductor();
        config([
            'services.supabase.url' => 'https://kaluz-test.supabase.co',
            'services.supabase.service_role_key' => 'test-service-role-key',
            'services.supabase.storage_bucket' => 'comprobantes-yape',
        ]);
        Http::fake();

        $this->withSession(['conductor_id' => $conductor->id])
            ->post(route('finanzas.pagos.store'), [
                'metodo_pago' => 'yape',
                'comprobante' => UploadedFile::fake()->image('captura.png'),
            ])
            ->assertSessionHasErrors('pago');

        $this->assertDatabaseCount('pagos_comisiones', 0);
        Http::assertNothingSent();
    }

    public function test_admin_opens_private_receipt_through_a_short_lived_signed_url(): void
    {
        $conductor = $this->conductor();
        $pago = PagoComision::query()->create([
            'conductor_id' => $conductor->id,
            'monto' => 1.00,
            'dias_pagados' => 1,
            'metodo_pago' => 'yape',
            'comprobante_url' => 'conductores/'.$conductor->id.'/2026/10/captura.png',
            'estado' => 'pendiente',
        ]);
        config([
            'services.supabase.url' => 'https://kaluz-test.supabase.co',
            'services.supabase.service_role_key' => 'test-service-role-key',
            'services.supabase.storage_bucket' => 'comprobantes-yape',
        ]);
        Http::fake([
            '*' => Http::response(['signedURL' => '/object/sign/comprobantes-yape/recibo.png?token=temporary'], 200),
        ]);

        $this->get(route('finanzas.yape.comprobante', $pago))
            ->assertRedirect('https://kaluz-test.supabase.co/storage/v1/object/sign/comprobantes-yape/recibo.png?token=temporary');

        Http::assertSent(fn ($request): bool => $request->method() === 'POST'
            && str_contains($request->url(), '/storage/v1/object/sign/')
            && $request['expiresIn'] === 300);
    }

    public function test_blocked_driver_sees_monthly_amount_and_only_configured_payment_destinations(): void
    {
        Carbon::setTestNow(now()->endOfMonth()->setTime(12, 0));
        $conductor = $this->conductor();
        $conductor->forceFill(['created_at' => today()->startOfMonth()])->save();
        config([
            'finanzas.yape_destino' => '955111222',
            'finanzas.plin_destino' => null,
        ]);

        $this->withSession(['conductor_id' => $conductor->id])
            ->get(route('despacho.pwa.bloqueado'))
            ->assertOk()
            ->assertSee('955111222')
            ->assertSee('Enviar comprobante por S/ '.number_format((float) today()->daysInMonth, 2))
            ->assertDontSee('964-000-000');
    }

    public function test_blocked_driver_cannot_submit_when_no_official_payment_destination_is_configured(): void
    {
        Carbon::setTestNow(now()->endOfMonth()->setTime(12, 0));
        $conductor = $this->conductor();
        $conductor->forceFill(['created_at' => today()->startOfMonth()])->save();
        config([
            'finanzas.yape_destino' => null,
            'finanzas.plin_destino' => null,
        ]);

        $this->withSession(['conductor_id' => $conductor->id])
            ->get(route('despacho.pwa.bloqueado'))
            ->assertOk()
            ->assertSee('El destino oficial de Yape o Plin aún no está configurado.')
            ->assertDontSee('Enviar comprobante por S/');
    }

    public function test_driver_id_in_query_string_cannot_bypass_the_financial_lock(): void
    {
        $conductor = $this->conductor();

        $this->get(route('despacho.pwa', ['conductor_id' => $conductor->id]))
            ->assertRedirect(route('despacho.pwa.bloqueado'));
    }

    public function test_login_links_a_driver_account_to_the_conductor_with_the_same_dni(): void
    {
        $conductor = $this->conductor(['dni' => '87654321']);
        config([
            'services.supabase.url' => 'https://kaluz-test.supabase.co',
            'services.supabase.key' => 'test-anon-key',
        ]);
        Http::fake([
            '*' => Http::response([[
                'usuario' => '87654321',
                'nombre' => 'Conductor de prueba',
            ]], 200),
        ]);

        $this->post('/login', [
            'usuario' => '87654321',
            'password' => 'test-password',
        ])
            ->assertRedirect(route('despacho.pwa'))
            ->assertSessionHas('conductor_id', $conductor->id);

        Http::assertSent(fn ($request): bool => str_contains($request->url(), '/rest/v1/usuarios'));
    }

    public function test_driver_session_cannot_open_the_admin_dashboard(): void
    {
        $conductor = $this->conductor(['dni' => '87654321']);

        $this->withSession([
            'usuario' => '87654321',
            'conductor_id' => $conductor->id,
        ])
            ->get(route('dashboard'))
            ->assertRedirect(route('despacho.pwa'));
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
        self::assertSame('al_dia', $conductor->estado_comision);
        self::assertTrue($conductor->habilitado);
    }

    public function test_monthly_fee_is_calculated_from_registration_and_does_not_carry_forward(): void
    {
        $inicioMes = now()->startOfMonth()->startOfDay();
        Carbon::setTestNow($inicioMes->copy()->addDays(9)->setTime(12, 0));
        $conductor = $this->conductor([
            'estado_comision' => 'moroso',
            'habilitado' => false,
            'fecha_vencimiento_permiso' => today()->addYear(),
        ]);
        $conductor->forceFill(['created_at' => $inicioMes])->save();

        self::assertSame(10.0, $conductor->deuda_comision);
        self::assertFalse($conductor->mora_comision);
        self::assertTrue(app(ValidacionCandadoService::class)->verificarEstadoConductor($conductor->id)['habilitado']);
        self::assertTrue(Conductor::habilitados()->whereKey($conductor->id)->exists());

        Carbon::setTestNow(now()->endOfMonth()->setTime(12, 0));
        $conductor->refresh();

        self::assertSame((float) today()->daysInMonth, $conductor->deuda_comision);
        self::assertTrue($conductor->mora_comision);
        self::assertFalse(app(ValidacionCandadoService::class)->verificarEstadoConductor($conductor->id)['habilitado']);
        self::assertFalse(Conductor::habilitados()->whereKey($conductor->id)->exists());

        Carbon::setTestNow(now()->addDay()->startOfDay());
        $conductor->refresh();
        self::assertSame(1.0, $conductor->deuda_comision);
        self::assertFalse($conductor->mora_comision);
        self::assertTrue(app(ValidacionCandadoService::class)->verificarEstadoConductor($conductor->id)['habilitado']);
        self::assertTrue(Conductor::habilitados()->whereKey($conductor->id)->exists());
    }

    public function test_admin_can_register_mora_only_after_month_end_due_date(): void
    {
        Carbon::setTestNow(now()->endOfMonth()->setTime(12, 0));
        $conductor = $this->conductor();
        $conductor->forceFill(['created_at' => today()->startOfMonth()])->save();

        $this->post(route('finanzas.padron.conductores.mora', $conductor))
            ->assertRedirect(route('finanzas.padron'))
            ->assertSessionHas('estado');

        self::assertSame('moroso', $conductor->fresh()->estado_comision);
        self::assertFalse($conductor->fresh()->habilitado);
        self::assertFalse(app(ValidacionCandadoService::class)->verificarEstadoConductor($conductor->id)['habilitado']);
    }

    public function test_admin_can_register_a_quota_alert_without_sending_a_message(): void
    {
        $conductor = $this->conductor();

        $this->post(route('finanzas.padron.conductores.alerta-cuota', $conductor), [
            'mensaje' => 'Recordatorio de cuota.',
        ])->assertRedirect(route('finanzas.padron'))
            ->assertSessionHas('estado', 'Alerta registrada en el panel. No se envió ningún mensaje al afiliado.');

        $this->assertDatabaseHas('alertas_financieras', [
            'conductor_id' => $conductor->id,
            'tipo' => 'cuota_mensual',
            'mensaje' => 'Recordatorio de cuota.',
        ]);
    }

    public function test_alert_action_is_visible_but_explains_when_its_table_is_not_migrated(): void
    {
        $this->conductor();
        Schema::drop('alertas_financieras');

        $this->get(route('finanzas.padron'))
            ->assertOk()
            ->assertSee('Requiere migración de alertas')
            ->assertSee('alertas_financieras');
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
