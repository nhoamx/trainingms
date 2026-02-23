<?php

namespace Tests\Feature;

use App\Jobs\ProcessOnlineEvaluation;
use App\Models\Organization;
use App\Models\PaperEvaluation;
use App\Models\Quiz;
use App\Models\SubmissionStatus;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ProcessOnlineEvaluationTest extends TestCase
{
    use DatabaseTransactions;

    protected Organization $organization;

    protected Quiz $quiz;

    protected function setUp(): void
    {
        parent::setUp();

        $this->organization = Organization::factory()->create();
        $this->quiz = Quiz::factory()->create([
            'organization_id' => $this->organization->id,
        ]);
    }

    public function test_extracts_standardized_referencia_i_with_numeric_indices(): void
    {
        $submissionStatus = SubmissionStatus::create([
            'folio' => '020010009',
            'personal_id' => '0009',
            'organization_id' => $this->organization->id,
            'quiz_id' => $this->quiz->id,
            'status' => SubmissionStatus::STATUS_PENDING,
            'data_snapshot' => [
                'evaluation_type' => 'referencia_v',
                'referencia_v' => ['sexo' => 'Masculino', 'edad' => '30'],
                'referencia_i' => [
                    '1' => true,
                    '2' => false,
                    '3' => true,
                    '4' => false,
                    '5' => true,
                    '6' => false,
                    '7' => true,
                    '8' => false,
                    '9' => true,
                    '10' => false,
                    '11' => true,
                    '12' => false,
                    '13' => true,
                    '14' => false,
                ],
            ],
        ]);

        ProcessOnlineEvaluation::dispatchSync($submissionStatus->id);

        $paperEvaluation = PaperEvaluation::where('folio', '020010009')->first();
        $this->assertNotNull($paperEvaluation);
        $this->assertNotNull($paperEvaluation->referencia_i_answers);

        // Verify indices 1-14 with correct values
        $this->assertEquals(true, $paperEvaluation->referencia_i_answers['1']);
        $this->assertEquals(false, $paperEvaluation->referencia_i_answers['2']);
        $this->assertEquals(true, $paperEvaluation->referencia_i_answers['13']);
        $this->assertEquals(false, $paperEvaluation->referencia_i_answers['14']);

        // Verify only indices 1-14 are present
        $this->assertCount(14, $paperEvaluation->referencia_i_answers);
    }

    public function test_extracts_standardized_referencia_iii_with_numeric_indices(): void
    {
        $answers = [];
        for ($i = 1; $i <= 64; $i++) {
            $answers[(string) $i] = ['A', 'B', 'C', 'D', 'E'][($i - 1) % 5];
        }

        $submissionStatus = SubmissionStatus::create([
            'folio' => '020010010',
            'personal_id' => '0010',
            'organization_id' => $this->organization->id,
            'quiz_id' => $this->quiz->id,
            'status' => SubmissionStatus::STATUS_PENDING,
            'data_snapshot' => [
                'evaluation_type' => 'referencia_v',
                'referencia_v' => ['sexo' => 'Femenino', 'edad' => '28'],
                'referencia_iii' => $answers,
            ],
        ]);

        ProcessOnlineEvaluation::dispatchSync($submissionStatus->id);

        $paperEvaluation = PaperEvaluation::where('folio', '020010010')->first();
        $this->assertNotNull($paperEvaluation);
        $this->assertNotNull($paperEvaluation->referencia_iii_answers);

        // Verify indices 1-64 are present
        $this->assertCount(64, $paperEvaluation->referencia_iii_answers);
        $this->assertEquals('A', $paperEvaluation->referencia_iii_answers['1']);
        $this->assertEquals('D', $paperEvaluation->referencia_iii_answers['64']);
    }

    public function test_extracts_citsats_s1_with_indices_1_to_6(): void
    {
        $submissionStatus = SubmissionStatus::create([
            'folio' => '020010011',
            'personal_id' => '0011',
            'organization_id' => $this->organization->id,
            'quiz_id' => $this->quiz->id,
            'status' => SubmissionStatus::STATUS_PENDING,
            'data_snapshot' => [
                'evaluation_type' => 'referencia_v',
                'referencia_v' => ['sexo' => 'Masculino', 'edad' => '35'],
                'referencia_iii' => [
                    '1' => 'A',
                    'ats_s1' => [
                        '1' => true,
                        '2' => false,
                        '3' => false,
                        '4' => true,
                        '5' => false,
                        '6' => true,
                    ],
                ],
            ],
        ]);

        ProcessOnlineEvaluation::dispatchSync($submissionStatus->id);

        $paperEvaluation = PaperEvaluation::where('folio', '020010011')->first();
        $this->assertNotNull($paperEvaluation);
        $this->assertNotNull($paperEvaluation->citsats_s1);

        // Verify indices 1-6 with correct structure
        $this->assertCount(6, $paperEvaluation->citsats_s1);
        $this->assertEquals(true, $paperEvaluation->citsats_s1['1']);
        $this->assertEquals(false, $paperEvaluation->citsats_s1['2']);
        $this->assertEquals(true, $paperEvaluation->citsats_s1['6']);

        // Verify NO duplicate indices like "731", "741"
        $this->assertArrayNotHasKey('731', $paperEvaluation->citsats_s1);
        $this->assertArrayNotHasKey('741', $paperEvaluation->citsats_s1);
    }

    public function test_extracts_customer_service_conditional_with_true_condition(): void
    {
        $submissionStatus = SubmissionStatus::create([
            'folio' => '020010012',
            'personal_id' => '0012',
            'organization_id' => $this->organization->id,
            'quiz_id' => $this->quiz->id,
            'status' => SubmissionStatus::STATUS_PENDING,
            'data_snapshot' => [
                'evaluation_type' => 'referencia_v',
                'referencia_v' => ['sexo' => 'Femenino', 'edad' => '32'],
                'referencia_iii' => [
                    '1' => 'A',
                    'customer_service' => [
                        'condition' => true,
                        '65' => 'A',
                        '66' => 'B',
                        '67' => 'C',
                        '68' => 'D',
                    ],
                ],
            ],
        ]);

        ProcessOnlineEvaluation::dispatchSync($submissionStatus->id);

        $paperEvaluation = PaperEvaluation::where('folio', '020010012')->first();
        $this->assertNotNull($paperEvaluation);
        $this->assertNotNull($paperEvaluation->referencia_iii_conditional);

        // Verify customer_service with condition true includes answers
        $this->assertArrayHasKey('customer_service', $paperEvaluation->referencia_iii_conditional);
        $customerService = $paperEvaluation->referencia_iii_conditional['customer_service'];

        $this->assertEquals(true, $customerService['condition']);
        $this->assertEquals('A', $customerService['65']);
        $this->assertEquals('B', $customerService['66']);
        $this->assertEquals('C', $customerService['67']);
        $this->assertEquals('D', $customerService['68']);
    }

    public function test_extracts_management_conditional_with_false_condition(): void
    {
        $submissionStatus = SubmissionStatus::create([
            'folio' => '020010013',
            'personal_id' => '0013',
            'organization_id' => $this->organization->id,
            'quiz_id' => $this->quiz->id,
            'status' => SubmissionStatus::STATUS_PENDING,
            'data_snapshot' => [
                'evaluation_type' => 'referencia_v',
                'referencia_v' => ['sexo' => 'Masculino', 'edad' => '40'],
                'referencia_iii' => [
                    '1' => 'A',
                    'management' => [
                        'condition' => false,
                    ],
                ],
            ],
        ]);

        ProcessOnlineEvaluation::dispatchSync($submissionStatus->id);

        $paperEvaluation = PaperEvaluation::where('folio', '020010013')->first();
        $this->assertNotNull($paperEvaluation);
        $this->assertNotNull($paperEvaluation->referencia_iii_conditional);

        // Verify management with condition false does NOT include answers
        $this->assertArrayHasKey('management', $paperEvaluation->referencia_iii_conditional);
        $management = $paperEvaluation->referencia_iii_conditional['management'];

        $this->assertEquals(false, $management['condition']);
        $this->assertArrayNotHasKey('69', $management);
        $this->assertArrayNotHasKey('70', $management);
        $this->assertArrayNotHasKey('71', $management);
        $this->assertArrayNotHasKey('72', $management);
    }

    public function test_does_not_store_conditionals_when_not_present(): void
    {
        $submissionStatus = SubmissionStatus::create([
            'folio' => '020010014',
            'personal_id' => '0014',
            'organization_id' => $this->organization->id,
            'quiz_id' => $this->quiz->id,
            'status' => SubmissionStatus::STATUS_PENDING,
            'data_snapshot' => [
                'evaluation_type' => 'referencia_v',
                'referencia_v' => ['sexo' => 'Femenino', 'edad' => '25'],
                'referencia_iii' => [
                    '1' => 'A',
                    '2' => 'B',
                ],
            ],
        ]);

        ProcessOnlineEvaluation::dispatchSync($submissionStatus->id);

        $paperEvaluation = PaperEvaluation::where('folio', '020010014')->first();
        $this->assertNotNull($paperEvaluation);

        // Verify referencia_iii_conditional is null when sections don't exist
        $this->assertNull($paperEvaluation->referencia_iii_conditional);
    }

    public function test_raw_data_includes_all_quiz_sections(): void
    {
        $submissionStatus = SubmissionStatus::create([
            'folio' => '020010015',
            'personal_id' => '0015',
            'organization_id' => $this->organization->id,
            'quiz_id' => $this->quiz->id,
            'status' => SubmissionStatus::STATUS_PENDING,
            'data_snapshot' => [
                'evaluation_type' => 'referencia_v',
                'referencia_v' => ['sexo' => 'Masculino', 'edad' => '38'],
                'referencia_i' => ['1' => true, '2' => false],
                'referencia_iii' => [
                    '1' => 'A',
                    'ats_s1' => ['1' => true, '2' => false],
                    'customer_service' => ['condition' => true, '65' => 'B'],
                ],
            ],
        ]);

        ProcessOnlineEvaluation::dispatchSync($submissionStatus->id);

        $paperEvaluation = PaperEvaluation::where('folio', '020010015')->first();
        $this->assertNotNull($paperEvaluation);
        $this->assertNotNull($paperEvaluation->raw_data);

        $rawData = $paperEvaluation->raw_data;

        // Verify raw_data includes all sections
        $this->assertArrayHasKey('referencia_i', $rawData);
        $this->assertArrayHasKey('referencia_iii', $rawData);
        $this->assertArrayHasKey('referencia_v', $rawData);

        // Verify referencia_iii contains nested structures
        $this->assertArrayHasKey('ats_s1', $rawData['referencia_iii']);
        $this->assertArrayHasKey('customer_service', $rawData['referencia_iii']);

        // Verify source metadata
        $this->assertEquals('online', $rawData['source']);
        $this->assertArrayHasKey('source_metadata', $rawData);
    }
}
