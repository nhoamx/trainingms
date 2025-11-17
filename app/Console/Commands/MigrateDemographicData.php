<?php

namespace App\Console\Commands;

use App\Models\DemographicData;
use App\Models\PaperEvaluation;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class MigrateDemographicData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'demographic-data:migrate {--force : Skip confirmation and run immediately}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate demographic data from PaperEvaluation JSON field to DemographicData table';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting demographic data migration...');

        // Check if force flag is set
        if (! $this->option('force')) {
            $this->warn('This command will migrate demographic data from paper evaluations to the new DemographicData table.');
            $this->warn('This operation cannot be undone without restoring from backup.');

            if (! $this->confirm('Do you want to continue?')) {
                $this->info('Migration cancelled.');

                return 0;
            }
        }

        try {
            // Get all paper evaluations with referencia_v type (demographic data)
            $evaluations = PaperEvaluation::ofType('referencia_v')
                ->whereNotNull('demographic_data')
                ->get();

            $this->info("Found {$evaluations->count()} evaluations with demographic data");

            if ($evaluations->isEmpty()) {
                $this->info('No evaluations to migrate.');

                return 0;
            }

            $bar = $this->output->createProgressBar($evaluations->count());
            $bar->start();

            $migratedCount = 0;
            $skippedCount = 0;

            foreach ($evaluations as $evaluation) {
                try {
                    // Check if demographic data already exists
                    if ($evaluation->demographicData) {
                        $skippedCount++;
                        $bar->advance();

                        continue;
                    }

                    $demographicInfo = $this->extractDemographicInfo($evaluation->demographic_data);

                    // Create DemographicData record
                    DemographicData::create([
                        'paper_evaluation_id' => $evaluation->id,
                        'gender' => $demographicInfo['gender'] ?? null,
                        'age' => $demographicInfo['age'] ?? null,
                        'estado_civil' => $demographicInfo['estado_civil'] ?? null,
                        'nivel_estudios' => $demographicInfo['nivel_estudios'] ?? null,
                        'puesto' => $demographicInfo['puesto'] ?? null,
                        'area' => $demographicInfo['area'] ?? null,
                        'tipo_puesto' => $demographicInfo['tipo_puesto'] ?? null,
                        'tipo_contratacion' => $demographicInfo['tipo_contratacion'] ?? null,
                        'tipo_personal' => $demographicInfo['tipo_personal'] ?? null,
                        'tipo_jornada' => $demographicInfo['tipo_jornada'] ?? null,
                        'rotacion_turnos' => $demographicInfo['rotacion_turnos'] ?? null,
                        'tiempo_puesto_actual' => $demographicInfo['tiempo_puesto_actual'] ?? null,
                        'tiempo_experiencia_laboral' => $demographicInfo['tiempo_experiencia_laboral'] ?? null,
                        'extra_fields' => $demographicInfo['extra_fields'] ?? null,
                    ]);

                    $migratedCount++;
                } catch (\Exception $e) {
                    Log::error("Error migrating demographic data for evaluation {$evaluation->id}: {$e->getMessage()}");
                }

                $bar->advance();
            }

            $bar->finish();

            $this->newLine(2);
            $this->info('✓ Migration completed successfully!');
            $this->info("  - Migrated: {$migratedCount}");
            $this->info("  - Skipped: {$skippedCount}");

            return 0;

        } catch (\Exception $e) {
            Log::error('Demographic data migration failed: '.$e->getMessage());
            $this->error('Migration failed: '.$e->getMessage());

            return 1;
        }
    }

    /**
     * Extract demographic information from the JSON data
     */
    private function extractDemographicInfo(array $demographicData): array
    {
        $info = [
            'gender' => $demographicData['genero'] ?? null,
            'age' => $demographicData['edad'] ?? null,
            'estado_civil' => $demographicData['estado_civil'] ?? null,
            'nivel_estudios' => $demographicData['nivel_estudios'] ?? null,
            'puesto' => $demographicData['ocupacion_puesto'] ?? null,
            'area' => $demographicData['departamento_seccion_area'] ?? null,
            'tipo_puesto' => $demographicData['tipo_puesto'] ?? null,
            'tipo_contratacion' => $demographicData['tipo_contratacion'] ?? null,
            'tipo_personal' => $demographicData['tipo_personal'] ?? null,
            'tipo_jornada' => $demographicData['tipo_jornada'] ?? null,
            'rotacion_turnos' => $demographicData['rotacion_turnos'] ?? null,
            'tiempo_puesto_actual' => $demographicData['tiempo_puesto_actual'] ?? null,
            'tiempo_experiencia_laboral' => $demographicData['tiempo_experiencia_laboral'] ?? null,
        ];

        // Store any extra fields in the extra_fields JSON column
        $knownFields = [
            'genero', 'edad', 'estado_civil', 'nivel_estudios', 'ocupacion_puesto',
            'departamento_seccion_area', 'tipo_puesto', 'tipo_contratacion', 'tipo_personal',
            'tipo_jornada', 'rotacion_turnos', 'tiempo_puesto_actual', 'tiempo_experiencia_laboral',
        ];

        $extraFields = [];
        foreach ($demographicData as $key => $value) {
            if (! in_array($key, $knownFields)) {
                $extraFields[$key] = $value;
            }
        }

        if (! empty($extraFields)) {
            $info['extra_fields'] = $extraFields;
        }

        return $info;
    }
}
