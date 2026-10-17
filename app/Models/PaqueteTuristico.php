<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaqueteTuristico extends Model
{
    use HasFactory;

    protected $table = 'paquetes_turisticos';

    protected $fillable = [
        'nombre',
        'descripcion',
        'destino_principal',
        'duracion_horas',
        'precio_taxi',
        'precio_combi',
        'capacidad_maxima',
        'requiere_flota_propia',
        'imagen_url',
        'activo',
    ];

    protected $casts = [
        'duracion_horas' => 'integer',
        'precio_taxi' => 'decimal:2',
        'precio_combi' => 'decimal:2',
        'capacidad_maxima' => 'integer',
        'requiere_flota_propia' => 'boolean',
        'activo' => 'boolean',
    ];

    public function solicitudesViaje(): HasMany
    {
        return $this->hasMany(SolicitudViaje::class, 'paquete_turistico_id');
    }
}
