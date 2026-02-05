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
        Schema::table('work_centers', function (Blueprint $table) {
            $table->string('slug')->nullable()->after('name');
            $table->unique(['organization_id', 'slug'], 'work_centers_org_slug_unique');
            $table->index(['organization_id', 'slug']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('work_centers', function (Blueprint $table) {
            $table->dropIndex(['organization_id', 'slug']);
            $table->dropUnique('work_centers_org_slug_unique');
            $table->dropColumn('slug');
        });
    }
};
