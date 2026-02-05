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
        Schema::create('work_centers', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // Relación con organización
            $table->foreignUuid('organization_id')
                ->constrained('organizations')
                ->onDelete('cascade');

            // Work center identification
            $table->string('code', 4); // 4-digit unique code per organization
            $table->string('name'); // Work center name
            $table->string('type', 20)->default('plant');
            $table->boolean('is_primary')->default(false);

            // Specific fiscal data (nullable if inherited from organization)
            $table->string('legal_name')->nullable();
            $table->string('tax_id', 13)->nullable();
            $table->string('employer_registration')->nullable();

            // Physical address
            $table->string('street_address')->nullable();
            $table->string('neighborhood')->nullable();
            $table->string('postal_code', 10)->nullable();
            $table->string('municipality', 100)->nullable();
            $table->string('state', 100)->nullable();

            // Contact information
            $table->string('phone')->nullable();
            $table->string('email')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Índices
            $table->unique(['organization_id', 'code']); // Código único por organización
            $table->index(['organization_id', 'is_primary']);
            $table->index(['organization_id', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('work_centers');
    }
};
