<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Modify the ENUM to add 'likert' value
        DB::statement("ALTER TABLE paper_evaluations MODIFY COLUMN evaluation_type ENUM('referencia_i','referencia_iii','referencia_v','cisneros','likert') NOT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove 'likert' from ENUM
        DB::statement("ALTER TABLE paper_evaluations MODIFY COLUMN evaluation_type ENUM('referencia_i','referencia_iii','referencia_v','cisneros') NOT NULL");
    }
};
