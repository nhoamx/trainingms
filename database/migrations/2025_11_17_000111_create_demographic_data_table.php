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
        Schema::create('demographic_data', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('paper_evaluation_id')->constrained('paper_evaluations')->onDelete('cascade');
            $table->string('gender')->nullable();
            $table->string('age')->nullable();
            $table->string('marital_status')->nullable();
            $table->string('education_level')->nullable();
            $table->string('position')->nullable();
            $table->string('department')->nullable();
            $table->string('position_type')->nullable();
            $table->string('contract_type')->nullable();
            $table->string('personnel_type')->nullable();
            $table->string('work_schedule')->nullable();
            $table->string('shift_rotation')->nullable();
            $table->string('time_in_current_position')->nullable();
            $table->string('work_experience')->nullable();
            $table->json('extra_fields')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('demographic_data');
    }
};
