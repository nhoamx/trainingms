<?php

use App\Models\WorkCenter;
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
        // Agregar columna emails JSON
        Schema::table('work_centers', function (Blueprint $table) {
            $table->json('emails')->nullable()->after('phone');
        });

        // Migrar datos existentes del campo email al nuevo campo emails
        WorkCenter::whereNotNull('email')->each(function ($workCenter) {
            $email = trim($workCenter->email);
            if (! empty($email)) {
                // Convertir el email único a array
                $workCenter->emails = [$email];
                $workCenter->save();
            }
        });

        // Eliminar la columna email antigua
        Schema::table('work_centers', function (Blueprint $table) {
            $table->dropColumn('email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Restaurar columna email
        Schema::table('work_centers', function (Blueprint $table) {
            $table->string('email')->nullable()->after('phone');
        });

        // Migrar de vuelta el primer email del array
        WorkCenter::whereNotNull('emails')->each(function ($workCenter) {
            if (is_array($workCenter->emails) && count($workCenter->emails) > 0) {
                $workCenter->email = $workCenter->emails[0];
                $workCenter->save();
            }
        });

        // Eliminar columna emails
        Schema::table('work_centers', function (Blueprint $table) {
            $table->dropColumn('emails');
        });
    }
};
