<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PagoComision extends Model
{
    use HasFactory;

    protected $table = 'pagos_comisiones';

    public const CREATED_AT = 'fecha_registro';

    public const UPDATED_AT = null;

    protected $fillable = [
        'conductor_id',
        'monto',
        'dias_pagados',
        'metodo_pago', // yape, plin, efectivo
        'comprobante_url',
        'estado', // pendiente, aprobado, rechazado
        'fecha_aprobacion',
    ];

    protected $casts = [
        'monto' => 'decimal:2',
        'dias_pagados' => 'integer',
        'fecha_registro' => 'datetime',
        'fecha_aprobacion' => 'datetime',
    ];

    public function conductor(): BelongsTo
    {
        return $this->belongsTo(Conductor::class, 'conductor_id');
    }
}
