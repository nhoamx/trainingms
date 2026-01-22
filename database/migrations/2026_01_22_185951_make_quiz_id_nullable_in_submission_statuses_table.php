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
        Schema::table('submission_statuses', function (Blueprint $table) {
            // Drop foreign key first
            $table->dropForeign(['quiz_id']);

            // Make quiz_id nullable
            $table->unsignedBigInteger('quiz_id')->nullable()->change();

            // Re-add foreign key constraint (nullable)
            $table->foreign('quiz_id')->references('id')->on('quizzes')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('submission_statuses', function (Blueprint $table) {
            // Drop foreign key
            $table->dropForeign(['quiz_id']);

            // Make quiz_id not nullable again
            $table->unsignedBigInteger('quiz_id')->nullable(false)->change();

            // Re-add foreign key constraint
            $table->foreign('quiz_id')->references('id')->on('quizzes')->onDelete('cascade');
        });
    }
};
