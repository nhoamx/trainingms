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
        Schema::create('work_center_clima_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('work_center_id')->constrained('work_centers')->cascadeOnDelete();
            $table->string('section_key', 100);
            $table->longText('content')->nullable();
            $table->string('status', 20)->default('draft');
            $table->foreignUuid('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->unique(['work_center_id', 'section_key']);
            $table->index(['work_center_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('work_center_clima_sections');
    }
};
