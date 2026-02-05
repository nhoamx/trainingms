<?php

namespace Database\Seeders;

use App\Enums\WorkCenterType;
use App\Models\Organization;
use App\Models\PaperEvaluation;
use App\Models\Quiz;
use App\Models\WorkCenter;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MigrateToWorkCentersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🚀 Starting Work Centers migration...');
        $this->command->newLine();

        DB::transaction(function () {
            $organizations = Organization::whereNull('deleted_at')->get();
            $this->command->info("📋 Found {$organizations->count()} organizations");
            $this->command->newLine();

            foreach ($organizations as $org) {
                $this->command->info("Processing: {$org->name}");

                // Create primary work center for organization
                $workCenter = WorkCenter::create([
                    'organization_id' => $org->id,
                    'code' => '0001',
                    'name' => $org->name,
                    'type' => WorkCenterType::Headquarters->value,
                    'is_primary' => true,
                    // Copy fiscal data
                    'legal_name' => $org->razon_social,
                    'tax_id' => $org->rfc,
                    'employer_registration' => $org->registro_patronal,
                    // Copy address
                    'street_address' => $org->calle_numero,
                    'neighborhood' => $org->colonia,
                    'postal_code' => $org->codigo_postal,
                    'municipality' => $org->municipio,
                    'state' => $org->estado,
                    // Copy contact
                    'phone' => $org->contacto_movil,
                    'email' => $org->contacto_email,
                ]);

                $this->command->line("  ✓ Created work center: {$workCenter->name} (code: {$workCenter->code})");

                // Migrate quizzes
                $quizzesCount = Quiz::where('organization_id', $org->id)
                    ->whereNull('work_center_id')
                    ->update(['work_center_id' => $workCenter->id]);

                if ($quizzesCount > 0) {
                    $this->command->line("  ✓ Migrated {$quizzesCount} quizzes");
                }

                // Migrate paper evaluations
                $evaluationsCount = PaperEvaluation::where('organization_id', $org->id)
                    ->whereNull('work_center_id')
                    ->update(['work_center_id' => $workCenter->id]);

                if ($evaluationsCount > 0) {
                    $this->command->line("  ✓ Migrated {$evaluationsCount} paper evaluations");
                }

                $this->command->newLine();
            }
        });

        $this->command->newLine();
        $this->command->info('✅ Migration completed successfully!');
        $this->command->newLine();

        // Validation summary
        $this->validateMigration();
    }

    /**
     * Validate migration results
     */
    protected function validateMigration(): void
    {
        $this->command->info('📊 Validation Summary:');
        $this->command->newLine();

        $totalWorkCenters = WorkCenter::count();
        $totalOrgs = Organization::whereNull('deleted_at')->count();
        $quizzesWithWorkCenter = Quiz::whereNotNull('work_center_id')->count();
        $quizzesWithoutWorkCenter = Quiz::whereNull('work_center_id')->count();
        $evalsWithWorkCenter = PaperEvaluation::whereNotNull('work_center_id')->count();
        $evalsWithoutWorkCenter = PaperEvaluation::whereNull('work_center_id')->count();

        $this->command->table(
            ['Metric', 'Count'],
            [
                ['Organizations', $totalOrgs],
                ['Work Centers Created', $totalWorkCenters],
                ['Quizzes with Work Center', $quizzesWithWorkCenter],
                ['Quizzes without Work Center', $quizzesWithoutWorkCenter],
                ['Evaluations with Work Center', $evalsWithWorkCenter],
                ['Evaluations without Work Center', $evalsWithoutWorkCenter],
            ]
        );

        if ($quizzesWithoutWorkCenter > 0 || $evalsWithoutWorkCenter > 0) {
            $this->command->warn('⚠️  Some records still without work center assignment');
        } else {
            $this->command->info('✅ All records successfully migrated!');
        }
    }
}
