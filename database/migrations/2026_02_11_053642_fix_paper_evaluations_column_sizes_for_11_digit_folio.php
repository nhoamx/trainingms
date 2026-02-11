<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Fix personal_folio column size to support 5 digits for 11-digit folio format.
     *
     * The 11-digit folio format is: [TT][OO][CC][PPPPP]
     * - TT = Evaluation type (2 digits)
     * - OO = Organization (2 digits)
     * - CC = Work center (2 digits)
     * - PPPPP = Personal folio (5 digits) <- Was incorrectly set to 4 digits
     *
     * This migration corrects the personal_folio column to allow 5 characters.
     */
    public function up(): void
    {
        Schema::table('paper_evaluations', function (Blueprint $table) {
            // Update personal_folio to 5 characters to support new 11-digit format
            $table->string('personal_folio', 5)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('paper_evaluations', function (Blueprint $table) {
            // Revert to 4 characters (legacy format)
            $table->string('personal_folio', 4)->change();
        });
    }
};
