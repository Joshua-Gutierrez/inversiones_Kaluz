<?php

namespace App\Services\Finanzas;

use App\Models\Conductor;
use App\Models\PagoComision;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * 👨‍💻 ZONA DEV 2 - Servicio de Validación del Candado Financiero y Documentario (S/ 1.00)
 */
class ValidacionCandadoService
{
    /**
     * Verifica si un conductor puede recibir viajes o si está bloqueado por deuda de S/ 1.00 o permiso vencido.
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

        if ($conductor->estado_comision !== 'al_dia' || $deuda > 0) {
            return [
                'habilitado' => false,
                'motivo' => 'Candado financiero activo: tienes una comisión pendiente de S/ '.number_format($deuda, 2).'.',
                'deuda' => $deuda,
            ];
        }

        if (! $permiso || $permiso->isBefore(today()) || ! $conductor->fecha_vencimiento_comision
            || $conductor->fecha_vencimiento_comision->isPast() || ! $conductor->habilitado) {
            return [
                'habilitado' => false,
                'motivo' => ! $permiso || $permiso->isBefore(today())
                    ? ($permiso
                        ? 'Candado documentario: tu permiso o SOAT venció el '.$permiso->format('d/m/Y').'.'
                        : 'Candado documentario: registra la fecha vigente de tu permiso municipal o SOAT.')
                    : 'Candado financiero activo: tu comisión diaria está vencida o pendiente de validación.',
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

            $conductor = Conductor::query()->lockForUpdate()->findOrFail($pago->conductor_id);
            $diasPagados = max(1, (int) $pago->dias_pagados);
            if ((float) $pago->monto < $diasPagados) {
                throw ValidationException::withMessages([
                    'pago' => 'El monto del comprobante no cubre los días de comisión declarados.',
                ]);
            }
            $deudaRestante = max(0, $conductor->deuda_comision - $diasPagados);

            $pago->estado = 'aprobado';
            $pago->fecha_aprobacion = now();
            $pago->save();

            $fechaBase = $conductor->fecha_vencimiento_comision;
            $fechaBase = $deudaRestante === 0 || ! $fechaBase ? now() : $fechaBase;
            $permisoVigente = $conductor->fecha_vencimiento_permiso
                && ! $conductor->fecha_vencimiento_permiso->isBefore(today());
            $fechaVencimiento = $fechaBase->copy()->addDays($diasPagados);
            $estadoComision = $deudaRestante === 0 ? 'al_dia' : 'moroso';
            $habilitado = $deudaRestante === 0
                && $permisoVigente
                && $fechaVencimiento->isFuture();

            $conductor->forceFill([
                'fecha_vencimiento_comision' => $fechaVencimiento,
                'estado_comision' => $estadoComision,
                'habilitado' => $habilitado,
            ])->save();

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
