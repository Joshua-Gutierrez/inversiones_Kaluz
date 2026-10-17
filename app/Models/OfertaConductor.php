<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OfertaConductor extends Model
{
    use HasFactory;

    protected $table = 'ofertas_conductor';

    protected $fillable = [
        'solicitud_viaje_id',
        'conductor_id',
        'monto_oferta',
        'tiempo_estimado_min',
        'estado', // enviada, aceptada, rechazada, expirada
    ];

    protected $casts = [
        'monto_oferta' => 'decimal:2',
        'tiempo_estimado_min' => 'integer',
    ];

    public function solicitudViaje(): BelongsTo
    {
        return $this->belongsTo(SolicitudViaje::class, 'solicitud_viaje_id');
    }

    public function conductor(): BelongsTo
    {
        return $this->belongsTo(Conductor::class, 'conductor_id');
    }
}
