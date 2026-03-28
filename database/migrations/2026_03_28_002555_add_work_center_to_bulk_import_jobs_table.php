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
        Schema::table('bulk_import_jobs', function (Blueprint $table) {
            $table->foreignUuid('work_center_id')->nullable()->constrained('work_centers')->nullOnDelete()->after('organization_id');
            $table->string('evaluation_type')->nullable()->after('source');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bulk_import_jobs', function (Blueprint $table) {
            $table->dropForeign(['work_center_id']);
            $table->dropColumn(['work_center_id', 'evaluation_type']);
        });
    }
};
