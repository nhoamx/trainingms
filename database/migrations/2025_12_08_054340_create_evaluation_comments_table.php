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
        Schema::create('evaluation_comments', function (Blueprint $table) {
            $table->id();
            $table->uuid('paper_evaluation_id');
            $table->string('factor');
            $table->text('comment');
            $table->timestamps();

            $table->foreign('paper_evaluation_id')
                ->references('id')
                ->on('paper_evaluations')
                ->onDelete('cascade');

            $table->index(['paper_evaluation_id', 'factor']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('evaluation_comments');
    }
};
