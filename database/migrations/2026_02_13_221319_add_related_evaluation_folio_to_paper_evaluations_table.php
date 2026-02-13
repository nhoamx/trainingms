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
            $table->string('related_evaluation_folio', 20)->nullable()->after('folio');
            $table->index('related_evaluation_folio');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('paper_evaluations', function (Blueprint $table) {
            $table->dropIndex(['related_evaluation_folio']);
            $table->dropColumn('related_evaluation_folio');
        });
    }
};
