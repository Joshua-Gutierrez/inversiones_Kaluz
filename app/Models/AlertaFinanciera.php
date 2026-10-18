<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AlertaFinanciera extends Model
{
    protected $table = 'alertas_financieras';

    protected $fillable = [
        'conductor_id',
        'tipo',
        'mensaje',
        'estado',
    ];

    public function conductor(): BelongsTo
    {
        return $this->belongsTo(Conductor::class, 'conductor_id');
    }
}
