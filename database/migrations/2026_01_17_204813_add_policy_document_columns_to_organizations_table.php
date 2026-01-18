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
        Schema::table('organizations', function (Blueprint $table) {
            $table->string('policy_draft_path')->nullable()->after('logo');
            $table->string('policy_approved_path')->nullable()->after('policy_draft_path');
            $table->timestamp('policy_approved_at')->nullable()->after('policy_approved_path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->dropColumn(['policy_draft_path', 'policy_approved_path', 'policy_approved_at']);
        });
    }
};
