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
        Schema::create('work_center_conclusions_files', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('work_center_id')->constrained('work_centers')->cascadeOnDelete();
            $table->tinyInteger('slot')->default(1)->comment('1, 2 or 3');
            $table->string('title')->nullable();
            $table->string('color', 20)->default('teal');
            $table->string('disk', 40)->default('public');
            $table->string('path');
            $table->string('original_filename');
            $table->unsignedBigInteger('file_size')->default(0);
            $table->string('mime_type', 100)->nullable();
            $table->boolean('is_published')->default(false);
            $table->foreignUuid('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['work_center_id', 'slot']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('work_center_conclusions_files');
    }
};
