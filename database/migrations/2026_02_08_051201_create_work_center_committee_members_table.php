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
        Schema::create('work_center_committee_members', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('work_center_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('department_area');
            $table->string('position');
            $table->string('factor');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('work_center_committee_members');
    }
};
