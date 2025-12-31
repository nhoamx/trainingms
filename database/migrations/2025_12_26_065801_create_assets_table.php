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
        Schema::create('assets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained()->cascadeOnDelete();
            $table->string('consecutive_number');
            $table->string('serial_number')->nullable();
            $table->string('asset_type')->nullable();
            $table->string('asset_category')->nullable();
            $table->string('location')->nullable();
            $table->string('capacity')->nullable();
            $table->string('fire_class')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'asset_type']);
            $table->unique(['organization_id', 'serial_number']);
            $table->unique(['organization_id', 'consecutive_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assets');
    }
};
