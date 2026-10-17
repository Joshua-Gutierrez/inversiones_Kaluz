<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PagoComision extends Model
{
    use HasFactory;

    protected $table = 'pagos_comision';

    protected $fillable = [
        'conductor_id',
        'monto',
        'metodo_pago', // yape, plin, efectivo
        'codigo_operacion',
        'comprobante_url',
        'estado', // pendiente, validado, rechazado
        'fecha_validacion',
    ];

    protected $casts = [
        'monto' => 'decimal:2',
        'fecha_validacion' => 'datetime',
    ];

    public function conductor(): BelongsTo
    {
        return $this->belongsTo(Conductor::class, 'conductor_id');
    }
}
