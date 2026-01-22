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
        Schema::table('paper_evaluations', function (Blueprint $table) {
            $table->string('source', 20)->default('paper')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('paper_evaluations', function (Blueprint $table) {
            $table->enum('source', ['online', 'paper'])->default('paper')->change();
        });
    }
};
