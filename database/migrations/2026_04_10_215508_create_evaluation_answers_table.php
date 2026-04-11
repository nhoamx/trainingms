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
        Schema::create('evaluation_answers', function (Blueprint $table) {
            $table->id();

            $table->foreignUuid('paper_evaluation_id')->constrained()->cascadeOnDelete();

            // Instrument identifier (referencia_i, referencia_iii, cisneros, likert) — string, no DB enum
            $table->string('instrument', 30)->index();

            // Question identifier within the instrument ("1", "pregunta_1", "65", etc.)
            $table->string('question_key', 20)->index();

            // Primary answer value (nullable — a null means the question was skipped/unanswered)
            $table->string('answer_value', 255)->nullable();

            // Additional structured metadata for complex answers (e.g. cisneros {persona, frecuencia})
            $table->json('answer_meta')->nullable();

            $table->timestamps();

            // Enforce one answer per question per evaluation
            $table->unique(['paper_evaluation_id', 'instrument', 'question_key'], 'uq_answer_per_question');

            // Composite index for analytic queries: filter by instrument + question + value
            $table->index(['instrument', 'question_key', 'answer_value'], 'idx_instrument_question_value');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('evaluation_answers');
    }
};
