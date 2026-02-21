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
        Schema::table('work_centers', function (Blueprint $table) {
            $table->string('constitutive_act_submitted_path')->nullable()->after('sample_justification');
            $table->timestamp('constitutive_act_submitted_at')->nullable()->after('constitutive_act_submitted_path');
            $table->string('constitutive_act_admin_path')->nullable()->after('constitutive_act_submitted_at');
            $table->timestamp('constitutive_act_admin_at')->nullable()->after('constitutive_act_admin_path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('work_centers', function (Blueprint $table) {
            $table->dropColumn([
                'constitutive_act_submitted_path',
                'constitutive_act_submitted_at',
                'constitutive_act_admin_path',
                'constitutive_act_admin_at',
            ]);
        });
    }
};
