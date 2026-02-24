<?php

namespace Tests\Feature;

use App\Jobs\ProcessOnlineEvaluation;
use App\Models\Organization;
use App\Models\PaperEvaluation;
use App\Models\Quiz;
use App\Models\SubmissionStatus;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Test dual-instrument record creation pattern (Referencia III + Referencia I)
 * Validates that online submissions replicate OCR pattern: 2 separate records
 */
class DualInstrumentRecordCreationTest extends TestCase
{
    use DatabaseTransactions;

    protected Organization $organization;

    protected Quiz $quiz;

    protected function setUp(): void
    {
        parent::setUp();

        $this->organization = Organization::factory()->create([
            'folio_organization' => '02',
        ]);
        $this->quiz = Quiz::factory()->create([
            'organization_id' => $this->organization->id,
        ]);
    }

    public function test_creates_two_separate_records_for_complete_quiz_with_traumatic_events(): void
    {
        // Simulate complete quiz (Ref III + traumatic events → triggers Ref I)
        $submissionStatus = SubmissionStatus::create([
            'folio' => '020010001', // Type 02 = Ref III
            'personal_id' => '0001',
            'organization_id' => $this->organization->id,
            'quiz_id' => $this->quiz->id,
            'status' => SubmissionStatus::STATUS_PENDING,
            'data_snapshot' => [
                'referencia_v' => [
                    'sexo' => 'Masculino',
                    'edad' => '30-39',
                ],
                'referencia_iii' => [
                    '1' => 'A',
                    '2' => 'B',
                    '3' => 'C',
                ],
                'referencia_i' => [
                    // ✅ UNIFIED: ATS now lives in referencia_i
                    'acontecimientos_traumaticos' => [
                        '1' => true, // Traumatic event "Sí"
                        '2' => false,
                        '3' => false,
                        '4' => false,
                        '5' => false,
                        '6' => false,
                    ],
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
                ],
                'evaluee_name' => 'Juan Pérez',
            ],
        ]);

        // Process submission
        ProcessOnlineEvaluation::dispatchSync($submissionStatus->id);

        // Assert: 2 records created
        $evaluations = PaperEvaluation::where('organization_id', $this->organization->id)
            ->orderBy('evaluation_type')
            ->get();

        $this->assertCount(2, $evaluations, 'Should create 2 separate records');

        // Assert: First record is Referencia I
        $refI = $evaluations[0];
        $this->assertEquals('referencia_i', $refI->evaluation_type);
        $this->assertEquals('01', $refI->evaluation_type_code);
        $this->assertEquals('010010001', $refI->folio); // Type 01 for Ref I
        $this->assertNotNull($refI->referencia_i_answers);
        $this->assertNull($refI->referencia_iii_answers, 'Ref I record should NOT have Ref III answers');
        $this->assertNotNull($refI->citsats_s1, 'Ref I record should have traumatic events context');
        $this->assertEquals('Juan Pérez', $refI->evaluee_name, 'Ref I record should have evaluee name');

        // Assert: Second record is Referencia III
        $refIII = $evaluations[1];
        $this->assertEquals('referencia_iii', $refIII->evaluation_type);
        $this->assertEquals('02', $refIII->evaluation_type_code);
        $this->assertEquals('020010001', $refIII->folio); // Original folio
        $this->assertNotNull($refIII->referencia_iii_answers);
        $this->assertNull($refIII->referencia_i_answers, 'Ref III record should NOT have Ref I answers');
        $this->assertNotNull($refIII->citsats_s1, 'Ref III record should have traumatic events');
        $this->assertNull($refIII->evaluee_name, 'Ref III record should NOT have evaluee name');

        // Assert: Records are linked bidirectionally
        $this->assertEquals($refI->folio, $refIII->related_evaluation_folio);
        $this->assertEquals($refIII->folio, $refI->related_evaluation_folio);

        // Assert: Both have same personal_folio (different types, same person)
        $this->assertEquals($refI->personal_folio, $refIII->personal_folio);
    }

    public function test_creates_single_record_for_reduced_quiz_without_ref_iii(): void
    {
        // Simulate reduced quiz (only Ref I, no Ref III)
        $submissionStatus = SubmissionStatus::create([
            'folio' => '010020002', // Type 01 = Ref I
            'personal_id' => '0002',
            'organization_id' => $this->organization->id,
            'quiz_id' => $this->quiz->id,
            'status' => SubmissionStatus::STATUS_PENDING,
            'data_snapshot' => [
                'referencia_v' => [
                    'sexo' => 'Femenino',
                    'edad' => '25-29',
                ],
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
                ],
                'evaluee_name' => 'María García',
            ],
        ]);

        // Process submission
        ProcessOnlineEvaluation::dispatchSync($submissionStatus->id);

        // Assert: Only 1 record created
        $evaluations = PaperEvaluation::where('organization_id', $this->organization->id)->get();
        $this->assertCount(1, $evaluations, 'Should create only 1 record for reduced quiz');

        $evaluation = $evaluations[0];
        $this->assertEquals('referencia_i', $evaluation->evaluation_type);
        $this->assertEquals('010020002', $evaluation->folio);
        $this->assertNotNull($evaluation->referencia_i_answers);
        $this->assertNull($evaluation->referencia_iii_answers);
        $this->assertEquals('María García', $evaluation->evaluee_name);
        $this->assertNull($evaluation->related_evaluation_folio, 'Single record should not have related folio');
    }

    public function test_creates_two_records_for_complete_quiz_even_when_traumatic_events_are_all_no(): void
    {
        // Simulate complete quiz with ATS all "No" (Ref I should still be created)
        $submissionStatus = SubmissionStatus::create([
            'folio' => '020010003', // Type 02 = Ref III
            'personal_id' => '0003',
            'organization_id' => $this->organization->id,
            'quiz_id' => $this->quiz->id,
            'status' => SubmissionStatus::STATUS_PENDING,
            'data_snapshot' => [
                'referencia_v' => [
                    'sexo' => 'Masculino',
                    'edad' => '40-49',
                ],
                'referencia_iii' => [
                    '1' => 'A',
                    '2' => 'B',
                ],
                'referencia_i' => [
                    // ✅ UNIFIED: ATS now lives in referencia_i
                    'acontecimientos_traumaticos' => [
                        '1' => false, // All "No"
                        '2' => false,
                        '3' => false,
                        '4' => false,
                        '5' => false,
                        '6' => false,
                    ],
                ],
            ],
        ]);

        // Process submission
        ProcessOnlineEvaluation::dispatchSync($submissionStatus->id);

        // Assert: 2 records created (Ref III + Ref I)
        $evaluations = PaperEvaluation::where('organization_id', $this->organization->id)
            ->orderBy('evaluation_type')
            ->get();
        $this->assertCount(2, $evaluations, 'Should create 2 records even when ATS are all No');

        $refI = $evaluations[0];
        $this->assertEquals('referencia_i', $refI->evaluation_type);
        $this->assertEquals('010010003', $refI->folio);
        $this->assertNull($refI->referencia_i_answers, 'Ref I follow-up answers can be null when ATS are all No');
        $this->assertNotNull($refI->citsats_s1);

        $refIII = $evaluations[1];
        $this->assertEquals('referencia_iii', $refIII->evaluation_type);
        $this->assertEquals('020010003', $refIII->folio);
        $this->assertNotNull($refIII->referencia_iii_answers);
        $this->assertNull($refIII->referencia_i_answers);
        $this->assertNotNull($refIII->citsats_s1);

        $this->assertEquals($refI->folio, $refIII->related_evaluation_folio);
        $this->assertEquals($refIII->folio, $refI->related_evaluation_folio);
    }

    public function test_both_records_have_complete_raw_data_for_audit_trail(): void
    {
        // Simulate complete quiz with both instruments
        $submissionStatus = SubmissionStatus::create([
            'folio' => '020010004',
            'personal_id' => '0004',
            'organization_id' => $this->organization->id,
            'quiz_id' => $this->quiz->id,
            'status' => SubmissionStatus::STATUS_PENDING,
            'data_snapshot' => [
                'referencia_v' => ['sexo' => 'Masculino'],
                'referencia_iii' => [
                    '1' => 'A',
                ],
                'referencia_i' => [
                    // ✅ UNIFIED: ATS now lives in referencia_i
                    'acontecimientos_traumaticos' => ['1' => true, '2' => false, '3' => false, '4' => false, '5' => false, '6' => false],
                    '1' => true,
                    '2' => false,
                ],
                'evaluee_name' => 'Pedro López',
            ],
        ]);

        ProcessOnlineEvaluation::dispatchSync($submissionStatus->id);

        $evaluations = PaperEvaluation::where('organization_id', $this->organization->id)->get();
        $this->assertCount(2, $evaluations);

        // Both records should have COMPLETE raw_data (audit trail)
        foreach ($evaluations as $evaluation) {
            $this->assertNotNull($evaluation->raw_data);
            $this->assertArrayHasKey('referencia_i', $evaluation->raw_data);
            $this->assertArrayHasKey('referencia_iii', $evaluation->raw_data);
            $this->assertArrayHasKey('referencia_v', $evaluation->raw_data);
            $this->assertArrayHasKey('source_metadata', $evaluation->raw_data);
            $this->assertEquals('online', $evaluation->raw_data['source']);
        }
    }
}
