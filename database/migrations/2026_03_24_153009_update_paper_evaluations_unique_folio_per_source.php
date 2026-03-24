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
            $table->dropUnique(['folio']);
            $table->unique(['folio', 'source'], 'paper_evaluations_folio_source_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('paper_evaluations', function (Blueprint $table) {
            $table->dropUnique('paper_evaluations_folio_source_unique');
            $table->unique('folio');
        });
    }
};
