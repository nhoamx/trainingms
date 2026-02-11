<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Update folio column to support 11-digit format: [TT][OO][CC][PPPPP]
     *
     * NOTE: This migration allows dual format support:
     * - Existing records keep their 9-digit format: [TT][OOO][PPPP]
     * - New records use the 11-digit format: [TT][OO][CC][PPPPP]
     */
    public function up(): void
    {
        Schema::table('paper_evaluations', function (Blueprint $table) {
            // Expand folio column to support both 9-digit (legacy) and 11-digit (new) formats
            $table->string('folio', 11)->change();

            // Update organization_code to 2 characters (was 3 for legacy, now 2 for new format)
            // Still supports legacy 3-char codes by left-padding
            $table->string('organization_code', 3)->change();

            // Update personal_folio to 5 characters (was 4 for legacy, now 5 for new format)
            $table->string('personal_folio', 5)->change();

            // Add work_center_code column (nullable for backward compatibility with legacy 9-digit folios)
            if (! Schema::hasColumn('paper_evaluations', 'work_center_code')) {
                $table->string('work_center_code', 2)->nullable()->after('organization_code')->index();
            }
        });

        // Optionally update work_center_code for existing records that have work_center_id
        // but keep their original 9-digit folios intact
        DB::statement('
            UPDATE paper_evaluations pe
            LEFT JOIN work_centers wc ON pe.work_center_id = wc.id
            SET pe.work_center_code = LPAD(RIGHT(wc.code, 2), 2, "0")
            WHERE pe.work_center_id IS NOT NULL 
            AND wc.code IS NOT NULL
            AND pe.work_center_code IS NULL
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('paper_evaluations', function (Blueprint $table) {
            // Revert to old format
            $table->string('folio', 9)->change();
            $table->string('organization_code', 3)->change();
            $table->string('personal_folio', 4)->change();

            // Remove work_center_code if it was added
            if (Schema::hasColumn('paper_evaluations', 'work_center_code')) {
                $table->dropColumn('work_center_code');
            }
        });
    }
};
