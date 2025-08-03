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
        Schema::create('online_answers', function (Blueprint $table) {
            $table->id();
            $table->string('folio');
            $table->string('personal_id');
            $table->uuid('organization_id');
            $table->unsignedBigInteger('quiz_id');
            $table->string('question_key');
            $table->text('answer_value')->nullable();
            $table->enum('reference_guide', ['I', 'III', 'V', 'Cisneros']);
            $table->timestamps();

            // Indexes for performance optimization
            $table->index('folio', 'idx_folio');
            $table->index('personal_id', 'idx_personal_id');
            $table->index(['organization_id', 'quiz_id'], 'idx_organization_quiz');
            $table->index('reference_guide', 'idx_reference_guide');

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
        Schema::dropIfExists('online_answers');
    }
};
