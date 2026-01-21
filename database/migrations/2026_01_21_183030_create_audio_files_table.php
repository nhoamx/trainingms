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
        Schema::create('audio_files', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('question_type'); // general, conditional, traumatic, cisneros, referencia_i
            $table->integer('question_index'); // 0-based index
            $table->string('original_filename');
            $table->string('storage_path');
            $table->string('file_extension', 10); // mp3, m4a, wav, ogg
            $table->string('mime_type');
            $table->unsignedBigInteger('file_size');
            $table->foreignUuid('uploaded_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();

            // Ensure unique combination of question_type and question_index
            $table->unique(['question_type', 'question_index']);
            $table->index(['question_type', 'question_index']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audio_files');
    }
};
