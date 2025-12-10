<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\PaperEvaluation;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class LikertExportByLevelTest extends TestCase
{
    use DatabaseTransactions;

    protected User $admin;

    protected Organization $organization;

    protected function setUp(): void
    {
        parent::setUp();

        $this->organization = Organization::factory()->create();

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');
    }

    public function test_export_by_level_route_exists(): void
    {
        // Create Likert evaluations
        PaperEvaluation::factory()
            ->likert()
            ->count(3)
            ->create([
                'organization_id' => $this->organization->id,
            ]);

        // Try all levels to ensure we hit at least one
        $response = $this->actingAs($this->admin)
            ->postJson(route('organization.likert.export-by-level', $this->organization->id), [
                'levels' => ['Totalmente de Acuerdo', 'De Acuerdo', 'Desacuerdo', 'Totalmente Desacuerdo'],
            ]);

        $response->assertStatus(200);
    }

    public function test_export_by_level_requires_admin_role(): void
    {
        $regularUser = User::factory()->create([
            'organization_id' => $this->organization->id,
        ]);
        $regularUser->assignRole('organization');

        $response = $this->actingAs($regularUser)
            ->postJson(route('organization.likert.export-by-level', $this->organization->id), [
                'levels' => ['Totalmente de Acuerdo'],
            ]);

        $response->assertStatus(403);
    }

    public function test_export_by_level_requires_at_least_one_level(): void
    {
        $response = $this->actingAs($this->admin)
            ->postJson(route('organization.likert.export-by-level', $this->organization->id), [
                'levels' => [],
            ]);

        $response->assertStatus(422)
            ->assertJsonFragment(['error' => 'Debe seleccionar al menos un nivel']);
    }

    public function test_export_by_level_returns_excel_file(): void
    {
        // Create Likert evaluations with specific scores
        PaperEvaluation::factory()
            ->likert()
            ->count(5)
            ->create([
                'organization_id' => $this->organization->id,
            ]);

        $response = $this->actingAs($this->admin)
            ->postJson(route('organization.likert.export-by-level', $this->organization->id), [
                'levels' => ['Totalmente de Acuerdo', 'De Acuerdo', 'Desacuerdo', 'Totalmente Desacuerdo'],
            ]);

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    public function test_export_by_level_returns_404_when_no_matching_evaluations(): void
    {
        // No evaluations created, cache will return empty data
        $response = $this->actingAs($this->admin)
            ->postJson(route('organization.likert.export-by-level', $this->organization->id), [
                'levels' => ['Totalmente de Acuerdo'],
            ]);

        $response->assertStatus(404)
            ->assertJsonFragment(['error' => 'No se encontraron evaluaciones con los niveles seleccionados']);
    }
}
