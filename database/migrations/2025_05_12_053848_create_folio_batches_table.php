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
        Schema::create('folio_batches', function (Blueprint $table) {
            $table->id()->comment('Identificador único del lote de folios');
            $table->foreignUuid('organization_id')->comment('Organización a la que pertenece este lote de folios')->constrained()->onDelete('cascade');
            $table->string('name')->comment('Nombre descriptivo del lote de folios, ej: Examen Mayo 2025');
            $table->string('description')->nullable()->comment('Descripción opcional del propósito del lote de folios');
            $table->integer('start_number')->comment('Número inicial del rango de folios');
            $table->integer('end_number')->comment('Número final del rango de folios');
            $table->integer('quantity')->comment('Cantidad total de folios reservados en este lote');
            $table->enum('type', ['presencial', 'en_linea'])->comment('Tipo de examen: presencial o en línea');
            $table->boolean('active')->default(true)->comment('Indica si el lote está activo o ha sido cancelado');
            $table->timestamps();
        }); 
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('folio_batches');
    }
};
