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
        Schema::table('folio_batches', function (Blueprint $table) {
            // Cambiar de enum a string para soportar nuevos tipos vía constantes PHP
            $table->string('type', 20)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('folio_batches', function (Blueprint $table) {
            // Revertir a enum original
            $table->enum('type', ['presencial', 'en_linea'])->change();
        });
    }
};
