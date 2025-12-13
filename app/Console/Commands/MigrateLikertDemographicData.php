<?php

namespace App\Console\Commands;

use App\Models\DemographicData;
use App\Models\PaperEvaluation;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MigrateLikertDemographicData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'likert:migrate-demographics 
                            {--organization= : Filter by organization code (e.g., 031)}
                            {--dry-run : Show what would be migrated without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate demographic data from likert_answers JSON to demographic_data table';

    /**
     * Gender mapping from JSON to database format
     *
     * @var array<string, string>
     */
    protected array $genderMapping = [
        'masculino' => 'Masculino',
        'femenino' => 'Femenino',
    ];

    /**
     * Shift mapping from JSON to database format
     *
     * @var array<string, string>
     */
    protected array $shiftMapping = [
        'matutino' => 'Matutino',
        'vespertino' => 'Vespertino',
        'nocturno' => 'Nocturno',
        'matutino_vespertino' => 'Matutino-Vespertino',
        'vespertino_nocturno' => 'Vespertino-Nocturno',
        'rotativo' => 'Rotativo',
    ];

    /**
     * Contract type mapping from JSON to database format
     *
     * @var array<string, string>
     */
    protected array $contractTypeMapping = [
        'tiempo_indeterminado' => 'Tiempo Indeterminado',
        'por_tiempo_determinado' => 'Por Tiempo Determinado',
        'por_obra_o_proyecto' => 'Por Obra o Proyecto',
        'honorarios' => 'Honorarios',
        'confianza' => 'Confianza',
    ];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $organizationCode = $this->option('organization');

        if ($dryRun) {
            $this->info('🔍 DRY RUN MODE - No changes will be made');
        }

        $this->info('Searching for Likert evaluations with missing demographic data...');

        // Build query to find evaluations with data in JSON but not in demographic_data table
        $query = PaperEvaluation::query()
            ->where('evaluation_type', 'likert')
            ->where('processing_status', 'completed')
            ->whereNotNull('likert_answers')
            ->where(function ($q) {
                $q->whereDoesntHave('demographicData')
                    ->orWhereHas('demographicData', function ($subQ) {
                        $subQ->whereNull('gender');
                    });
            });

        if ($organizationCode) {
            $query->where('organization_code', $organizationCode);
            $this->info("Filtering by organization code: {$organizationCode}");
        }

        $evaluations = $query->with('demographicData')->get();

        if ($evaluations->isEmpty()) {
            $this->info('✅ No evaluations found that need migration.');

            return self::SUCCESS;
        }

        $this->info("Found {$evaluations->count()} evaluations to process.");

        $created = 0;
        $updated = 0;
        $skipped = 0;
        $errors = 0;

        $this->withProgressBar($evaluations, function ($evaluation) use ($dryRun, &$created, &$updated, &$skipped, &$errors) {
            try {
                $likertAnswers = $evaluation->likert_answers ?? [];

                // Extract demographic data from JSON
                $gender = $this->formatGender($likertAnswers['genero'] ?? null);
                $shift = $this->formatShift($likertAnswers['turno'] ?? null);
                $contractType = $this->formatContractType($likertAnswers['tipo_contrato'] ?? null);
                $position = $likertAnswers['puestos'] ?? null;
                $department = $likertAnswers['areas'] ?? null;

                // Skip if no gender data in JSON
                if (! $gender) {
                    $skipped++;

                    return;
                }

                if ($dryRun) {
                    if ($evaluation->demographicData) {
                        $updated++;
                    } else {
                        $created++;
                    }

                    return;
                }

                DB::transaction(function () use ($evaluation, $gender, $shift, $contractType, $position, $department, &$created, &$updated) {
                    if ($evaluation->demographicData) {
                        // Update existing record
                        $evaluation->demographicData->update([
                            'gender' => $gender,
                            'work_schedule' => $shift,
                            'contract_type' => $contractType,
                            'position' => $position,
                            'department' => $department,
                        ]);
                        $updated++;
                    } else {
                        // Create new record
                        DemographicData::create([
                            'paper_evaluation_id' => $evaluation->id,
                            'gender' => $gender,
                            'work_schedule' => $shift,
                            'contract_type' => $contractType,
                            'position' => $position,
                            'department' => $department,
                        ]);
                        $created++;
                    }
                });
            } catch (\Exception $e) {
                $errors++;
                $this->newLine();
                $this->error("Error processing evaluation {$evaluation->id}: {$e->getMessage()}");
            }
        });

        $this->newLine(2);
        $this->info('Migration Summary:');
        $this->table(
            ['Action', 'Count'],
            [
                ['Created', $created],
                ['Updated', $updated],
                ['Skipped (no gender in JSON)', $skipped],
                ['Errors', $errors],
                ['Total Processed', $evaluations->count()],
            ]
        );

        if ($dryRun) {
            $this->newLine();
            $this->warn('This was a dry run. Run without --dry-run to apply changes.');
        }

        return $errors > 0 ? self::FAILURE : self::SUCCESS;
    }

    protected function formatGender(?string $gender): ?string
    {
        if (! $gender || $gender === 'null') {
            return null;
        }

        return $this->genderMapping[strtolower($gender)] ?? ucfirst($gender);
    }

    protected function formatShift(?string $shift): ?string
    {
        if (! $shift || $shift === 'null') {
            return null;
        }

        return $this->shiftMapping[strtolower($shift)] ?? ucfirst(str_replace('_', ' ', $shift));
    }

    protected function formatContractType(?string $contractType): ?string
    {
        if (! $contractType || $contractType === 'null') {
            return null;
        }

        return $this->contractTypeMapping[strtolower($contractType)] ?? ucfirst(str_replace('_', ' ', $contractType));
    }
}
