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
     * Scope: Filtra conductores sin deuda de S/ 1.00 y con permiso vigente.
     */
    public function scopeHabilitados(Builder $query): Builder
    {
        return $query->where('habilitado', true)
            ->where('estado_comision', 'al_dia')
            ->whereNotNull('fecha_vencimiento_permiso')
            ->whereDate('fecha_vencimiento_permiso', '>=', today()->toDateString())
            ->where('fecha_vencimiento_comision', '>=', now());
    }

    /**
     * Scope: Filtra los vehículos que pertenecen a la flota propia de Kaluz (ej. Combis / Turismo).
     */
    public function scopeFlotaPropia(Builder $query): Builder
    {
        return $query->whereIn('tipo_flota', ['propia_auto', 'propia_combi']);
    }

    public function getDeudaComisionAttribute(): float
    {
        $vencimiento = $this->fecha_vencimiento_comision;
        if ($this->estado_comision === 'moroso' || ! $vencimiento || $vencimiento->isPast()) {
            return $vencimiento && $vencimiento->isPast()
                ? (float) max(1, (int) $vencimiento->copy()->startOfDay()->diffInDays(today()))
                : 1.00;
        }

        return 0.00;
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
