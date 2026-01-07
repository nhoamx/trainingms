<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\AssetInspection;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class AssetInspectionNewFormatTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * Test que verifica el formato correcto del JSON al guardar una inspección.
     */
    public function test_inspection_stores_with_new_format_including_status_and_weight(): void
    {
        // Crear usuario con rol de admin/inspector
        $user = User::factory()->create();
        $user->assignRole('admin');

        // Crear organización y asset
        $organization = Organization::factory()->create();
        $asset = Asset::factory()->create([
            'organization_id' => $organization->id,
            'asset_category' => 'extintor',
        ]);

        // Datos de la inspección con el nuevo formato
        $inspectionData = [
            'inspector_name' => 'Juan Pérez Inspector',
            'inspection_date' => '2026-01-07',
            'extinguisher_weight' => '4.5 kg',
            'checklist_results' => [
                '1' => [
                    'date' => '2026-01-07',
                    'status' => 'ok',
                    'result' => '',
                ],
                '2' => [
                    'date' => '2026-01-07',
                    'status' => 'issue',
                    'result' => 'Se encontró fuga en la válvula',
                ],
                '3' => [
                    'date' => '2026-01-07',
                    'status' => 'ok',
                    'result' => '',
                ],
            ],
            'anomalies_followup' => 'Se requiere cambio de válvula en item 2',
        ];

        // Enviar request
        $response = $this->actingAs($user)->post(
            route('assets.inspections.store', $asset),
            $inspectionData
        );

        // Verificar redirección exitosa
        $response->assertRedirect(route('assets.inspect', $asset));
        $response->assertSessionHas('success');

        // Verificar que se guardó en la base de datos
        $this->assertDatabaseHas('asset_inspections', [
            'asset_id' => $asset->id,
            'inspector_name' => 'Juan Pérez Inspector',
            'inspection_date' => '2026-01-07',
            'extinguisher_weight' => '4.5 kg',
        ]);

        // Obtener la inspección guardada
        $inspection = AssetInspection::where('asset_id', $asset->id)
            ->where('inspector_name', 'Juan Pérez Inspector')
            ->first();

        $this->assertNotNull($inspection);

        // Verificar el formato del JSON checklist_results
        $checklistResults = $inspection->checklist_results;

        $this->assertIsArray($checklistResults);
        $this->assertArrayHasKey('1', $checklistResults);
        $this->assertArrayHasKey('2', $checklistResults);
        $this->assertArrayHasKey('3', $checklistResults);

        // Verificar item con status 'ok'
        $this->assertEquals('2026-01-07', $checklistResults['1']['date']);
        $this->assertEquals('ok', $checklistResults['1']['status']);
        $this->assertEquals('', $checklistResults['1']['result']);

        // Verificar item con status 'issue'
        $this->assertEquals('2026-01-07', $checklistResults['2']['date']);
        $this->assertEquals('issue', $checklistResults['2']['status']);
        $this->assertEquals('Se encontró fuga en la válvula', $checklistResults['2']['result']);

        // Verificar anomalies_followup
        $this->assertEquals('Se requiere cambio de válvula en item 2', $inspection->anomalies_followup);
    }

    /**
     * Test que verifica que el campo extinguisher_weight es opcional.
     */
    public function test_inspection_can_be_created_without_weight(): void
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        $organization = Organization::factory()->create();
        $asset = Asset::factory()->create([
            'organization_id' => $organization->id,
            'asset_category' => 'extintor',
        ]);

        $inspectionData = [
            'inspector_name' => 'María López',
            'inspection_date' => '2026-01-07',
            'extinguisher_weight' => '', // Vacío
            'checklist_results' => [
                '1' => [
                    'date' => '2026-01-07',
                    'status' => 'ok',
                    'result' => '',
                ],
            ],
            'anomalies_followup' => '',
        ];

        $response = $this->actingAs($user)->post(
            route('assets.inspections.store', $asset),
            $inspectionData
        );

        $response->assertRedirect(route('assets.inspect', $asset));

        $this->assertDatabaseHas('asset_inspections', [
            'asset_id' => $asset->id,
            'inspector_name' => 'María López',
        ]);
    }

    /**
     * Test que verifica validación de status solo permite 'ok' o 'issue'.
     */
    public function test_inspection_validates_status_values(): void
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        $organization = Organization::factory()->create();
        $asset = Asset::factory()->create([
            'organization_id' => $organization->id,
            'asset_category' => 'extintor',
        ]);

        $inspectionData = [
            'inspector_name' => 'Pedro Sánchez',
            'inspection_date' => '2026-01-07',
            'checklist_results' => [
                '1' => [
                    'date' => '2026-01-07',
                    'status' => 'invalid_status', // Status inválido
                    'result' => '',
                ],
            ],
        ];

        $response = $this->actingAs($user)->post(
            route('assets.inspections.store', $asset),
            $inspectionData
        );

        $response->assertSessionHasErrors('checklist_results.1.status');
    }
}
