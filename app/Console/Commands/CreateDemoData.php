<?php

namespace App\Console\Commands;

use App\Models\DemographicData;
use App\Models\DepartmentArea;
use App\Models\OccupationPosition;
use App\Models\Organization;
use App\Models\PaperEvaluation;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class CreateDemoData extends Command
{
    protected $signature = 'demo:create-data';

    protected $description = 'Create demo organization with user, positions, areas, and 100 NOM-035 evaluations';

    private array $positions = [
        'Gerente General',
        'Gerente de Área',
        'Supervisor de Línea',
        'Coordinador de Producción',
        'Analista de Recursos Humanos',
        'Operador de Maquinaria',
        'Técnico de Mantenimiento',
        'Auxiliar Administrativo',
        'Vendedor',
        'Asistente de Gerencia',
    ];

    private array $areas = [
        'Recursos Humanos',
        'Producción',
        'Administración',
        'Ventas',
        'Mantenimiento',
        'Calidad',
        'Logística',
        'Almacén',
        'Compras',
        'Finanzas',
    ];

    private array $ageRanges = ['20 - 24', '25 - 29', '30 - 34', '35 - 39', '40 - 44', '45 - 49', '50 - 54', '55 - 59'];

    private array $maritalStatuses = ['Casado', 'Soltero', 'Unión libre', 'Divorciado'];

    private array $educationLevels = [
        'Secundaria Terminada',
        'Preparatoria o Bachillerato Terminada',
        'Técnico Superior Terminada',
        'Licenciatura Terminada',
        'Licenciatura Incompleta',
    ];

    private array $positionTypes = ['Operativo', 'Profesional o técnico', 'Supervisor', 'Gerente'];

    private array $contractTypes = [
        'Tiempo indeterminado',
        'Por tiempo determinado (temporal)',
        'Por obra o proyecto',
    ];

    private array $personnelTypes = ['Sindicalizado', 'Confianza', 'Ninguno'];

    private array $workSchedules = [
        'Fijo diurno (entre las 6:00 y 20:00 hrs)',
        'Fijo nocturno (entre las 20:00 y 6:00 hrs)',
        'Fijo mixto (combinación de nocturno y diurno)',
    ];

    private array $timeRanges = [
        'Menos de 6 meses',
        'Entre 6 meses y 1 año',
        'Entre 1 a 4 años',
        'Entre 5 a 9 años',
        'Entre 10 a 14 años',
    ];

    public function handle(): int
    {
        DB::beginTransaction();

        try {
            $this->info('🚀 Iniciando creación de datos DEMO...');

            // 1. Crear organización DEMO
            $organization = $this->createDemoOrganization();
            $this->info("✅ Organización creada: {$organization->name}");

            // 2. Crear puestos y áreas
            $this->createPositionsAndAreas($organization);
            $this->info('✅ Puestos y áreas creados');

            // 3. Crear usuario demo
            $user = $this->createDemoUser($organization);
            $this->info("✅ Usuario creado: {$user->email}");

            // 4. Crear 100 evaluaciones con datos demográficos
            $withReferenciaI = $this->createEvaluations($organization);
            $this->info('✅ 100 evaluaciones creadas con datos demográficos');

            DB::commit();

            $this->newLine();
            $this->info('🎉 Datos DEMO creados exitosamente!');
            $this->newLine();
            $this->table(
                ['Información', 'Valor'],
                [
                    ['Organización', $organization->name],
                    ['Email', $user->email],
                    ['Password', 'password'],
                    ['Total Evaluaciones', '100'],
                    ['Con Referencia III', '100'],
                    ['Con Referencia I (PTSD)', $withReferenciaI],
                    ['Con Datos Demográficos', '100'],
                ]
            );

            return Command::SUCCESS;
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('❌ Error al crear datos DEMO: '.$e->getMessage());
            $this->error($e->getTraceAsString());

            return Command::FAILURE;
        }
    }

    private function createDemoOrganization(): Organization
    {
        // Eliminar todas las evaluaciones con organization_code 999 (independiente de la organización)
        PaperEvaluation::where('organization_code', '999')->each(function ($evaluation) {
            $evaluation->demographicData()->delete();
            $evaluation->customFields()->delete();
            $evaluation->comments()->delete();
            $evaluation->delete();
        });

        // Verificar si ya existe
        $existing = Organization::where('name', 'Empresa DEMO')->first();
        if ($existing) {
            $this->warn('⚠️  La organización DEMO ya existe, se eliminará y recreará...');

            // Eliminar todas las evaluaciones y sus datos relacionados
            PaperEvaluation::where('organization_id', $existing->id)->each(function ($evaluation) {
                $evaluation->demographicData()->delete();
                $evaluation->customFields()->delete();
                $evaluation->comments()->delete();
                $evaluation->delete();
            });

            // Eliminar puestos y áreas
            $existing->occupationPositions()->delete();
            $existing->departmentAreas()->delete();

            // Eliminar la organización
            $existing->delete();
        }

        // Crear con DB::table() para poder especificar el UUID manualmente
        DB::table('organizations')->insert([
            'id' => 'a0d2175b-4c4e-4e6b-bc5d-6faef772b10d',
            'name' => 'Empresa DEMO',
            'folio_organization' => '999',
            'razon_social' => 'Empresa DEMO S.A. de C.V.',
            'rfc' => 'EDM999999XXX',
            'registro_patronal' => 'REG-DEMO-999',
            'calle_numero' => 'Av. Demostración #999',
            'colonia' => 'Colonia Demo',
            'codigo_postal' => '99999',
            'municipio' => 'Ciudad Demo',
            'estado' => 'Estado Demo',
            'contacto_nombre' => 'Juan Pérez',
            'contacto_puesto' => 'Gerente de RH',
            'contacto_email' => 'contacto@demo.com',
            'contacto_movil' => '5551234567',
            'responsable_nombre' => 'María González',
            'responsable_puesto' => 'Director General',
            'responsable_email' => 'director@demo.com',
            'responsable_movil' => '5559876543',
            'actividad_principal' => 'Servicios de demostración y capacitación',
            'total_trabajadores' => 100,
            'total_hombres' => 55,
            'total_mujeres' => 45,
            'muestra_aplicada' => 100,
            'muestra_hombres' => 55,
            'muestra_mujeres' => 45,
            'comite_integrantes' => 5,
            'comite_hombres' => 3,
            'comite_mujeres' => 2,
            'fecha_aplicacion' => now()->format('Y-m-d'),
            'justificacion_muestra' => 'Se aplicó censo completo a todos los trabajadores para fines demostrativos',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Retornar el modelo de Eloquent
        return Organization::find('a0d2175b-4c4e-4e6b-bc5d-6faef772b10d');
    }

    private function createPositionsAndAreas(Organization $organization): void
    {
        foreach ($this->positions as $index => $position) {
            OccupationPosition::create([
                'organization_id' => $organization->id,
                'identifier' => (string) ($index + 1),
                'name' => $position,
            ]);
        }

        foreach ($this->areas as $index => $area) {
            DepartmentArea::create([
                'organization_id' => $organization->id,
                'identifier' => (string) ($index + 1),
                'name' => $area,
            ]);
        }
    }

    private function createDemoUser(Organization $organization): User
    {
        // Verificar si ya existe el usuario
        $existing = User::where('email', 'demo@email.com')->first();
        if ($existing) {
            $this->warn('⚠️  El usuario demo@email.com ya existe, se eliminará y recreará...');
            // Forzar eliminación incluso si tiene relaciones
            $existing->forceDelete();
        }

        $user = User::create([
            'name' => 'Usuario Demo',
            'email' => 'demo@email.com',
            'password' => Hash::make('password'),
            'organization_id' => $organization->id,
        ]);

        // Asignar rol de admin
        $user->assignRole('admin');

        return $user;
    }

    private function createEvaluations(Organization $organization): int
    {
        $bar = $this->output->createProgressBar(100);
        $bar->start();

        $withReferenciaICount = 0;

        for ($i = 1; $i <= 100; $i++) {
            $personalFolio = str_pad((string) $i, 4, '0', STR_PAD_LEFT);
            $folio = '02'.$organization->folio_organization.$personalFolio;

            // Generar respuestas para Referencia III
            $referenciaIIIAnswers = $this->generateReferenciaIIIAnswers();

            // Generar respuestas condicionales con ~20% que tenga al menos un SI en acontecimientos_traumaticos
            $conditionalAnswers = $this->generateConditionalAnswers($i);

            // Determinar si necesita Referencia I basado en acontecimientos traumáticos
            $needsReferenciaI = $this->checkIfNeedsReferenciaI($conditionalAnswers);

            if ($needsReferenciaI) {
                $withReferenciaICount++;
            }

            // Crear la evaluación
            $evaluation = PaperEvaluation::create([
                'folio' => $folio,
                'evaluee_name' => 'Evaluado Demo '.$i,
                'evaluation_type_code' => '02',
                'organization_code' => $organization->folio_organization,
                'personal_folio' => $personalFolio,
                'organization_id' => $organization->id,
                'evaluation_type' => 'referencia_iii',
                'source' => 'paper',
                'processing_status' => 'completed',
                'processed_at' => now(),
                'referencia_iii_answers' => $referenciaIIIAnswers,
                'referencia_iii_conditional' => $conditionalAnswers,
                'referencia_i_answers' => $needsReferenciaI ? $this->generateReferenciaIAnswers() : null,
            ]);

            // Crear datos demográficos
            $this->createDemographicData($evaluation);

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        return $withReferenciaICount;
    }

    private function generateReferenciaIIIAnswers(): array
    {
        $answers = [];
        $options = ['A', 'B', 'C', 'D', 'E'];

        // Generar respuestas para las preguntas 1-64 (excluyendo 65-72 que van en condicional)
        for ($q = 1; $q <= 64; $q++) {
            $questionKey = str_pad((string) $q, 2, '0', STR_PAD_LEFT);
            $answers[$questionKey] = $options[array_rand($options)];
        }

        return $answers;
    }

    private function generateConditionalAnswers(int $evaluationNumber): array
    {
        $options = ['A', 'B', 'C', 'D', 'E'];
        $yesNo = ['SI', 'NO'];

        // Customer service (preguntas 65-68) - 70% responde que sí tiene atención a clientes
        $hasCustomerService = rand(1, 100) <= 70;
        $customerServiceAnswers = [];

        if ($hasCustomerService) {
            for ($q = 65; $q <= 68; $q++) {
                $customerServiceAnswers[$q] = $options[array_rand($options)];
            }
        }

        // Management (preguntas 69-72) - 30% tiene personal a cargo
        $hasManagement = rand(1, 100) <= 30;
        $managementAnswers = [];

        if ($hasManagement) {
            for ($q = 69; $q <= 72; $q++) {
                $managementAnswers[$q] = $options[array_rand($options)];
            }
        }

        // Acontecimientos traumáticos (preguntas 73-78)
        // Aproximadamente 20% tendrá al menos un SI (los primeros 20 de 100)
        $traumaticEvents = [];
        $shouldHaveTrauma = $evaluationNumber <= 20;

        if ($shouldHaveTrauma) {
            // Este evaluado TENDRÁ al menos un SI (probabilidad alta de múltiples SI)
            for ($q = 73; $q <= 78; $q++) {
                // 50% de probabilidad de SI para cada pregunta
                $traumaticEvents[$q] = rand(1, 100) <= 50 ? 'SI' : 'NO';
            }

            // Asegurar que al menos una respuesta sea SI
            $allNo = true;
            foreach ($traumaticEvents as $answer) {
                if ($answer === 'SI') {
                    $allNo = false;
                    break;
                }
            }

            if ($allNo) {
                // Forzar al menos un SI en una pregunta aleatoria
                $randomQuestion = rand(73, 78);
                $traumaticEvents[$randomQuestion] = 'SI';
            }
        } else {
            // Este evaluado NO tendrá acontecimientos traumáticos (todo NO)
            for ($q = 73; $q <= 78; $q++) {
                $traumaticEvents[$q] = 'NO';
            }
        }

        return [
            'customer_service' => [
                'condition' => $hasCustomerService ? 'SI' : 'NO',
                'questions' => $customerServiceAnswers,
            ],
            'management' => [
                'condition' => $hasManagement ? 'SI' : 'NO',
                'questions' => $managementAnswers,
            ],
            'acontecimientos_traumaticos' => $traumaticEvents,
        ];
    }

    private function checkIfNeedsReferenciaI(array $conditionalAnswers): bool
    {
        // Verificar si respondió SI en alguna pregunta de acontecimientos traumáticos (73-78)
        $traumaticEvents = $conditionalAnswers['acontecimientos_traumaticos'] ?? [];

        foreach ($traumaticEvents as $answer) {
            if ($answer === 'SI') {
                return true;
            }
        }

        return false;
    }

    private function generateReferenciaIAnswers(): array
    {
        $answers = [];
        $yesNo = ['SI', 'NO'];

        // 12 preguntas de PTSD
        for ($q = 1; $q <= 12; $q++) {
            // 40% de probabilidad de responder SÍ (presencia de síntomas de trauma)
            $answers[(string) $q] = rand(1, 100) <= 40 ? 'SI' : 'NO';
        }

        return $answers;
    }

    private function createDemographicData(PaperEvaluation $evaluation): void
    {
        $gender = rand(0, 1) === 0 ? 'Masculino' : 'Femenino';

        DemographicData::create([
            'paper_evaluation_id' => $evaluation->id,
            'gender' => $gender,
            'age' => $this->ageRanges[array_rand($this->ageRanges)],
            'marital_status' => $this->maritalStatuses[array_rand($this->maritalStatuses)],
            'education_level' => $this->educationLevels[array_rand($this->educationLevels)],
            'position' => $this->positions[array_rand($this->positions)],
            'department' => $this->areas[array_rand($this->areas)],
            'position_type' => $this->positionTypes[array_rand($this->positionTypes)],
            'contract_type' => $this->contractTypes[array_rand($this->contractTypes)],
            'personnel_type' => $this->personnelTypes[array_rand($this->personnelTypes)],
            'work_schedule' => $this->workSchedules[array_rand($this->workSchedules)],
            'shift_rotation' => rand(0, 1) === 0 ? 'Sí' : 'No',
            'time_in_current_position' => $this->timeRanges[array_rand($this->timeRanges)],
            'work_experience' => $this->timeRanges[array_rand($this->timeRanges)],
        ]);
    }
}
