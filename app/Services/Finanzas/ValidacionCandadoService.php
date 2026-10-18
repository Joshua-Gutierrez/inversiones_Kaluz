<?php

namespace App\Services\Finanzas;

use App\Models\Conductor;
use App\Models\PagoComision;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * 👨‍💻 ZONA DEV 2 - Validación del candado financiero mensual y documentario.
 */
class ValidacionCandadoService
{
    public function diasDisponiblesParaPago(Conductor $conductor): int
    {
        $inicioPeriodo = today()->startOfMonth();
        $finPeriodo = today()->endOfDay();
        $diasReservados = $conductor->pagosComision()
            ->whereIn('estado', ['pendiente', 'aprobado'])
            ->whereBetween('fecha_registro', [$inicioPeriodo, $finPeriodo])
            ->sum('dias_pagados');

        return max(0, $conductor->diasComisionAcumulados() - (int) $diasReservados);
    }

    public function pagoPendienteDelMes(Conductor $conductor): ?PagoComision
    {
        return $conductor->pagosComision()
            ->where('estado', 'pendiente')
            ->whereBetween('fecha_registro', [today()->startOfMonth(), today()->endOfDay()])
            ->latest('fecha_registro')
            ->first();
    }

    /**
     * La cuota acumula S/ 1 por día calendario y vence al cierre del mes.
     */
    public function verificarEstadoConductor(int $conductorId): array
    {
        $conductor = Conductor::query()->find($conductorId);

        if (! $conductor) {
            return [
                'habilitado' => false,
                'motivo' => 'No se encontró un conductor válido para verificar el candado.',
                'deuda' => 0.00,
            ];
        }

        $deuda = $conductor->deuda_comision;
        $permiso = $conductor->fecha_vencimiento_permiso;

        if ($conductor->mora_comision) {
            return [
                'habilitado' => false,
                'motivo' => 'Candado financiero activo: la cuota mensual vencida es de S/ '.number_format($deuda, 2).'.',
                'deuda' => $deuda,
            ];
        }

        if (! $permiso || $permiso->isBefore(today())) {
            return [
                'habilitado' => false,
                'motivo' => ! $permiso
                    ? 'Candado documentario: registra la fecha vigente de tu permiso municipal o SOAT.'
                    : 'Candado documentario: tu permiso o SOAT venció el '.$permiso->format('d/m/Y').'.',
                'deuda' => $deuda,
            ];
        }

        return ['habilitado' => true, 'motivo' => null, 'deuda' => $deuda];
    }

    public function aprobarPago(int $pagoId): bool
    {
        return DB::transaction(function () use ($pagoId): bool {
            $pago = PagoComision::query()->lockForUpdate()->findOrFail($pagoId);
            if ($pago->estado !== 'pendiente') {
                return false;
            }

            if (! is_string($pago->comprobante_url) || trim($pago->comprobante_url) === '') {
                throw ValidationException::withMessages([
                    'pago' => 'No se puede aprobar un pago sin comprobante registrado.',
                ]);
            }

            $conductor = Conductor::query()->lockForUpdate()->findOrFail($pago->conductor_id);
            $fechaPago = $pago->fecha_registro ?? now();
            $diasPagados = (int) $pago->dias_pagados;
            if ($diasPagados < 1 || (int) round((float) $pago->monto * 100) !== $diasPagados * 100) {
                throw ValidationException::withMessages([
                    'pago' => 'El monto debe coincidir exactamente con los días calendario declarados a razón de S/ 1 por día.',
                ]);
            }

            $periodoInicio = $fechaPago->copy()->startOfMonth();
            $periodoFin = $fechaPago->copy()->endOfMonth()->endOfDay();
            $diasYaPagados = (int) PagoComision::query()
                ->where('conductor_id', $conductor->id)
                ->where('estado', 'aprobado')
                ->whereBetween('fecha_registro', [$periodoInicio, $periodoFin])
                ->sum('dias_pagados');
            $diasAcumuladosHastaPago = $conductor->diasComisionAcumulados($fechaPago);

            if ($diasYaPagados + $diasPagados > $diasAcumuladosHastaPago) {
                throw ValidationException::withMessages([
                    'pago' => 'Los días pagados superan la comisión calendario acumulada del conductor en este mes.',
                ]);
            }

            $pago->estado = 'aprobado';
            $pago->fecha_aprobacion = now();
            $pago->save();

            if ($periodoInicio->isSameMonth(now())) {
                $permisoVigente = $conductor->fecha_vencimiento_permiso
                    && ! $conductor->fecha_vencimiento_permiso->isBefore(today());
                $mora = $conductor->mora_comision;

                $conductor->forceFill([
                    'fecha_vencimiento_comision' => now()->endOfMonth(),
                    'estado_comision' => $mora ? 'moroso' : 'al_dia',
                    'habilitado' => $permisoVigente && ! $mora,
                ])->save();
            }

            return true;
        });
    }

    public function rechazarPago(int $pagoId): bool
    {
        return DB::transaction(function () use ($pagoId): bool {
            $pago = PagoComision::query()->lockForUpdate()->findOrFail($pagoId);
            if ($pago->estado !== 'pendiente') {
                return false;
            }

            $pago->estado = 'rechazado';
            $pago->fecha_aprobacion = now();
            $pago->save();

            return true;
        });
    }
}
