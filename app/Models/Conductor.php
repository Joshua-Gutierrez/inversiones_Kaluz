<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Conductor extends Model
{
    use HasFactory;

    protected $table = 'conductores';

    protected $fillable = [
        'nombre',
        'dni',
        'telefono',
        'placa',
        'modelo_vehiculo',
        'tipo_vehiculo',
        'es_flota_propia',
        'deuda_comision',
        'fecha_vencimiento_permiso',
        'estado',
        'latitud_actual',
        'longitud_actual',
    ];

    protected $casts = [
        'es_flota_propia' => 'boolean',
        'deuda_comision' => 'decimal:2',
        'fecha_vencimiento_permiso' => 'date',
        'latitud_actual' => 'float',
        'longitud_actual' => 'float',
    ];

    /**
     * Scope: Filtra conductores sin deuda de S/ 1.00 y con permiso vigente.
     */
    public function scopeHabilitados(Builder $query): Builder
    {
        return $query->where('estado', 'activo')
            ->where('deuda_comision', '<', 1.00)
            ->where(function (Builder $q) {
                $q->whereNull('fecha_vencimiento_permiso')
                  ->orWhereDate('fecha_vencimiento_permiso', '>=', now()->toDateString());
            });
    }

    /**
     * Scope: Filtra los vehículos que pertenecen a la flota propia de Kaluz (ej. Combis / Turismo).
     */
    public function scopeFlotaPropia(Builder $query): Builder
    {
        return $query->where('es_flota_propia', true);
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
