<?php

namespace Tests\Unit\Services;

use App\Services\DemographicDataNormalizationService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class DemographicDataNormalizationServiceTest extends TestCase
{
    use DatabaseTransactions;

    private DemographicDataNormalizationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(DemographicDataNormalizationService::class);
    }

    public function test_extracts_demographic_info_from_online_quiz_data(): void
    {
        $onlineData = [
            'sexo' => 'Masculino',
            'edad' => '35',
            'estado_civil' => 'Casado',
            'nivel_estudios' => 'Licenciatura',
            'datos_laborales' => [
                'ocupacion_puesto' => 'Gerente',
                'departamento_seccion_area' => 'Recursos Humanos',
                'tipo_puesto' => 'Mandos medios',
                'tipo_contratacion' => 'Base',
                'tipo_personal' => 'Sindicalizado',
                'tipo_jornada' => 'Diurno',
                'rotacion_turnos' => 'No',
                'experiencia' => [
                    'tiempo_puesto_actual' => '1-4 años',
                    'tiempo_experiencia_laboral' => '5-9 años',
                ],
            ],
        ];

        $result = $this->service->extractDemographicInfo($onlineData);

        $this->assertEquals('Masculino', $result['gender']);
        $this->assertEquals('35', $result['age']);
        $this->assertEquals('Casado', $result['marital_status']);
        $this->assertEquals('Licenciatura', $result['education_level']);
        $this->assertEquals('Gerente', $result['position']);
        $this->assertEquals('Recursos Humanos', $result['department']);
        $this->assertEquals('Mandos medios', $result['position_type']);
        $this->assertEquals('Base', $result['contract_type']);
        $this->assertEquals('Sindicalizado', $result['personnel_type']);
        $this->assertEquals('Diurno', $result['work_schedule']);
        $this->assertEquals('No', $result['shift_rotation']);
        $this->assertEquals('1-4 años', $result['time_in_current_position']);
        $this->assertEquals('5-9 años', $result['work_experience']);
    }

    public function test_extracts_demographic_info_from_old_ocr_structure(): void
    {
        $ocrData = [
            'sexo' => 'masculino',
            'edad' => [
                'decenas' => 3,
                'unidades' => 5,
            ],
            'estado_civil' => 'casado',
            'nivel_estudios' => [
                'licenciatura' => [
                    'seleccionado' => true,
                    'completado' => 'Terminada',
                ],
            ],
            'ocupacion_puesto' => [
                'fila1' => 'Supervisor',
                'fila2' => null,
            ],
            'departamento_seccion_area' => [
                'fila1' => 'Producción',
                'fila2' => null,
            ],
            'tipo_puesto' => '2',
            'tipo_contratacion' => '1',
            'tipo_personal' => '1',
            'tipo_jornada' => '1',
            'rotacion_turnos' => '0',
        ];

        $result = $this->service->extractDemographicInfo($ocrData);

        $this->assertEquals('Masculino', $result['gender']);
        $this->assertEquals('35 - 39', $result['age']);
        $this->assertEquals('Casado', $result['marital_status']);
        $this->assertEquals('Licenciatura - Terminada', $result['education_level']);
        $this->assertEquals('Supervisor', $result['position']);
        $this->assertEquals('Producción', $result['department']);
        $this->assertNotNull($result['position_type']);
        $this->assertNotNull($result['contract_type']);
    }

    public function test_extracts_demographic_info_from_likert_data(): void
    {
        $likertData = [
            'questions' => ['q1' => 4, 'q2' => 3],
            'genero' => 'femenino',
            'puestos' => 'Coordinador',
            'areas' => 'Ventas',
            'tipo_contrato' => 'temporal',
            'turno' => 'matutino',
        ];

        $result = $this->service->extractDemographicInfo($likertData);

        $this->assertEquals('Femenino', $result['gender']);
        $this->assertNull($result['age']); // Not provided in Likert
        $this->assertNull($result['marital_status']); // Not provided in Likert
        $this->assertNull($result['education_level']); // Not provided in Likert
        $this->assertEquals('Coordinador', $result['position']);
        $this->assertEquals('Ventas', $result['department']);
        $this->assertNotNull($result['contract_type']);
        $this->assertArrayHasKey('extra_fields', $result);
        $this->assertArrayHasKey('questions', $result['extra_fields']);
    }

    public function test_converts_numeric_age_to_range_format(): void
    {
        $testCases = [
            ['edad' => ['decenas' => 2, 'unidades' => 5], 'expected' => '25 - 29'],
            ['edad' => ['decenas' => 3, 'unidades' => 0], 'expected' => '30 - 34'],
            ['edad' => ['decenas' => 4, 'unidades' => 7], 'expected' => '45 - 49'],
            ['edad' => ['decenas' => 5, 'unidades' => 9], 'expected' => '55 - 59'],
            ['edad' => ['decenas' => 1, 'unidades' => 8], 'expected' => '15 - 19'],
        ];

        foreach ($testCases as $testCase) {
            $data = [
                'sexo' => 'masculino',
                'edad' => $testCase['edad'],
            ];

            $result = $this->service->extractDemographicInfo($data);

            $this->assertEquals($testCase['expected'], $result['age']);
        }
    }

    public function test_normalizes_contract_type_values(): void
    {
        $testCases = [
            ['input' => 'por_obra_o_proyecto', 'expected' => 'Por obra o proyecto'],
            ['input' => 'por_tiempo_determinado_temporal', 'expected' => 'Por tiempo determinado (temporal)'],
            ['input' => 'tiempo_indeterminado', 'expected' => 'Tiempo indeterminado'],
            ['input' => 'tiempo_determinado', 'expected' => 'Tiempo determinado'],
            ['input' => 'Base', 'expected' => 'Base'],
            ['input' => 'Confianza', 'expected' => 'Confianza'],
            // Numeric values are not normalized, they return as-is
            ['input' => '1', 'expected' => '1'],
            ['input' => '2', 'expected' => '2'],
        ];

        foreach ($testCases as $testCase) {
            $data = [
                'sexo' => 'masculino',
                'tipo_contratacion' => $testCase['input'],
            ];

            $result = $this->service->extractDemographicInfo($data);

            $this->assertEquals($testCase['expected'], $result['contract_type'], "Failed for input: {$testCase['input']}");
        }
    }

    public function test_normalizes_position_type_values(): void
    {
        $testCases = [
            ['input' => 'operativo', 'expected' => 'Operativo'],
            ['input' => 'supervisor', 'expected' => 'Supervisor'],
            ['input' => 'profesional_o_tecnico', 'expected' => 'Profesional o técnico'],
            ['input' => 'gerente', 'expected' => 'Gerente'],
            ['input' => 'Operativo', 'expected' => 'Operativo'],
            ['input' => 'Mandos medios', 'expected' => 'Mandos medios'],
            // Numeric values are not normalized in old structure, they return as-is
            ['input' => '1', 'expected' => '1'],
            ['input' => '2', 'expected' => '2'],
        ];

        foreach ($testCases as $testCase) {
            $data = [
                'sexo' => 'masculino',
                'tipo_puesto' => $testCase['input'],
            ];

            $result = $this->service->extractDemographicInfo($data);

            $this->assertNotNull($result['position_type'], "Position type should not be null for input: {$testCase['input']}");
            $this->assertEquals($testCase['expected'], $result['position_type'], "Failed for input: {$testCase['input']}");
        }
    }

    public function test_normalizes_department_type_values(): void
    {
        $testCases = [
            ['input' => 'Recursos Humanos', 'expected' => 'Recursos Humanos'],
            ['input' => 'Producción', 'expected' => 'Producción'],
            ['input' => 'Ventas', 'expected' => 'Ventas'],
            ['input' => 'Administración', 'expected' => 'Administración'],
            ['input' => ['fila1' => 'IT Department', 'fila2' => null], 'expected' => 'IT Department'],
        ];

        foreach ($testCases as $testCase) {
            $data = [
                'sexo' => 'masculino',
                'departamento_seccion_area' => $testCase['input'],
            ];

            $result = $this->service->extractDemographicInfo($data);

            $this->assertEquals($testCase['expected'], $result['department']);
        }
    }

    public function test_handles_missing_demographic_fields_gracefully(): void
    {
        $incompleteData = [
            'sexo' => 'Masculino',
            // Missing: edad, estado_civil, nivel_estudios, datos_laborales
        ];

        $result = $this->service->extractDemographicInfo($incompleteData);

        $this->assertEquals('Masculino', $result['gender']);
        $this->assertNull($result['age']);
        $this->assertNull($result['marital_status']);
        $this->assertNull($result['education_level']);
        $this->assertNull($result['position']);
        $this->assertNull($result['department']);
        $this->assertNull($result['position_type']);
        $this->assertNull($result['contract_type']);
    }
}
