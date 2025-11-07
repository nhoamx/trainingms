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
        Schema::create('report_generations', function (Blueprint $table) {
            $table->id();
            $table->uuid('user_id');
            $table->uuid('organization_id')->nullable();
            $table->string('report_type'); // 'demographic', 'diagnostic', 'executive'
            $table->string('format')->default('word'); // 'word' (future: 'excel')
            $table->string('status')->default('pending'); // 'pending', 'processing', 'completed', 'failed'
            $table->string('file_path')->nullable(); // Path to generated file
            $table->string('original_filename')->nullable(); // Original filename for download
            $table->text('error_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            // Foreign keys
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('organization_id')->references('id')->on('organizations')->onDelete('cascade');

            // Indexes
            $table->index('status');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('report_generations');
    }
};
