<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SolicitudViaje extends Model
{
    use HasFactory;

    protected $table = 'solicitudes_viaje';

    protected $fillable = [
        'cliente_nombre',
        'cliente_telefono',
        'origen_direccion',
        'origen_lat',
        'origen_lng',
        'destino_direccion',
        'destino_lat',
        'destino_lng',
        'precio_sugerido',
        'precio_acordado',
        'estado', // pendiente, en_subasta, asignado, en_curso, completado, cancelado
        'canal_origen', // whatsapp, web, torre_control
        'conductor_id',
        'paquete_turistico_id',
    ];

    protected $casts = [
        'origen_lat' => 'float',
        'origen_lng' => 'float',
        'destino_lat' => 'float',
        'destino_lng' => 'float',
        'precio_sugerido' => 'decimal:2',
        'precio_acordado' => 'decimal:2',
    ];

    public function conductor(): BelongsTo
    {
        return $this->belongsTo(Conductor::class, 'conductor_id');
    }

    public function ofertas(): HasMany
    {
        return $this->hasMany(OfertaConductor::class, 'solicitud_viaje_id');
    }

    public function paqueteTuristico(): BelongsTo
    {
        return $this->belongsTo(PaqueteTuristico::class, 'paquete_turistico_id');
    }
}
