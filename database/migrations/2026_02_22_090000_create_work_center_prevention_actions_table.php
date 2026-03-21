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
        Schema::create('work_center_prevention_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('work_center_id')->constrained()->onDelete('cascade');
            $table->string('instrument_type', 32);
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('responsible')->nullable();
            $table->string('status', 32)->default('pendiente');
            $table->date('due_date')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->foreignUuid('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['work_center_id', 'instrument_type'], 'wc_prev_actions_wc_instr_idx');
            $table->index(['work_center_id', 'sort_order'], 'wc_prev_actions_wc_sort_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('work_center_prevention_actions');
    }
};
