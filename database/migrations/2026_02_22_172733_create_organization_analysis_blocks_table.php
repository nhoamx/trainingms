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
        Schema::create('organization_analysis_blocks', function (Blueprint $table) {
            $table->id();
            $table->uuid('organization_id');
            $table->string('instrument_type', 32);
            $table->string('title')->nullable();
            $table->longText('content_html');
            $table->unsignedInteger('sort_order')->default(0);
            $table->uuid('updated_by')->nullable();
            $table->timestamps();

            $table->foreign('organization_id')->references('id')->on('organizations')->cascadeOnDelete();
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
            $table->index(['organization_id', 'instrument_type', 'sort_order'], 'org_analysis_blocks_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('organization_analysis_blocks');
    }
};
