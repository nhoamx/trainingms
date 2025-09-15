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
        Schema::create('submission_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('folio');
            $table->string('personal_id');
            $table->uuid('organization_id');
            $table->unsignedBigInteger('quiz_id');
            $table->enum('status', ['pending', 'processing', 'completed', 'failed', 'retrying'])
                  ->default('pending');
            $table->json('data_snapshot'); // Store the original submission data
            $table->text('error_message')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->integer('retry_count')->default(0);
            $table->string('session_id')->nullable(); // To track user session
            $table->timestamps();

            // Indexes for performance
            $table->index(['folio', 'personal_id'], 'idx_folio_personal');
            $table->index(['organization_id', 'quiz_id'], 'idx_org_quiz');
            $table->index(['status', 'created_at'], 'idx_status_created');
            $table->index('session_id', 'idx_session');

            // Foreign key constraints
            $table->foreign('organization_id')->references('id')->on('organizations')->onDelete('cascade');
            $table->foreign('quiz_id')->references('id')->on('quizzes')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('submission_statuses');
    }
};
