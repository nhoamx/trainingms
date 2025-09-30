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
        Schema::create('questions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('evaluation_id')->nullable()->constrained('evaluations')->onDelete('cascade');
            $table->string('question');
            $table->string('answer')->nullable();
            $table->foreignUuid('domain_id')->nullable()->constrained('domains')->onDelete('set null');
            $table->foreignUuid('dimension_id')->nullable()->constrained('dimensions')->onDelete('set null');
            $table->foreignUuid('category_id')->nullable()->constrained('categories')->onDelete('set null');
            $table->string('value')->nullable();
            $table->string('reference_guide');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('questions');
    }
};
