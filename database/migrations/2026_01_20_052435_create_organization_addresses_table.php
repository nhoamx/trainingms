<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('organization_addresses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')
                ->constrained('organizations')
                ->onDelete('cascade');
            $table->string('type')->default('fiscal'); // fiscal, fisica
            $table->string('calle_numero')->nullable();
            $table->string('colonia')->nullable();
            $table->string('codigo_postal', 10)->nullable();
            $table->string('municipio')->nullable();
            $table->string('estado')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->timestamps();

            $table->index(['organization_id', 'is_primary']);
            $table->index(['organization_id', 'type']);
        });

        // Migrate existing address data from organizations table
        DB::table('organizations')->whereNotNull('calle_numero')->orWhereNotNull('colonia')->orWhereNotNull('codigo_postal')->orWhereNotNull('municipio')->orWhereNotNull('estado')->get()->each(function ($org) {
            // Only create address if at least one field has data
            if ($org->calle_numero || $org->colonia || $org->codigo_postal || $org->municipio || $org->estado) {
                DB::table('organization_addresses')->insert([
                    'id' => (string) Str::uuid(),
                    'organization_id' => $org->id,
                    'type' => 'fiscal',
                    'calle_numero' => $org->calle_numero,
                    'colonia' => $org->colonia,
                    'codigo_postal' => $org->codigo_postal,
                    'municipio' => $org->municipio,
                    'estado' => $org->estado,
                    'is_primary' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('organization_addresses');
    }
};
