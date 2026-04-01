<?php

namespace Tests\Feature;

use App\Exports\ClimaLaboralCompactExport;
use App\Models\Organization;
use App\Models\PaperEvaluation;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ClimaLaboralCompactExportTest extends TestCase
{
    use DatabaseTransactions;

    protected User $admin;

    protected User $orgUser;

    protected Organization $organization;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed roles
        $this->artisan('db:seed', ['--class' => 'RolesSeeder']);

        $this->organization = Organization::factory()->create();

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        $this->orgUser = User::factory()->create([
            'organization_id' => $this->organization->id,
        ]);
        $this->orgUser->assignRole('organization');
    }

    public function test_export_compact_route_exists(): void
    {
        PaperEvaluation::factory()
            ->likert()
            ->count(3)
            ->create([
                'organization_id' => $this->organization->id,
            ]);

        $response = $this->actingAs($this->admin)
            ->post(route('organization.clima-laboral.export-compact', $this->organization->id));

        $response->assertStatus(200);
    }

    public function test_export_compact_works_for_admin(): void
    {
        PaperEvaluation::factory()
            ->likert()
            ->count(5)
            ->create([
                'organization_id' => $this->organization->id,
            ]);

        $response = $this->actingAs($this->admin)
            ->post(route('organization.clima-laboral.export-compact', $this->organization->id));

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    public function test_export_compact_is_forbidden_for_organization_user(): void
    {
        PaperEvaluation::factory()
            ->likert()
            ->count(3)
            ->create([
                'organization_id' => $this->organization->id,
            ]);

        $response = $this->actingAs($this->orgUser)
            ->post(route('organization.clima-laboral.export-compact', $this->organization->id));

        $response->assertStatus(403);
    }

    public function test_export_compact_returns_excel_file(): void
    {
        PaperEvaluation::factory()
            ->likert()
            ->count(10)
            ->create([
                'organization_id' => $this->organization->id,
            ]);

        $response = $this->actingAs($this->admin)
            ->post(route('organization.clima-laboral.export-compact', $this->organization->id));

        $response->assertStatus(200);
        $this->assertStringContainsString(
            'clima_laboral_',
            $response->headers->get('content-disposition')
        );
    }

    public function test_export_compact_returns_404_when_no_evaluations(): void
    {
        $response = $this->actingAs($this->admin)
            ->post(route('organization.clima-laboral.export-compact', $this->organization->id));

        $response->assertStatus(404)
            ->assertJson(['error' => 'No se encontraron evaluaciones de clima laboral']);
    }

    public function test_unauthorized_user_cannot_export(): void
    {
        $unauthorizedUser = User::factory()->create();
        $unauthorizedUser->assignRole('organization');

        PaperEvaluation::factory()
            ->likert()
            ->count(3)
            ->create([
                'organization_id' => $this->organization->id,
            ]);

        $response = $this->actingAs($unauthorizedUser)
            ->post(route('organization.clima-laboral.export-compact', $this->organization->id));

        $response->assertStatus(403);
    }

    public function test_compact_export_headings_include_evaluee_name(): void
    {
        $headings = (new ClimaLaboralCompactExport([]))->headings();

        $this->assertSame('Folio', $headings[0]);
        $this->assertSame('Nombre del Evaluado', $headings[1]);
    }
}
