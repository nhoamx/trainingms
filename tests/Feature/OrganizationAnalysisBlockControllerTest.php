<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\OrganizationAnalysisBlock;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class OrganizationAnalysisBlockControllerTest extends TestCase
{
    use DatabaseTransactions;

    public function test_admin_can_store_organization_analysis_block(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create();
        $user->syncRoles(['admin']);

        $response = $this->actingAs($user)->post(route('organization.analysis-blocks.store', $organization), [
            'instrument_type' => 'referencia_iii',
            'title' => 'Tabla de hallazgos por área',
            'content_html' => '<table><tr><th>Área</th><th>Riesgo</th></tr><tr><td>Operación</td><td>Alto</td></tr></table>',
            'sort_order' => 2,
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('organization_analysis_blocks', [
            'organization_id' => $organization->id,
            'instrument_type' => 'referencia_iii',
            'title' => 'Tabla de hallazgos por área',
            'sort_order' => 2,
        ]);
    }

    public function test_organization_user_cannot_store_analysis_block(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create();
        $user->syncRoles(['organization']);
        $user->update(['organization_id' => $organization->id]);

        $response = $this->actingAs($user)->post(route('organization.analysis-blocks.store', $organization), [
            'instrument_type' => 'referencia_iii',
            'title' => 'Intento no autorizado',
            'content_html' => '<p>Texto</p>',
            'sort_order' => 0,
        ]);

        $response->assertForbidden();

        $this->assertDatabaseMissing('organization_analysis_blocks', [
            'organization_id' => $organization->id,
            'title' => 'Intento no autorizado',
        ]);
    }

    public function test_admin_can_delete_organization_analysis_block(): void
    {
        $organization = Organization::factory()->create();
        $block = OrganizationAnalysisBlock::query()->create([
            'organization_id' => $organization->id,
            'instrument_type' => 'referencia_i',
            'title' => 'GRI bloque editable',
            'content_html' => '<p>Contenido GRI</p>',
            'sort_order' => 0,
        ]);

        $user = User::factory()->create();
        $user->syncRoles(['admin']);

        $response = $this->actingAs($user)->delete(route('organization.analysis-blocks.destroy', [$organization, $block]));

        $response->assertRedirect();

        $this->assertDatabaseMissing('organization_analysis_blocks', [
            'id' => $block->id,
        ]);
    }
}
