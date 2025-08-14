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
        Schema::create('custom_fields', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('type', ['text', 'number', 'textarea']);
            $table->unsignedBigInteger('quiz_id');
            $table->timestamps();

            // Foreign key constraint
            $table->foreign('quiz_id')->references('id')->on('quizzes')->onDelete('cascade');
            
            // Index for performance
            $table->index('quiz_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('custom_fields');
    }
};
