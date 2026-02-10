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
        Schema::table('notifications', function (Blueprint $table) {
            // Drop the existing morph columns
            $table->dropMorphs('notifiable');
            
            // Recreate with UUID support
            $table->uuidMorphs('notifiable');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            // Drop UUID morph columns
            $table->dropMorphs('notifiable');
            
            // Recreate with regular integer IDs
            $table->morphs('notifiable');
        });
    }
};
