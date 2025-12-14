<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\PaperEvaluation;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LikertAnswersExportTest extends TestCase
{
    use DatabaseTransactions;

    protected User $adminUser;

    protected Organization $organization;

    protected function setUp(): void
    {
        parent::setUp();

        // Create admin user
        $this->adminUser = User::factory()->create([
            'email' => 'admin-export-test@example.com',
            'password' => Hash::make('password'),
        ]);
        $this->adminUser->assignRole('admin');

        // Create organization
        $this->organization = Organization::factory()->create([
            'name' => 'Test Organization Export',
        ]);
    }

    /**
     * Test that admin users can access the export route
     */
    public function test_admin_can_access_likert_answers_export(): void
    {
        // Create a likert evaluation for this organization
        PaperEvaluation::create([
            'folio' => '060990001',
            'evaluation_type_code' => '06',
            'organization_code' => '099',
            'personal_folio' => '0001',
            'organization_id' => $this->organization->id,
            'evaluation_type' => 'likert',
            'source' => 'paper',
            'processing_status' => 'completed',
            'likert_answers' => [
                'genero' => 'masculino',
                'tipo_contrato' => 'Sindicalizado',
                'turno' => 'TURNO A',
                'puestos' => 1,
                'areas' => 1,
                'questions' => [
                    '1' => 'A',
                    '2' => 'B',
                    '3' => 'C',
                    '4' => 'D',
                    '5' => 'A',
                    '6' => 'B',
                    '7' => 'C',
                    '8' => 'D',
                    '9' => 'A',
                    '10' => 'B',
                    '11' => 'C',
                    '12' => 'D',
                    '13' => 'A',
                    '14' => 'B',
                    '15' => 'C',
                    '16' => 'D',
                    '17' => 'A',
                    '18' => 'B',
                    '19' => 'C',
                    '20' => 'D',
                    '21' => 'A',
                    '22' => 'B',
                    '23' => 'C',
                ],
            ],
        ]);

        $response = $this->actingAs($this->adminUser)
            ->get(route('organizations.export-likert-answers', $this->organization->id));

        $response->assertStatus(200);
        $response->assertDownload();
    }

    /**
     * Test that unauthenticated users cannot access the export
     */
    public function test_unauthenticated_user_cannot_access_export(): void
    {
        $response = $this->get(route('organizations.export-likert-answers', $this->organization->id));

        $response->assertRedirect(route('login'));
    }
}
