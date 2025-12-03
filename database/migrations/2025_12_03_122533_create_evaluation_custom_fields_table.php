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
        Schema::create('evaluation_custom_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('paper_evaluation_id')->constrained()->cascadeOnDelete();
            $table->string('key', 100)->comment('Snake case key: supervisor, codigo_linea, etc.');
            $table->string('key_label', 150)->comment('Original label from Excel header');
            $table->string('value', 500)->nullable();
            $table->timestamps();

            // Index for efficient filtering
            $table->index(['paper_evaluation_id', 'key']);
            $table->index(['key', 'value']);

            // Unique constraint to prevent duplicate keys per evaluation
            $table->unique(['paper_evaluation_id', 'key']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('evaluation_custom_fields');
    }
};
