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
        Schema::table('questions', function (Blueprint $table) {
            $table->string('personal_id')->after('evaluation_id')->nullable();

            // Clave foránea comentada ya que no se necesita relación directa por ahora
            // Asume que tienes una tabla 'personal' con una columna 'id' de tipo UUID
            // Ajusta 'personal' al nombre real de tu tabla si es diferente
            // $table->foreign('personal_id')->references('id')->on('personal')->onDelete('cascade');
            // Comentado temporalmente por si la tabla 'personal' no existe aún o tiene otro nombre
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            // Clave foránea comentada
            // $table->dropForeign(['personal_id']);
            $table->dropColumn('personal_id');
        });
    }
};
