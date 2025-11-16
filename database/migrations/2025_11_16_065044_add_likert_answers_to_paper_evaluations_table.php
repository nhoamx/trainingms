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
        Schema::table('paper_evaluations', function (Blueprint $table) {
            $table->json('likert_answers')->nullable()->after('cisneros_answers');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('paper_evaluations', function (Blueprint $table) {
            $table->dropColumn('likert_answers');
        });
    }
};
