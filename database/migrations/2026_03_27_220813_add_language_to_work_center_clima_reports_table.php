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
        Schema::table('work_center_clima_reports', function (Blueprint $table) {
            $table->string('language', 5)->default('es')->after('title');
            $table->index(['work_center_id', 'language', 'is_active'], 'wcr_wc_lang_active_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('work_center_clima_reports', function (Blueprint $table) {
            $table->dropIndex('wcr_wc_lang_active_idx');
            $table->dropColumn('language');
        });
    }
};
