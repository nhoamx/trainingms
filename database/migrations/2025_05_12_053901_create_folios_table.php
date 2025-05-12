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
        Schema::create('folios', function (Blueprint $table) {
            $table->id()->comment('Identificador único del folio');
            $table->foreignId('folio_batch_id')->comment('Lote al que pertenece este folio')->constrained()->onDelete('cascade');
            $table->string('folio_number')->comment('Número de folio formateado con ceros a la izquierda (ej: 0001)');
            $table->integer('numeric_value')->comment('Valor numérico del folio para ordenamiento');
            $table->boolean('used')->default(false)->comment('Indica si el folio ya ha sido utilizado');
            $table->timestamp('used_at')->nullable()->comment('Fecha y hora en que se utilizó el folio');
            $table->timestamps();
            
            $table->unique(['folio_batch_id', 'folio_number'])->comment('Garantiza que no haya folios duplicados dentro de un mismo lote');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('folios');
    }
};
