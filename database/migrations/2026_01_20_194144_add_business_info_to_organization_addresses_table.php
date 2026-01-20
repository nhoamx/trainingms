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
        Schema::table('organization_addresses', function (Blueprint $table) {
            $table->string('nombre_comercial')->nullable()->after('organization_id');
            $table->string('razon_social')->nullable()->after('nombre_comercial');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('organization_addresses', function (Blueprint $table) {
            $table->dropColumn(['nombre_comercial', 'razon_social']);
        });
    }
};
