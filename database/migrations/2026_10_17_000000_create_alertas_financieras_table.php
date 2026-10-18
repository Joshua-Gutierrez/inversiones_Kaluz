<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alertas_financieras', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('conductor_id')->constrained('conductores')->cascadeOnDelete();
            $table->string('tipo', 30);
            $table->text('mensaje');
            $table->string('estado', 20)->default('registrada');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alertas_financieras');
    }
};
