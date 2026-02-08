<?php

use App\Models\Organization;
use App\Models\WorkCenter;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // PASO 1: Agregar campos faltantes a work_centers si no existen
        Schema::table('work_centers', function (Blueprint $table) {
            // Contact fields
            if (! Schema::hasColumn('work_centers', 'contact_name')) {
                $table->string('contact_name')->nullable()->after('phone');
                $table->string('contact_position')->nullable()->after('contact_name');
                $table->string('contact_email')->nullable()->after('contact_position');
                $table->string('contact_phone')->nullable()->after('contact_email');

                $table->string('responsible_name')->nullable()->after('contact_phone');
                $table->string('responsible_position')->nullable()->after('responsible_name');
                $table->string('responsible_email')->nullable()->after('responsible_position');
                $table->string('responsible_phone')->nullable()->after('responsible_email');
            }

            // Worker census
            if (! Schema::hasColumn('work_centers', 'total_workers')) {
                $table->integer('total_workers')->nullable()->after('responsible_phone');
                $table->integer('total_men')->nullable()->after('total_workers');
                $table->integer('total_women')->nullable()->after('total_men');
            }

            // Sample applied
            if (! Schema::hasColumn('work_centers', 'sample_applied')) {
                $table->integer('sample_applied')->nullable()->after('total_women');
                $table->integer('sample_men')->nullable()->after('sample_applied');
                $table->integer('sample_women')->nullable()->after('sample_men');
            }

            // Follow-up committee
            if (! Schema::hasColumn('work_centers', 'committee_members')) {
                $table->integer('committee_members')->nullable()->after('sample_women');
                $table->integer('committee_men')->nullable()->after('committee_members');
                $table->integer('committee_women')->nullable()->after('committee_men');
            }

            // Dates and justification
            if (! Schema::hasColumn('work_centers', 'application_date')) {
                $table->date('application_date')->nullable()->after('committee_women');
                $table->text('sample_justification')->nullable()->after('application_date');
            }
        });

        // PASO 2: Migrar datos de organizations → work_centers primarios
        $this->migrateDataToWorkCenters();

        // PASO 3: NO ELIMINAR COLUMNAS DE ORGANIZATIONS AÚN (backward compatibility)
        // Mantenerlas por 1-2 meses para rollback si es necesario
    }

    /**
     * Migrate organization data to their primary work centers
     */
    protected function migrateDataToWorkCenters(): void
    {
        $organizations = Organization::with('workCenters')->get();

        foreach ($organizations as $org) {
            $primaryCenter = $org->workCenters()->where('is_primary', true)->first();

            if (! $primaryCenter) {
                // Si no tiene centro primario, crearlo
                $primaryCenter = WorkCenter::create([
                    'organization_id' => $org->id,
                    'code' => '0001',
                    'name' => $org->name,
                    'type' => 'headquarters',
                    'is_primary' => true,
                ]);
            }

            // Migrar datos desde organization al work center
            $primaryCenter->update([
                // Address
                'street_address' => $org->calle_numero,
                'neighborhood' => $org->colonia,
                'postal_code' => $org->codigo_postal,
                'municipality' => $org->municipio,
                'state' => $org->estado,

                // Contact
                'contact_name' => $org->contacto_nombre,
                'contact_position' => $org->contacto_puesto,
                'contact_email' => $org->contacto_email,
                'contact_phone' => $org->contacto_movil,

                // Responsible
                'responsible_name' => $org->responsable_nombre,
                'responsible_position' => $org->responsable_puesto,
                'responsible_email' => $org->responsable_email,
                'responsible_phone' => $org->responsable_movil,

                // Census
                'total_workers' => $org->total_trabajadores,
                'total_men' => $org->total_hombres,
                'total_women' => $org->total_mujeres,

                // Sample
                'sample_applied' => $org->muestra_aplicada,
                'sample_men' => $org->muestra_hombres,
                'sample_women' => $org->muestra_mujeres,

                // Committee
                'committee_members' => $org->comite_integrantes,
                'committee_men' => $org->comite_hombres,
                'committee_women' => $org->comite_mujeres,

                // Dates
                'application_date' => $org->fecha_aplicacion,
                'sample_justification' => $org->justificacion_muestra,
            ]);
        }

        Log::info('Migrated organization data to work centers', [
            'organizations_processed' => $organizations->count(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Rollback: Remove added columns
        Schema::table('work_centers', function (Blueprint $table) {
            $table->dropColumn([
                'contact_name', 'contact_position', 'contact_email', 'contact_phone',
                'responsible_name', 'responsible_position', 'responsible_email', 'responsible_phone',
                'total_workers', 'total_men', 'total_women',
                'sample_applied', 'sample_men', 'sample_women',
                'committee_members', 'committee_men', 'committee_women',
                'application_date', 'sample_justification',
            ]);
        });
    }
};
