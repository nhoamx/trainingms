<?php

namespace App\Console\Commands;

use App\Models\DemographicData;
use App\Models\Organization;
use App\Models\PaperEvaluation;
use App\Models\WorkCenter;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SeedNom035SyntheticData extends Command
{
    protected $signature = 'nom035:seed-synthetic
        {--organization= : Organization UUID to seed}
        {--work-center= : Work center UUID to seed}';

    protected $description = 'Seed synthetic NOM-035 data (Ref III, Ref I, Cisneros) for one organization without evaluations';

    private const TOTAL_PEOPLE = 300;

    private const ATS_PERCENTAGE = 18;

    private const HIGH_AND_VERY_HIGH_PERCENTAGE = 45;

    private const VERY_HIGH_PERCENTAGE = 15;

    private const GLOBAL_NULO_PERCENTAGE = 20;

    private const GLOBAL_BAJO_PERCENTAGE = 20;

    private const GLOBAL_MEDIO_PERCENTAGE = 15;

    private const GLOBAL_ALTO_PERCENTAGE = 30;

    private const GLOBAL_MUY_ALTO_PERCENTAGE = 15;

    public function handle(): int
    {
        $organization = $this->resolveOrganization();

        if (! $organization instanceof Organization) {
            return self::FAILURE;
        }

        if ($this->organizationHasEvaluations($organization->id)) {
            $this->error('La organización seleccionada ya tiene evaluaciones. Selecciona una organización sin evaluaciones.');

            return self::FAILURE;
        }

        $workCenter = $this->resolveWorkCenter($organization);

        if (! $workCenter instanceof WorkCenter) {
            return self::FAILURE;
        }

        $totalPeople = self::TOTAL_PEOPLE;
        $atsCount = (int) round($totalPeople * (self::ATS_PERCENTAGE / 100));
        $veryHighCount = (int) round($totalPeople * (self::VERY_HIGH_PERCENTAGE / 100));
        $highAndVeryHighCount = (int) round($totalPeople * (self::HIGH_AND_VERY_HIGH_PERCENTAGE / 100));
        $highCount = max($highAndVeryHighCount - $veryHighCount, 0);
        $baseCount = max($totalPeople - $veryHighCount - $highCount, 0);

        $globalNuloCount = (int) round($totalPeople * (self::GLOBAL_NULO_PERCENTAGE / 100));
        $globalBajoCount = (int) round($totalPeople * (self::GLOBAL_BAJO_PERCENTAGE / 100));
        $globalMedioCount = (int) round($totalPeople * (self::GLOBAL_MEDIO_PERCENTAGE / 100));

        $confirm = $this->confirm(
            "Se crearán {$totalPeople} personas en {$organization->name} / {$workCenter->name} con Ref III + Ref I y Cisneros solo para Muy Alto. ¿Continuar?",
            true
        );

        if (! $confirm) {
            $this->warn('Operación cancelada por el usuario.');

            return self::SUCCESS;
        }

        DB::beginTransaction();

        try {
            $genders = array_merge(
                array_fill(0, (int) floor($totalPeople / 2), 'Masculino'),
                array_fill(0, (int) ceil($totalPeople / 2), 'Femenino')
            );
            shuffle($genders);

            $atsFlags = array_merge(
                array_fill(0, $atsCount, true),
                array_fill(0, $totalPeople - $atsCount, false)
            );
            shuffle($atsFlags);

            $profilePairs = array_merge(
                array_fill(0, $veryHighCount, ['violence' => 'muy_alto', 'global' => 'muy_alto']),
                array_fill(0, $highCount, ['violence' => 'alto', 'global' => 'alto']),
                array_fill(0, $globalNuloCount, ['violence' => 'base', 'global' => 'nulo']),
                array_fill(0, $globalBajoCount, ['violence' => 'base', 'global' => 'bajo']),
                array_fill(0, $globalMedioCount, ['violence' => 'base', 'global' => 'medio'])
            );
            shuffle($profilePairs);

            $organizationCode = (string) ($organization->folio_organization ?? '01');
            $workCenterCode = $this->normalizeWorkCenterCode($workCenter->code);

            $personalCounter = 1;
            $createdRefIII = 0;
            $createdRefI = 0;
            $createdCisneros = 0;

            $progress = $this->output->createProgressBar($totalPeople);
            $progress->start();

            for ($i = 0; $i < $totalPeople; $i++) {
                $folioblock = $this->generateAvailableFolioBlock($organizationCode, $workCenterCode, $personalCounter);
                $personalCounter = $folioblock['next_counter'];

                $gender = $genders[$i];
                $age = (string) random_int(18, 60);
                $profile = $profilePairs[$i]['violence'];
                $hasAts = $atsFlags[$i];
                $globalProfile = $profilePairs[$i]['global'];

                $refIIIAnswers = $this->generateReferenciaIIIAnswers($profile, $globalProfile);
                $citsats = $this->generateCitsatsAnswers($hasAts);
                $refIIIConditional = $this->generateReferenciaIIIConditional();
                $refIAnswers = $this->generateReferenciaIAnswers($hasAts);

                $name = 'Persona '.$folioblock['personal_folio'];
                $demographicPayload = $this->buildDemographicPayload($gender, $age);
                $demographicRecord = $this->buildDemographicRecord($demographicPayload);

                $refIII = PaperEvaluation::create([
                    'folio' => $folioblock['ref_iii_folio'],
                    'evaluation_type_code' => '02',
                    'organization_code' => $organizationCode,
                    'work_center_code' => $workCenterCode,
                    'personal_folio' => $folioblock['personal_folio'],
                    'organization_id' => $organization->id,
                    'work_center_id' => $workCenter->id,
                    'evaluation_type' => 'referencia_iii',
                    'source' => 'paper',
                    'processing_status' => 'completed',
                    'processed_at' => now(),
                    'evaluee_name' => $name,
                    'demographic_data' => $demographicPayload,
                    'referencia_iii_answers' => $refIIIAnswers,
                    'referencia_iii_conditional' => $refIIIConditional,
                    'citsats_s1' => $citsats,
                    'raw_data' => [
                        'seeded_by' => 'nom035:seed-synthetic',
                        'profile' => $profile,
                        'has_ats' => $hasAts,
                    ],
                ]);

                DemographicData::create(array_merge($demographicRecord, [
                    'paper_evaluation_id' => $refIII->id,
                ]));

                if ($hasAts) {
                    $refI = PaperEvaluation::create([
                        'folio' => $folioblock['ref_i_folio'],
                        'evaluation_type_code' => '01',
                        'organization_code' => $organizationCode,
                        'work_center_code' => $workCenterCode,
                        'personal_folio' => $folioblock['personal_folio'],
                        'organization_id' => $organization->id,
                        'work_center_id' => $workCenter->id,
                        'evaluation_type' => 'referencia_i',
                        'source' => 'paper',
                        'processing_status' => 'completed',
                        'processed_at' => now(),
                        'evaluee_name' => $name,
                        'demographic_data' => $demographicPayload,
                        'referencia_i_answers' => $refIAnswers,
                        'citsats_s1' => $citsats,
                        'related_evaluation_folio' => $folioblock['ref_iii_folio'],
                        'raw_data' => [
                            'seeded_by' => 'nom035:seed-synthetic',
                            'derived_from' => 'ref_i',
                        ],
                    ]);

                    DemographicData::create(array_merge($demographicRecord, [
                        'paper_evaluation_id' => $refI->id,
                    ]));

                    $refIII->update(['related_evaluation_folio' => $folioblock['ref_i_folio']]);
                    $createdRefI++;
                }

                $createdRefIII++;

                if ($profile === 'muy_alto') {
                    $cisnerosAnswers = $this->generateCisnerosAnswers();

                    $cisneros = PaperEvaluation::create([
                        'folio' => $folioblock['cisneros_folio'],
                        'evaluation_type_code' => '04',
                        'organization_code' => $organizationCode,
                        'work_center_code' => $workCenterCode,
                        'personal_folio' => $folioblock['personal_folio'],
                        'organization_id' => $organization->id,
                        'work_center_id' => $workCenter->id,
                        'evaluation_type' => 'cisneros',
                        'source' => 'paper',
                        'processing_status' => 'completed',
                        'processed_at' => now(),
                        'evaluee_name' => $name,
                        'demographic_data' => $demographicPayload,
                        'cisneros_answers' => $cisnerosAnswers,
                        'raw_data' => [
                            'seeded_by' => 'nom035:seed-synthetic',
                            'trigger_profile' => 'muy_alto',
                        ],
                    ]);

                    DemographicData::create(array_merge($demographicRecord, [
                        'paper_evaluation_id' => $cisneros->id,
                    ]));

                    $createdCisneros++;
                }

                $progress->advance();
            }

            $progress->finish();
            $this->newLine(2);

            $organization->update([
                'total_trabajadores' => $totalPeople,
                'total_hombres' => (int) floor($totalPeople / 2),
                'total_mujeres' => (int) ceil($totalPeople / 2),
            ]);

            DB::commit();

            $this->table(
                ['Métrica', 'Valor'],
                [
                    ['Organización', $organization->name],
                    ['Centro de trabajo', $workCenter->name],
                    ['Personas creadas', (string) $totalPeople],
                    ['Ref III creadas', (string) $createdRefIII],
                    ['Ref I creadas', (string) $createdRefI],
                    ['Cisneros creadas', (string) $createdCisneros],
                    ['ATS objetivo', self::ATS_PERCENTAGE.'%'],
                    ['Violencia Alta/Muy Alta objetivo', self::HIGH_AND_VERY_HIGH_PERCENTAGE.'%'],
                    ['Global Nulo objetivo', self::GLOBAL_NULO_PERCENTAGE.'%'],
                    ['Global Bajo objetivo', self::GLOBAL_BAJO_PERCENTAGE.'%'],
                    ['Global Medio objetivo', self::GLOBAL_MEDIO_PERCENTAGE.'%'],
                    ['Global Alto objetivo', self::GLOBAL_ALTO_PERCENTAGE.'%'],
                    ['Global Muy Alto objetivo', self::GLOBAL_MUY_ALTO_PERCENTAGE.'%'],
                    ['Cisneros solo Muy Alto', 'Sí'],
                ]
            );

            $this->info('Datos sintéticos NOM-035 generados correctamente.');

            return self::SUCCESS;
        } catch (\Throwable $exception) {
            DB::rollBack();
            $this->error('Error al generar datos: '.$exception->getMessage());

            return self::FAILURE;
        }
    }

    private function resolveOrganization(): ?Organization
    {
        $organizationId = $this->option('organization');

        if (is_string($organizationId) && $organizationId !== '') {
            $organization = Organization::query()->find($organizationId);

            if (! $organization instanceof Organization) {
                $this->error('La opción --organization no corresponde a una organización válida.');

                return null;
            }

            return $organization;
        }

        $organizations = Organization::query()
            ->whereNotExists(function ($query) {
                $query->selectRaw('1')
                    ->from('paper_evaluations')
                    ->whereColumn('paper_evaluations.organization_id', 'organizations.id');
            })
            ->withCount('workCenters')
            ->having('work_centers_count', '>', 0)
            ->orderBy('name')
            ->get(['id', 'name', 'folio_organization']);

        if ($organizations->isEmpty()) {
            $this->warn('No hay organizaciones sin evaluaciones disponibles para sembrar datos.');

            return null;
        }

        $options = [];
        foreach ($organizations as $organization) {
            $label = $organization->name.' [folio: '.($organization->folio_organization ?? 'N/A').']';
            $options[$label] = $organization->id;
        }

        $selected = $this->choice(
            'Selecciona una organización SIN evaluaciones',
            array_keys($options)
        );

        return Organization::query()->find($options[$selected] ?? null);
    }

    private function resolveWorkCenter(Organization $organization): ?WorkCenter
    {
        $workCenterId = $this->option('work-center');

        if (is_string($workCenterId) && $workCenterId !== '') {
            $workCenter = WorkCenter::query()
                ->where('organization_id', $organization->id)
                ->find($workCenterId);

            if (! $workCenter instanceof WorkCenter) {
                $this->error('La opción --work-center no corresponde a un centro de trabajo de la organización seleccionada.');

                return null;
            }

            return $workCenter;
        }

        $workCenters = $organization->workCenters()
            ->orderByDesc('is_primary')
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        if ($workCenters->isEmpty()) {
            $this->error('La organización no tiene centros de trabajo.');

            return null;
        }

        $options = [];
        foreach ($workCenters as $workCenter) {
            $options[$workCenter->name.' [código: '.$workCenter->code.']'] = $workCenter->id;
        }

        $selected = $this->choice('Selecciona el centro de trabajo donde se sembrarán las evaluaciones', array_keys($options));

        return WorkCenter::query()->find($options[$selected] ?? null);
    }

    private function organizationHasEvaluations(string $organizationId): bool
    {
        return PaperEvaluation::query()->where('organization_id', $organizationId)->exists();
    }

    private function normalizeWorkCenterCode(?string $code): string
    {
        $digits = preg_replace('/\D/', '', (string) $code);

        if ($digits === '' || $digits === '0') {
            return '01';
        }

        return str_pad(substr($digits, -2), 2, '0', STR_PAD_LEFT);
    }

    /**
     * @return array{personal_folio: string, ref_i_folio: string, ref_iii_folio: string, cisneros_folio: string, next_counter: int}
     */
    private function generateAvailableFolioBlock(string $organizationCode, string $workCenterCode, int $startingCounter): array
    {
        $counter = $startingCounter;

        while (true) {
            $personalFolio = str_pad((string) $counter, 5, '0', STR_PAD_LEFT);

            $refIFolio = PaperEvaluation::generateFolio('01', $organizationCode, $personalFolio, $workCenterCode);
            $refIIIFolio = PaperEvaluation::generateFolio('02', $organizationCode, $personalFolio, $workCenterCode);
            $cisnerosFolio = PaperEvaluation::generateFolio('04', $organizationCode, $personalFolio, $workCenterCode);

            $exists = PaperEvaluation::query()
                ->whereIn('folio', [$refIFolio, $refIIIFolio, $cisnerosFolio])
                ->exists();

            if (! $exists) {
                return [
                    'personal_folio' => $personalFolio,
                    'ref_i_folio' => $refIFolio,
                    'ref_iii_folio' => $refIIIFolio,
                    'cisneros_folio' => $cisnerosFolio,
                    'next_counter' => $counter + 1,
                ];
            }

            $counter++;
        }
    }

    private function generateReferenciaIIIAnswers(string $violenceProfile, string $globalProfile): array
    {
        $ranges = config('nom035_risk_levels.global.levels', []);
        $globalRange = $ranges[$globalProfile] ?? $ranges['medio'] ?? ['min' => 75, 'max' => 98];

        for ($attempt = 0; $attempt < 40; $attempt++) {
            $answers = $this->generateBaseReferenciaIIIAnswers($globalProfile);
            $answers = $this->applyViolenceProfileToAnswers($answers, $violenceProfile);

            $score = $this->calculateReferenciaIIITotalScore($answers);

            if ($score >= $globalRange['min'] && $score <= $globalRange['max']) {
                return $answers;
            }
        }

        $fallback = $this->generateBaseReferenciaIIIAnswers($globalProfile);

        return $this->applyViolenceProfileToAnswers($fallback, $violenceProfile);
    }

    private function generateBaseReferenciaIIIAnswers(string $globalProfile): array
    {
        $group1Questions = array_map('intval', config('answer_values.group1.questions', []));
        $group1Lookup = array_fill_keys($group1Questions, true);

        $optionsByProfile = [
            'nulo' => [
                'group1' => ['A', 'A', 'B'],
                'group2' => ['E', 'E', 'D'],
            ],
            'bajo' => [
                'group1' => ['A', 'B', 'B', 'C'],
                'group2' => ['E', 'D', 'D', 'C'],
            ],
            'medio' => [
                'group1' => ['B', 'B', 'C'],
                'group2' => ['D', 'D', 'C'],
            ],
            'alto' => [
                'group1' => ['B', 'C', 'D'],
                'group2' => ['D', 'C', 'B'],
            ],
            'muy_alto' => [
                'group1' => ['C', 'D', 'E', 'E'],
                'group2' => ['C', 'B', 'A', 'A'],
            ],
        ];

        $profileOptions = $optionsByProfile[$globalProfile] ?? $optionsByProfile['medio'];

        $answers = [];
        for ($question = 1; $question <= 64; $question++) {
            $group = isset($group1Lookup[$question]) ? 'group1' : 'group2';
            $options = $profileOptions[$group];
            $answers[(string) $question] = $options[array_rand($options)];
        }

        return $answers;
    }

    /**
     * @param  array<string, string>  $answers
     * @return array<string, string>
     */
    private function applyViolenceProfileToAnswers(array $answers, string $violenceProfile): array
    {
        if ($violenceProfile === 'muy_alto') {
            $answers['57'] = 'A';
            $answers['58'] = 'A';
            $answers['59'] = 'A';
            $answers['60'] = 'A';
            $answers['61'] = 'A';
            $answers['62'] = 'A';
            $answers['63'] = 'A';
            $answers['64'] = 'A';

            return $answers;
        }

        if ($violenceProfile === 'alto') {
            $answers['57'] = 'C';
            $answers['58'] = 'B';
            $answers['59'] = 'B';
            $answers['60'] = 'C';
            $answers['61'] = 'C';
            $answers['62'] = 'D';
            $answers['63'] = 'E';
            $answers['64'] = 'E';

            return $answers;
        }

        $answers['57'] = 'E';
        $answers['58'] = 'E';
        $answers['59'] = 'D';
        $answers['60'] = 'E';
        $answers['61'] = 'D';
        $answers['62'] = 'E';
        $answers['63'] = 'E';
        $answers['64'] = 'E';

        return $answers;
    }

    /**
     * @param  array<string, string>  $answers
     */
    private function calculateReferenciaIIITotalScore(array $answers): int
    {
        $answerValues = config('answer_values');
        $group1Questions = config('answer_values.group1.questions', []);
        $totalScore = 0;

        foreach ($answers as $questionNumber => $answer) {
            if (! is_string($answer)) {
                continue;
            }

            $questionKey = str_pad((string) $questionNumber, 2, '0', STR_PAD_LEFT);
            $group = in_array($questionKey, $group1Questions, true) ? 'group1' : 'group2';
            $totalScore += $answerValues[$group]['values'][$answer] ?? 0;
        }

        return $totalScore;
    }

    private function generateReferenciaIIIConditional(): array
    {
        $options = ['A', 'B', 'C', 'D', 'E'];

        $hasCustomerService = random_int(1, 100) <= 70;
        $hasManagement = random_int(1, 100) <= 25;

        $customerServiceQuestions = [];
        if ($hasCustomerService) {
            for ($q = 65; $q <= 68; $q++) {
                $customerServiceQuestions[(string) $q] = $options[array_rand($options)];
            }
        }

        $managementQuestions = [];
        if ($hasManagement) {
            for ($q = 69; $q <= 72; $q++) {
                $managementQuestions[(string) $q] = $options[array_rand($options)];
            }
        }

        return [
            'customer_service' => [
                'condition' => $hasCustomerService ? 'SI' : 'NO',
                'questions' => $customerServiceQuestions,
            ],
            'management' => [
                'condition' => $hasManagement ? 'SI' : 'NO',
                'questions' => $managementQuestions,
            ],
        ];
    }

    private function generateCitsatsAnswers(bool $hasAts): array
    {
        $answers = [];

        for ($i = 1; $i <= 6; $i++) {
            $answers[(string) $i] = 'NO';
        }

        if (! $hasAts) {
            return $answers;
        }

        $yesIndex = random_int(1, 6);
        $answers[(string) $yesIndex] = 'SI';

        return $answers;
    }

    private function generateReferenciaIAnswers(bool $hasAts): array
    {
        $answers = [];

        for ($i = 1; $i <= 14; $i++) {
            if (! $hasAts) {
                $answers[(string) $i] = 'NO';

                continue;
            }

            $answers[(string) $i] = random_int(1, 100) <= 35 ? 'SI' : 'NO';
        }

        if ($hasAts && ! in_array('SI', $answers, true)) {
            $answers[(string) random_int(1, 14)] = 'SI';
        }

        return $answers;
    }

    private function generateCisnerosAnswers(): array
    {
        $answers = [];
        $personas = ['A', 'B', 'C'];
        $frequencies = [3, 4, 5, 6];

        for ($question = 1; $question <= 43; $question++) {
            $answers[(string) $question] = [
                'persona' => $personas[array_rand($personas)],
                'frecuencia' => $frequencies[array_rand($frequencies)],
            ];
        }

        $answers['44'] = true;

        return $answers;
    }

    private function buildDemographicPayload(string $gender, string $age): array
    {
        $positions = ['Operador', 'Supervisor', 'Analista', 'Coordinador', 'Tecnico'];
        $departments = ['Produccion', 'Calidad', 'Mantenimiento', 'Recursos Humanos', 'Logistica'];
        $maritalStatuses = ['Soltero', 'Casado', 'Union libre', 'Divorciado'];
        $educationLevels = ['Secundaria', 'Preparatoria', 'Tecnico', 'Licenciatura'];
        $positionTypes = ['Operativo', 'Supervisor', 'Gerente'];
        $contractTypes = ['Tiempo indeterminado', 'Por tiempo determinado', 'Por obra o proyecto'];
        $personnelTypes = ['Sindicalizado', 'Confianza', 'Ninguno'];
        $workSchedules = ['Fijo diurno', 'Fijo nocturno', 'Fijo mixto'];
        $timeRanges = ['Menos de 6 meses', 'Entre 6 meses y 1 ano', 'Entre 1 a 4 anos', 'Entre 5 a 9 anos'];

        return [
            'sexo' => $gender,
            'edad' => $age,
            'estado_civil' => $maritalStatuses[array_rand($maritalStatuses)],
            'nivel_estudios' => $educationLevels[array_rand($educationLevels)],
            'ocupacion_puesto' => ['fila1' => $positions[array_rand($positions)]],
            'departamento_seccion_area' => ['fila1' => $departments[array_rand($departments)]],
            'tipo_puesto' => $positionTypes[array_rand($positionTypes)],
            'tipo_contratacion' => $contractTypes[array_rand($contractTypes)],
            'tipo_personal' => $personnelTypes[array_rand($personnelTypes)],
            'tipo_jornada' => $workSchedules[array_rand($workSchedules)],
            'rotacion_turnos' => random_int(0, 1) === 1 ? 'Si' : 'No',
            'tiempo_puesto_actual' => $timeRanges[array_rand($timeRanges)],
            'tiempo_experiencia_laboral' => $timeRanges[array_rand($timeRanges)],
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function buildDemographicRecord(array $payload): array
    {
        return [
            'gender' => (string) ($payload['sexo'] ?? 'No especificado'),
            'age' => (string) ($payload['edad'] ?? '0'),
            'marital_status' => (string) ($payload['estado_civil'] ?? 'No especificado'),
            'education_level' => (string) ($payload['nivel_estudios'] ?? 'No especificado'),
            'position' => (string) ($payload['ocupacion_puesto']['fila1'] ?? 'No especificado'),
            'department' => (string) ($payload['departamento_seccion_area']['fila1'] ?? 'No especificado'),
            'position_type' => (string) ($payload['tipo_puesto'] ?? 'No especificado'),
            'contract_type' => (string) ($payload['tipo_contratacion'] ?? 'No especificado'),
            'personnel_type' => (string) ($payload['tipo_personal'] ?? 'No especificado'),
            'work_schedule' => (string) ($payload['tipo_jornada'] ?? 'No especificado'),
            'shift_rotation' => (string) ($payload['rotacion_turnos'] ?? 'No'),
            'time_in_current_position' => (string) ($payload['tiempo_puesto_actual'] ?? 'No especificado'),
            'work_experience' => (string) ($payload['tiempo_experiencia_laboral'] ?? 'No especificado'),
        ];
    }
}
