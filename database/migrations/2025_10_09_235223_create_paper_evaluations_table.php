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
        Schema::create('paper_evaluations', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // Folio structure: 2 digits (evaluation_type_code) + 3 digits (organization_code) + 4 digits (personal_folio)
            $table->string('folio', 9)->unique()->index();
            $table->string('evaluation_type_code', 2)->index(); // 01, 02, 03, 04
            $table->string('organization_code', 3)->index();
            $table->string('personal_folio', 4);

            // Relationships
            $table->foreignUuid('organization_id')->nullable()->constrained()->nullOnDelete();

            // Evaluation metadata
            $table->enum('evaluation_type', ['referencia_i', 'referencia_iii', 'referencia_v', 'cisneros'])->index();
            $table->enum('source', ['online', 'paper'])->default('paper')->index();
            $table->enum('processing_status', ['pending', 'processing', 'completed', 'failed'])->default('pending')->index();

            // Paper-specific data
            $table->string('pdf_file_path')->nullable();
            $table->timestamp('processed_at')->nullable();

            // Structured data storage
            $table->json('demographic_data')->nullable(); // Store all demographic info from Referencia V
            $table->json('referencia_i_answers')->nullable(); // Guide I PTSD questions
            $table->json('referencia_iii_answers')->nullable(); // Reference III workplace questions
            $table->json('referencia_iii_conditional')->nullable(); // Conditional questions (customer service, management)
            $table->json('cisneros_answers')->nullable(); // Cisneros scale mobbing questions
            $table->json('raw_data')->nullable(); // Original JSON from OCR for auditing

            // Error handling
            $table->text('processing_error')->nullable();
            $table->integer('retry_count')->default(0);

            $table->timestamps();
            $table->softDeletes();

            // Composite indexes for common queries
            $table->index(['organization_id', 'evaluation_type', 'created_at'], 'idx_org_type_date');
            $table->index(['evaluation_type', 'source', 'processing_status'], 'idx_type_source_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('paper_evaluations');
    }
};
