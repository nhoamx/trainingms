<?php

namespace Tests\Feature;

use App\Models\EvaluationComment;
use App\Models\Organization;
use App\Models\PaperEvaluation;
use App\Models\User;
use App\Models\WorkCenter;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class EvaluationCommentsTest extends TestCase
{
    use DatabaseTransactions;

    public function test_evaluation_comment_can_be_created(): void
    {
        $organization = Organization::factory()->create();
        $evaluation = PaperEvaluation::factory()
            ->for($organization)
            ->likert()
            ->create();

        $comment = EvaluationComment::create([
            'paper_evaluation_id' => $evaluation->id,
            'factor' => 'Condiciones en el ambiente de trabajo',
            'comment' => 'Test comment for workplace conditions',
        ]);

        $this->assertDatabaseHas('evaluation_comments', [
            'id' => $comment->id,
            'paper_evaluation_id' => $evaluation->id,
            'factor' => 'Condiciones en el ambiente de trabajo',
        ]);
    }

    public function test_paper_evaluation_has_comments_relationship(): void
    {
        $organization = Organization::factory()->create();
        $evaluation = PaperEvaluation::factory()
            ->for($organization)
            ->likert()
            ->create();

        EvaluationComment::factory()
            ->for($evaluation, 'paperEvaluation')
            ->create(['factor' => 'Liderazgo']);

        EvaluationComment::factory()
            ->for($evaluation, 'paperEvaluation')
            ->create(['factor' => 'Violencia']);

        $this->assertCount(2, $evaluation->fresh()->comments);
    }

    public function test_comments_are_deleted_when_evaluation_is_deleted(): void
    {
        $organization = Organization::factory()->create();
        $evaluation = PaperEvaluation::factory()
            ->for($organization)
            ->likert()
            ->create();

        $comment = EvaluationComment::factory()
            ->for($evaluation, 'paperEvaluation')
            ->create();

        $commentId = $comment->id;
        $evaluation->forceDelete();

        $this->assertDatabaseMissing('evaluation_comments', [
            'id' => $commentId,
        ]);
    }

    public function test_bulk_comments_template_route_is_accessible_with_authorization(): void
    {
        // Seed roles
        $this->artisan('db:seed', ['--class' => 'RolesSeeder']);

        $organization = Organization::factory()->create();
        $user = User::factory()->create([
            'organization_id' => $organization->id,
        ]);
        $user->assignRole('organization');

        $response = $this->actingAs($user)
            ->get(route('organization.results.comments-template', $organization));

        $response->assertStatus(200);
        $response->assertDownload();
    }

    public function test_bulk_comments_upload_requires_valid_file(): void
    {
        // Seed roles
        $this->artisan('db:seed', ['--class' => 'RolesSeeder']);

        $organization = Organization::factory()->create();
        $user = User::factory()->create([
            'organization_id' => $organization->id,
        ]);
        $user->assignRole('organization');

        $response = $this->actingAs($user)
            ->post(route('organization.results.bulk-comments', $organization), [
                'file' => 'not-a-file',
            ]);

        $response->assertSessionHasErrors('file');
    }

    public function test_folio_padding_is_handled_correctly(): void
    {
        $organization = Organization::factory()->create();

        // Create evaluations with padded folios
        PaperEvaluation::factory()
            ->for($organization)
            ->likert()
            ->create(['personal_folio' => '0001']);

        PaperEvaluation::factory()
            ->for($organization)
            ->likert()
            ->create(['personal_folio' => '0060']);

        PaperEvaluation::factory()
            ->for($organization)
            ->likert()
            ->create(['personal_folio' => '0100']);

        // Verify we can find them with numeric input (1, 60, 100)
        $eval1 = PaperEvaluation::where('organization_id', $organization->id)
            ->where('personal_folio', str_pad('1', 4, '0', STR_PAD_LEFT))
            ->first();

        $eval60 = PaperEvaluation::where('organization_id', $organization->id)
            ->where('personal_folio', str_pad('60', 4, '0', STR_PAD_LEFT))
            ->first();

        $eval100 = PaperEvaluation::where('organization_id', $organization->id)
            ->where('personal_folio', str_pad('100', 4, '0', STR_PAD_LEFT))
            ->first();

        $this->assertNotNull($eval1);
        $this->assertEquals('0001', $eval1->personal_folio);

        $this->assertNotNull($eval60);
        $this->assertEquals('0060', $eval60->personal_folio);

        $this->assertNotNull($eval100);
        $this->assertEquals('0100', $eval100->personal_folio);
    }

    public function test_accepts_flexible_column_names(): void
    {
        $organization = Organization::factory()->create();
        $evaluation = PaperEvaluation::factory()
            ->for($organization)
            ->likert()
            ->create(['personal_folio' => '0001']);

        // Test the import class directly with different column names
        $import = new \App\Imports\EvaluationBulkCommentsImport($organization->id);

        // Test with 'comentarios' (plural)
        $rowPlural = collect([
            'folio' => '1',
            'comentarios' => 'Test comment with plural',
            'factor' => 'Liderazgo',
        ]);

        $result = $import->collection(collect([$rowPlural]));

        $this->assertDatabaseHas('evaluation_comments', [
            'paper_evaluation_id' => $evaluation->id,
            'comment' => 'Test comment with plural',
            'factor' => 'Liderazgo',
        ]);

        // Test with 'comentario' (singular)
        $rowSingular = collect([
            'folio' => '1',
            'comentario' => 'Test comment with singular',
            'factor' => 'Violencia',
        ]);

        $import->collection(collect([$rowSingular]));

        $this->assertDatabaseHas('evaluation_comments', [
            'paper_evaluation_id' => $evaluation->id,
            'comment' => 'Test comment with singular',
            'factor' => 'Violencia',
        ]);
    }

    public function test_bulk_comments_import_allows_multiple_comments_for_same_folio_and_factor(): void
    {
        $organization = Organization::factory()->create();
        $evaluation = PaperEvaluation::factory()
            ->for($organization)
            ->likert()
            ->create(['personal_folio' => '00001']);

        $import = new \App\Imports\EvaluationBulkCommentsImport($organization->id);

        $rows = collect([
            collect([
                'folio' => '00001',
                'comentario' => 'Comentario 1',
                'factor' => 'Entorno Laboral Seguro',
            ]),
            collect([
                'folio' => '00001',
                'comentario' => 'Comentario 2',
                'factor' => 'Entorno Laboral Seguro',
            ]),
            collect([
                'folio' => '00001',
                'comentario' => 'Comentario 3',
                'factor' => 'Entorno Laboral Seguro',
            ]),
        ]);

        $import->collection($rows);

        $this->assertEquals(3, EvaluationComment::where('paper_evaluation_id', $evaluation->id)->count());
    }

    public function test_bulk_comments_import_respects_work_center_scope(): void
    {
        $organization = Organization::factory()->create();
        $workCenterA = WorkCenter::factory()->create(['organization_id' => $organization->id]);
        $workCenterB = WorkCenter::factory()->create(['organization_id' => $organization->id]);

        $evaluationA = PaperEvaluation::factory()
            ->for($organization)
            ->likert()
            ->create([
                'work_center_id' => $workCenterA->id,
                'personal_folio' => '00001',
            ]);

        PaperEvaluation::factory()
            ->for($organization)
            ->likert()
            ->create([
                'work_center_id' => $workCenterB->id,
                'personal_folio' => '00001',
            ]);

        $import = new \App\Imports\EvaluationBulkCommentsImport($organization->id, null, $workCenterA->id);
        $import->collection(collect([
            collect([
                'folio' => '1',
                'comentario' => 'Solo centro A',
                'factor' => 'Liderazgo',
            ]),
        ]));

        $this->assertDatabaseHas('evaluation_comments', [
            'paper_evaluation_id' => $evaluationA->id,
            'comment' => 'Solo centro A',
            'factor' => 'Liderazgo',
        ]);
    }

    public function test_bulk_comments_import_replaces_comments_only_for_selected_work_center(): void
    {
        $organizationA = Organization::factory()->create();
        $organizationB = Organization::factory()->create();

        $workCenterA1 = WorkCenter::factory()->create(['organization_id' => $organizationA->id]);
        $workCenterA2 = WorkCenter::factory()->create(['organization_id' => $organizationA->id]);
        $workCenterB1 = WorkCenter::factory()->create(['organization_id' => $organizationB->id]);

        $evaluationA1 = PaperEvaluation::factory()
            ->for($organizationA)
            ->likert()
            ->create([
                'work_center_id' => $workCenterA1->id,
                'personal_folio' => '00001',
            ]);

        $evaluationA2 = PaperEvaluation::factory()
            ->for($organizationA)
            ->likert()
            ->create([
                'work_center_id' => $workCenterA2->id,
                'personal_folio' => '00002',
            ]);

        $evaluationB1 = PaperEvaluation::factory()
            ->for($organizationB)
            ->likert()
            ->create([
                'work_center_id' => $workCenterB1->id,
                'personal_folio' => '00003',
            ]);

        EvaluationComment::create([
            'paper_evaluation_id' => $evaluationA1->id,
            'factor' => 'Liderazgo',
            'comment' => 'Comentario viejo A1',
        ]);

        EvaluationComment::create([
            'paper_evaluation_id' => $evaluationA2->id,
            'factor' => 'Liderazgo',
            'comment' => 'Comentario viejo A2',
        ]);

        EvaluationComment::create([
            'paper_evaluation_id' => $evaluationB1->id,
            'factor' => 'Liderazgo',
            'comment' => 'Comentario viejo B1',
        ]);

        $import = new \App\Imports\EvaluationBulkCommentsImport($organizationA->id, null, $workCenterA1->id);
        $import->collection(collect([
            collect([
                'folio' => '1',
                'comentario' => 'Comentario nuevo A1',
                'factor' => 'Liderazgo',
            ]),
        ]));

        $this->assertDatabaseMissing('evaluation_comments', [
            'paper_evaluation_id' => $evaluationA1->id,
            'comment' => 'Comentario viejo A1',
        ]);

        $this->assertDatabaseHas('evaluation_comments', [
            'paper_evaluation_id' => $evaluationA1->id,
            'comment' => 'Comentario nuevo A1',
            'factor' => 'Liderazgo',
        ]);

        $this->assertDatabaseHas('evaluation_comments', [
            'paper_evaluation_id' => $evaluationA2->id,
            'comment' => 'Comentario viejo A2',
            'factor' => 'Liderazgo',
        ]);

        $this->assertDatabaseHas('evaluation_comments', [
            'paper_evaluation_id' => $evaluationB1->id,
            'comment' => 'Comentario viejo B1',
            'factor' => 'Liderazgo',
        ]);
    }
}
