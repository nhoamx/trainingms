<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('demographic_data', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('paper_evaluation_id')->constrained('paper_evaluations')->onDelete('cascade');
            $table->string('gender')->nullable();
            $table->string('age')->nullable();
            $table->string('estado_civil')->nullable();
            $table->string('nivel_estudios')->nullable();
            $table->string('puesto')->nullable();
            $table->string('area')->nullable();
            $table->string('tipo_puesto')->nullable();
            $table->string('tipo_contratacion')->nullable();
            $table->string('tipo_personal')->nullable();
            $table->string('tipo_jornada')->nullable();
            $table->string('rotacion_turnos')->nullable();
            $table->string('tiempo_puesto_actual')->nullable();
            $table->string('tiempo_experiencia_laboral')->nullable();
            $table->json('extra_fields')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('demographic_data');
    }
};
