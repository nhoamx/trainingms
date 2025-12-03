<?php

namespace App\Console\Commands;

use App\Models\DemographicData;
use App\Models\Organization;
use App\Models\PaperEvaluation;
use App\Services\LikertScoreService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class ReprocessOrganizationEvaluations extends Command
{
    protected $signature = 'evaluations:reprocess 
        {organization : ID of the organization to reprocess}
        {--type= : Filter by evaluation type (likert, referencia_i, referencia_iii, referencia_v, cisneros)}
        {--climate= : Filter by climate level(s). Use comma-separated values: ta,da,d,td (Totalmente Acuerdo, De Acuerdo, Desacuerdo, Totalmente Desacuerdo)}
        {--dry-run : Show what would be processed without making changes}
        {--limit= : Limit the number of evaluations to process}';

    protected $description = 'Reprocess paper evaluations for an organization using existing aligned images';

    protected string $containerName = 'training-and-ms';

    public function handle(): int
    {
        $organizationId = $this->argument('organization');
        $evaluationType = $this->option('type');
        $climateLevels = $this->option('climate');
        $dryRun = $this->option('dry-run');
        $limit = $this->option('limit');

        // Find organization
        $organization = Organization::find($organizationId);

        if (! $organization) {
            $this->error("Organization with ID {$organizationId} not found.");

            return self::FAILURE;
        }

        $this->info("Organization: {$organization->name} ({$organization->folio_organization})");

        // Build query
        $query = PaperEvaluation::query()
            ->where('organization_id', $organizationId)
            ->where('source', 'paper')
            ->where('processing_status', 'completed');

        if ($evaluationType) {
            $query->where('evaluation_type', $evaluationType);
        }

        if ($limit) {
            $query->limit((int) $limit);
        }

        $evaluations = $query->get();

        if ($evaluations->isEmpty()) {
            $this->warn('No paper evaluations found for the specified criteria.');

            return self::SUCCESS;
        }

        // Filter by climate level if specified (only applies to likert evaluations)
        if ($climateLevels) {
            $evaluations = $this->filterByClimateLevel($evaluations, $climateLevels);

            if ($evaluations->isEmpty()) {
                $this->warn('No evaluations found matching the specified climate level(s).');

                return self::SUCCESS;
            }
        }

        $this->info("Found {$evaluations->count()} evaluations to reprocess.");

        if ($dryRun) {
            $this->info('DRY RUN - No changes will be made.');
            $likertService = app(LikertScoreService::class);

            $this->table(
                ['Folio', 'Type', 'Personal Folio', 'Climate', 'Score', 'Has Image'],
                $evaluations->map(function ($e) use ($likertService) {
                    $climate = '-';
                    $score = '-';

                    if ($e->evaluation_type === 'likert' && ! empty($e->likert_answers['questions'])) {
                        $scores = $likertService->calculateLikertScores($e);
                        $score = $scores['total_score'];
                        $climate = $this->getClimateShortCode($scores['interpretation']);
                    }

                    return [
                        $e->folio,
                        $e->evaluation_type,
                        $e->personal_folio,
                        $climate,
                        $score,
                        $this->hasStoredImage($e->folio) ? '✓' : '✗',
                    ];
                })->toArray()
            );

            return self::SUCCESS;
        }

        if (! $this->confirm('Do you want to continue with reprocessing?')) {
            $this->info('Operation cancelled.');

            return self::SUCCESS;
        }

        // Clear previous reprocessing log
        $this->clearReprocessingLog();

        // Check Docker container is running
        // if (! $this->checkDockerContainer()) {
        //     $this->error("Docker container '{$this->containerName}' is not running. Please start it first.");

        //     return self::FAILURE;
        // }

        $progressBar = $this->output->createProgressBar($evaluations->count());
        $progressBar->start();

        $successCount = 0;
        $failCount = 0;
        $skippedCount = 0;
        $totalTime = 0;
        $processingTimes = [];
        $startTotalTime = microtime(true);

        foreach ($evaluations as $evaluation) {
            try {
                $startTime = microtime(true);
                $result = $this->reprocessEvaluation($evaluation);
                $elapsedTime = microtime(true) - $startTime;

                if ($result === 'success') {
                    $successCount++;
                    $processingTimes[] = $elapsedTime;
                    $totalTime += $elapsedTime;
                } elseif ($result === 'skipped') {
                    $skippedCount++;
                } else {
                    $failCount++;
                    $totalTime += $elapsedTime;
                }
            } catch (\Exception $e) {
                $failCount++;
                Log::error("Error reprocessing evaluation {$evaluation->folio}: ".$e->getMessage());
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        // Copy log file from container
        $this->copyReprocessingLog();

        // Calculate timing stats
        $totalElapsed = microtime(true) - $startTotalTime;
        $avgTime = count($processingTimes) > 0 ? array_sum($processingTimes) / count($processingTimes) : 0;

        $this->info('Reprocessing completed!');
        $this->table(
            ['Status', 'Count'],
            [
                ['Success', $successCount],
                ['Failed', $failCount],
                ['Skipped (no image)', $skippedCount],
            ]
        );

        $this->newLine();
        $this->table(
            ['Metric', 'Value'],
            [
                ['Total time', sprintf('%.2f seconds', $totalElapsed)],
                ['Processing time (success only)', sprintf('%.2f seconds', $totalTime)],
                ['Average per image', sprintf('%.2f seconds', $avgTime)],
            ]
        );

        return self::SUCCESS;
    }

    /**
     * Filter evaluations by climate level interpretation
     *
     * @param  \Illuminate\Support\Collection  $evaluations
     * @param  string  $climateLevels  Comma-separated codes: ta,da,d,td
     */
    protected function filterByClimateLevel($evaluations, string $climateLevels): \Illuminate\Support\Collection
    {
        $likertService = app(LikertScoreService::class);

        // Parse climate level codes
        $codes = array_map('trim', explode(',', strtolower($climateLevels)));

        // Map codes to full interpretation names
        $codeToInterpretation = [
            'ta' => 'Totalmente de Acuerdo',
            'da' => 'De Acuerdo',
            'd' => 'Desacuerdo',
            'td' => 'Totalmente Desacuerdo',
        ];

        $allowedInterpretations = [];
        foreach ($codes as $code) {
            if (isset($codeToInterpretation[$code])) {
                $allowedInterpretations[] = $codeToInterpretation[$code];
            }
        }

        if (empty($allowedInterpretations)) {
            $this->warn('Invalid climate level codes. Use: ta (Totalmente Acuerdo), da (De Acuerdo), d (Desacuerdo), td (Totalmente Desacuerdo)');

            return collect();
        }

        $this->info('Filtering by climate levels: '.implode(', ', $allowedInterpretations));

        return $evaluations->filter(function ($evaluation) use ($likertService, $allowedInterpretations) {
            // Only filter likert evaluations
            if ($evaluation->evaluation_type !== 'likert') {
                return false;
            }

            // Skip if no likert answers
            if (empty($evaluation->likert_answers['questions'])) {
                return false;
            }

            $scores = $likertService->calculateLikertScores($evaluation);

            return in_array($scores['interpretation'], $allowedInterpretations);
        });
    }

    /**
     * Get short code for climate interpretation
     */
    protected function getClimateShortCode(?string $interpretation): string
    {
        if (! $interpretation) {
            return '-';
        }

        $map = [
            'Totalmente de Acuerdo' => 'TA',
            'De Acuerdo' => 'DA',
            'Desacuerdo' => 'D',
            'Totalmente Desacuerdo' => 'TD',
        ];

        return $map[$interpretation] ?? $interpretation;
    }

    protected function hasStoredImage(string $folio): bool
    {
        $imagePath = storage_path("app/public/folios/{$folio}.png");

        return File::exists($imagePath);
    }

    protected function clearReprocessingLog(): void
    {
        $logPath = base_path('docker/reprocessing.log');

        // Delete existing log file
        exec("docker exec {$this->containerName} rm -f /app/reprocessing.log");

        // Also delete local copy if exists
        if (File::exists($logPath)) {
            File::delete($logPath);
        }

        $this->info('Reprocessing log cleared.');
    }

    protected function copyReprocessingLog(): void
    {
        $logPath = base_path('docker/reprocessing.log');

        // Copy log from container to local
        exec("docker cp {$this->containerName}:/app/reprocessing.log ".escapeshellarg($logPath));

        if (File::exists($logPath)) {
            $this->info('Reprocessing log saved to: docker/reprocessing.log');
        }
    }

    protected function checkDockerContainer(): bool
    {
        $output = [];
        $returnCode = 0;

        exec("docker ps --filter name={$this->containerName} --format '{{.Names}}'", $output, $returnCode);

        return $returnCode === 0 && in_array($this->containerName, $output);
    }

    protected function reprocessEvaluation(PaperEvaluation $evaluation): string
    {
        $folio = $evaluation->folio;
        $imagePath = storage_path("app/public/folios/{$folio}.png");

        // Check if image exists
        if (! File::exists($imagePath)) {
            Log::warning("Image not found for folio: {$folio}");

            return 'skipped';
        }

        // Create backup of the original image
        $backupPath = storage_path("app/public/folios/{$folio}_v1.png");
        if (! File::exists($backupPath)) {
            File::copy($imagePath, $backupPath);
            Log::info("Backup created for folio: {$folio}");
        }

        // Copy image to Docker container
        $containerInputPath = "/app/reprocess_input/{$folio}.png";
        $containerOutputPath = "/app/reprocess_output/{$folio}.json";

        // Create directories in container
        exec("docker exec {$this->containerName} mkdir -p /app/reprocess_input /app/reprocess_output");

        // Copy image to container
        $copyCommand = 'docker cp '.escapeshellarg($imagePath)." {$this->containerName}:{$containerInputPath}";
        exec($copyCommand, $output, $returnCode);

        if ($returnCode !== 0) {
            Log::error("Failed to copy image to container for folio: {$folio}");

            return 'failed';
        }

        // Execute reprocessing script
        $execCommand = "docker exec {$this->containerName} python /app/reprocess_single.py {$containerInputPath} {$containerOutputPath} 2>&1";
        exec($execCommand, $execOutput, $execReturn);

        if ($execReturn !== 0) {
            Log::error("Reprocessing failed for folio: {$folio}", ['output' => $execOutput]);

            return 'failed';
        }

        // Copy JSON result back
        $localJsonPath = storage_path("app/temp/{$folio}_reprocess.json");
        File::ensureDirectoryExists(dirname($localJsonPath));

        $copyBackCommand = "docker cp {$this->containerName}:{$containerOutputPath} ".escapeshellarg($localJsonPath);
        exec($copyBackCommand, $output, $returnCode);

        if ($returnCode !== 0 || ! File::exists($localJsonPath)) {
            Log::error("Failed to retrieve JSON result for folio: {$folio}");

            return 'failed';
        }

        // Parse JSON and update database
        $jsonContent = File::get($localJsonPath);
        $result = json_decode($jsonContent, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::error("Invalid JSON result for folio: {$folio}");

            return 'failed';
        }

        // Update the evaluation
        $this->updateEvaluation($evaluation, $result);

        // Cleanup temp files
        File::delete($localJsonPath);

        // Cleanup container files
        exec("docker exec {$this->containerName} rm -f {$containerInputPath} {$containerOutputPath}");

        Log::info("Successfully reprocessed evaluation: {$folio}");

        return 'success';
    }

    protected function updateEvaluation(PaperEvaluation $evaluation, array $result): void
    {
        $answers = $result['answers'] ?? [];
        $templateType = $result['template_type'] ?? $evaluation->evaluation_type_code;

        $updateData = [
            'raw_data' => $answers,
            'processed_at' => now(),
        ];

        // Update specific fields based on template type
        switch ($templateType) {
            case '01':
                $updateData['referencia_i_answers'] = $answers;
                break;

            case '02':
                $updateData['referencia_iii_answers'] = $answers['referencia_iii'] ?? null;
                $updateData['referencia_iii_conditional'] = [
                    'customer_service' => [
                        'condition' => $answers['customer_service_conditional']['condition'] ?? null,
                        'questions' => $answers['customer_service_questions'] ?? null,
                    ],
                    'management' => [
                        'condition' => $answers['conditional_management']['condition'] ?? null,
                        'questions' => $answers['management_questions'] ?? null,
                    ],
                ];
                $updateData['citsats_s1'] = $answers['citsats_s1'] ?? null;
                break;

            case '03':
                $updateData['demographic_data'] = $answers;
                $this->updateDemographicData($evaluation, $answers);
                break;

            case '04':
                $updateData['cisneros_answers'] = $answers['cisneros'] ?? $answers;
                break;

            case '05':
            case '06':
                $questionsKey = $templateType === '06' ? 'likert_planta_3' : 'likert';
                $updateData['likert_answers'] = [
                    'questions' => $answers[$questionsKey] ?? null,
                    'genero' => $answers['genero'] ?? null,
                    'turno' => $answers['turno'] ?? null,
                    'tipo_contrato' => $answers['tipo_contrato'] ?? null,
                    'puestos' => $answers['puestos'] ?? null,
                    'areas' => $answers['areas'] ?? null,
                ];
                $this->updateDemographicData($evaluation, $updateData['likert_answers']);
                break;
        }

        $evaluation->update($updateData);
    }

    protected function updateDemographicData(PaperEvaluation $evaluation, array $data): void
    {
        try {
            $extractedData = $this->extractDemographicInfo($data);

            // Delete existing demographic data
            $evaluation->demographicData?->delete();

            // Create new demographic data record
            DemographicData::create([
                'paper_evaluation_id' => $evaluation->id,
                'gender' => $extractedData['gender'] ?? null,
                'age' => $extractedData['age'] ?? null,
                'marital_status' => $extractedData['marital_status'] ?? null,
                'education_level' => $extractedData['education_level'] ?? null,
                'position' => $extractedData['position'] ?? null,
                'department' => $extractedData['department'] ?? null,
                'position_type' => $extractedData['position_type'] ?? null,
                'contract_type' => $extractedData['contract_type'] ?? null,
                'personnel_type' => $extractedData['personnel_type'] ?? null,
                'work_schedule' => $extractedData['work_schedule'] ?? null,
                'shift_rotation' => $extractedData['shift_rotation'] ?? null,
                'time_in_current_position' => $extractedData['time_in_current_position'] ?? null,
                'work_experience' => $extractedData['work_experience'] ?? null,
                'extra_fields' => $extractedData['extra_fields'] ?? null,
            ]);

            Log::info("Demographic data updated for evaluation: {$evaluation->folio}");
        } catch (\Exception $e) {
            Log::error("Error updating demographic data for {$evaluation->folio}: ".$e->getMessage());
        }
    }

    /**
     * Extract demographic information from raw data
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function extractDemographicInfo(array $data): array
    {
        // Check if this is Likert data
        if (array_key_exists('questions', $data)) {
            return $this->extractFromLikert($data);
        }

        return $this->extractFromReferenciaV($data);
    }

    /**
     * Extract demographic info from Likert data
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function extractFromLikert(array $data): array
    {
        return [
            'gender' => $data['genero'] ?? null,
            'position' => $data['puestos'] ?? null,
            'department' => $data['areas'] ?? null,
            'work_schedule' => $data['turno'] ?? null,
            'contract_type' => $data['tipo_contrato'] ?? null,
            'extra_fields' => [
                'source' => 'likert_reprocess',
            ],
        ];
    }

    /**
     * Extract demographic info from Referencia V data
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function extractFromReferenciaV(array $data): array
    {
        $extracted = [
            'gender' => $data['sexo'] ?? null,
            'marital_status' => $data['estado_civil'] ?? null,
            'personnel_type' => $data['tipo_personal'] ?? null,
            'position_type' => $data['tipo_puesto'] ?? null,
            'contract_type' => $data['tipo_contratacion'] ?? null,
            'work_schedule' => $data['tipo_jornada'] ?? null,
            'shift_rotation' => $data['rotacion_turnos'] ?? null,
            'time_in_current_position' => $data['tiempo_puesto_actual'] ?? null,
            'work_experience' => $data['experiencia_laboral'] ?? null,
        ];

        // Process age
        if (isset($data['edad'])) {
            $edad = $data['edad'];
            if (is_array($edad) && isset($edad['decenas'], $edad['unidades'])) {
                $decenas = (int) $edad['decenas'];
                $unidades = (int) $edad['unidades'];
                $extracted['age'] = ($decenas * 10) + $unidades;
            } elseif (is_numeric($edad)) {
                $extracted['age'] = (int) $edad;
            }
        }

        // Process occupation
        if (isset($data['ocupacion'])) {
            if (is_array($data['ocupacion'])) {
                $fila1 = $data['ocupacion']['fila1'] ?? null;
                $fila2 = $data['ocupacion']['fila2'] ?? null;
                $extracted['position'] = $fila1 ?? $fila2;
            } else {
                $extracted['position'] = $data['ocupacion'];
            }
        }

        // Process department
        if (isset($data['departamento'])) {
            if (is_array($data['departamento'])) {
                $fila1 = $data['departamento']['fila1'] ?? null;
                $fila2 = $data['departamento']['fila2'] ?? null;
                $extracted['department'] = $fila1 ?? $fila2;
            } else {
                $extracted['department'] = $data['departamento'];
            }
        }

        // Process education level
        if (isset($data['nivel_estudios'])) {
            $extracted['education_level'] = $this->extractEducationLevel($data['nivel_estudios']);
        }

        $extracted['extra_fields'] = [
            'source' => 'referencia_v_reprocess',
        ];

        return $extracted;
    }

    /**
     * Extract the highest completed education level
     *
     * @param  array<string, mixed>  $nivelEstudios
     */
    protected function extractEducationLevel(array $nivelEstudios): ?string
    {
        $levels = [
            'doctorado' => 'Doctorado',
            'maestria' => 'Maestría',
            'licenciatura' => 'Licenciatura',
            'tecnico_superior' => 'Técnico Superior',
            'preparatoria' => 'Preparatoria',
            'secundaria' => 'Secundaria',
            'primaria' => 'Primaria',
            'sin_formacion' => 'Sin formación',
        ];

        foreach ($levels as $key => $label) {
            $value = $nivelEstudios[$key] ?? false;

            if ($value === true) {
                return $label;
            }

            if (is_array($value) && ($value['seleccionado'] ?? false)) {
                $completado = $value['completado'] ?? '';

                return $label.($completado === 'incompleto' ? ' (incompleta)' : '');
            }
        }

        return null;
    }
}
