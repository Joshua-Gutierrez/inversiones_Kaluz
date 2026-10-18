<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('solicitudes_viaje', function (Blueprint $table) {
            $table->integer('paquete_turistico_id')->nullable()->index();
            $table->dateTime('fecha_hora_servicio')->nullable()->index();
            $table->foreign('paquete_turistico_id')
                ->references('id')
                ->on('paquetes_turisticos')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('solicitudes_viaje', function (Blueprint $table) {
            $table->dropForeign(['paquete_turistico_id']);
            $table->dropIndex(['paquete_turistico_id']);
            $table->dropIndex(['fecha_hora_servicio']);
            $table->dropColumn(['paquete_turistico_id', 'fecha_hora_servicio']);
        });
    }
};
