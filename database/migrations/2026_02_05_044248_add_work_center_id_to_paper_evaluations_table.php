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
            $table->foreignUuid('work_center_id')
                ->nullable()
                ->after('organization_id')
                ->constrained('work_centers')
                ->onDelete('set null');

            $table->index('work_center_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('paper_evaluations', function (Blueprint $table) {
            $table->dropForeign(['work_center_id']);
            $table->dropIndex(['work_center_id']);
            $table->dropColumn('work_center_id');
        });
    }
};
