<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class Conductor extends Model
{
    use HasFactory;

    protected $table = 'conductores';

    protected $fillable = [
        'nombre',
        'dni',
        'telefono_whatsapp',
        'placa',
        'modelo_vehiculo',
        'tipo_flota',
        'estado_comision',
        'fecha_vencimiento_comision',
        'fecha_vencimiento_permiso',
        'habilitado',
        'lat_actual',
        'lng_actual',
    ];

    protected $casts = [
        'habilitado' => 'boolean',
        'fecha_vencimiento_comision' => 'datetime',
        'fecha_vencimiento_permiso' => 'date',
        'lat_actual' => 'float',
        'lng_actual' => 'float',
    ];

    /**
     * The monthly balance blocks access only on the final calendar day of its period.
     */
    public function scopeHabilitados(Builder $query): Builder
    {
        $habilitables = static::query()
            ->whereNotNull('fecha_vencimiento_permiso')
            ->whereDate('fecha_vencimiento_permiso', '>=', today()->toDateString());

        if (today()->isLastOfMonth()) {
            $conductores = $habilitables->get(['id', 'created_at']);
            $pagosAprobados = PagoComision::query()
                ->whereIn('conductor_id', $conductores->pluck('id'))
                ->where('estado', 'aprobado')
                ->whereBetween('fecha_registro', [today()->startOfMonth(), today()->endOfDay()])
                ->selectRaw('conductor_id, COALESCE(SUM(dias_pagados), 0) as dias_pagados')
                ->groupBy('conductor_id')
                ->pluck('dias_pagados', 'conductor_id');

            $idsHabilitados = $conductores
                ->filter(fn (Conductor $conductor): bool => (int) ($pagosAprobados[$conductor->id] ?? 0)
                    >= $conductor->diasComisionAcumulados())
                ->pluck('id');

            return $query->whereIn($query->getModel()->qualifyColumn('id'), $idsHabilitados);
        }

        return $query->whereIn(
            $query->getModel()->qualifyColumn('id'),
            $habilitables->select('id')
        );
    }

    public function scopeBloqueados(Builder $query): Builder
    {
        return $query->whereNotIn(
            $query->getModel()->qualifyColumn('id'),
            static::habilitados()->select('conductores.id')
        );
    }

    /**
     * Scope: Filtra los vehículos que pertenecen a la flota propia de Kaluz (ej. Combis / Turismo).
     */
    public function scopeFlotaPropia(Builder $query): Builder
    {
        return $query->whereIn('tipo_flota', ['propia_auto', 'propia_combi']);
    }

    public function diasComisionAcumulados(?Carbon $fecha = null): int
    {
        $fecha = ($fecha ?? today())->copy()->startOfDay();
        $inicioPeriodo = $fecha->copy()->startOfMonth();
        $fechaAlta = $this->created_at
            ? $this->created_at->copy()->startOfDay()
            : $inicioPeriodo;
        $inicioCobro = $fechaAlta->greaterThan($inicioPeriodo) ? $fechaAlta : $inicioPeriodo;

        if ($inicioCobro->greaterThan($fecha)) {
            return 0;
        }

        return (int) $inicioCobro->diffInDays($fecha) + 1;
    }

    public function getDeudaComisionAttribute(): float
    {
        $fechaInicio = today()->startOfMonth();
        $fechaFin = today()->endOfDay();
        $atributos = $this->getAttributes();

        $diasPagados = array_key_exists('dias_comision_pagados_mes', $atributos)
            ? (int) ($atributos['dias_comision_pagados_mes'] ?? 0)
            : (int) $this->pagosComision()
                ->where('estado', 'aprobado')
                ->whereBetween('fecha_registro', [$fechaInicio, $fechaFin])
                ->sum('dias_pagados');

        return (float) max(0, $this->diasComisionAcumulados() - $diasPagados);
    }

    public function getMoraComisionAttribute(): bool
    {
        return today()->isLastOfMonth() && $this->deuda_comision > 0;
    }

    public function getEstadoComisionActualAttribute(): string
    {
        return $this->mora_comision ? 'moroso' : 'al_dia';
    }

    public function getAccesoHabilitadoAttribute(): bool
    {
        return $this->estado_comision_actual === 'al_dia'
            && $this->fecha_vencimiento_permiso
            && ! $this->fecha_vencimiento_permiso->isBefore(today());
    }

    public function getTelefonoAttribute(): ?string
    {
        return $this->telefono_whatsapp;
    }

    public function getEsFlotaPropiaAttribute(): bool
    {
        return in_array($this->tipo_flota, ['propia_auto', 'propia_combi'], true);
    }

    public function ofertas(): HasMany
    {
        return $this->hasMany(OfertaConductor::class, 'conductor_id');
    }

    public function pagosComision(): HasMany
    {
        return $this->hasMany(PagoComision::class, 'conductor_id');
    }

    public function viajesAsignados(): HasMany
    {
        return $this->hasMany(SolicitudViaje::class, 'conductor_id');
    }
}
