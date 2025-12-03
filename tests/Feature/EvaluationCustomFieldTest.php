<?php

namespace Tests\Feature;

use App\Models\EvaluationCustomField;
use App\Models\Organization;
use App\Models\PaperEvaluation;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class EvaluationCustomFieldTest extends TestCase
{
    use DatabaseTransactions;

    public function test_label_to_key_converts_correctly(): void
    {
        $testCases = [
            'Supervisor' => 'supervisor',
            'Líder de Línea' => 'lider_de_linea',
            'CodigoLinea' => 'codigo_linea',
            'Superintendente' => 'superintendente',
            'Gerente' => 'gerente',
            'Tipo de Empleado' => 'tipo_de_empleado',
            'Número' => 'numero',
            'Nombre completo' => 'nombre_completo',
        ];

        foreach ($testCases as $label => $expectedKey) {
            $this->assertEquals(
                $expectedKey,
                EvaluationCustomField::labelToKey($label),
                "Failed for label: {$label}"
            );
        }
    }

    public function test_can_create_custom_field_for_evaluation(): void
    {
        $organization = Organization::factory()->create();
        $evaluation = PaperEvaluation::factory()->likert()->create([
            'organization_id' => $organization->id,
        ]);

        $customField = EvaluationCustomField::create([
            'paper_evaluation_id' => $evaluation->id,
            'key' => 'supervisor',
            'key_label' => 'Supervisor',
            'value' => 'Juan Pérez',
        ]);

        $this->assertDatabaseHas('evaluation_custom_fields', [
            'paper_evaluation_id' => $evaluation->id,
            'key' => 'supervisor',
            'value' => 'Juan Pérez',
        ]);
    }

    public function test_paper_evaluation_has_custom_fields_relationship(): void
    {
        $organization = Organization::factory()->create();
        $evaluation = PaperEvaluation::factory()->likert()->create([
            'organization_id' => $organization->id,
        ]);

        EvaluationCustomField::create([
            'paper_evaluation_id' => $evaluation->id,
            'key' => 'supervisor',
            'key_label' => 'Supervisor',
            'value' => 'Juan Pérez',
        ]);

        EvaluationCustomField::create([
            'paper_evaluation_id' => $evaluation->id,
            'key' => 'gerente',
            'key_label' => 'Gerente',
            'value' => 'María López',
        ]);

        // Refresh to get the relationship
        $evaluation->refresh();

        $this->assertCount(2, $evaluation->customFields);
        $this->assertEquals('Juan Pérez', $evaluation->getCustomField('supervisor'));
        $this->assertEquals('María López', $evaluation->getCustomField('gerente'));
    }

    public function test_set_custom_field_creates_new_field(): void
    {
        $organization = Organization::factory()->create();
        $evaluation = PaperEvaluation::factory()->likert()->create([
            'organization_id' => $organization->id,
        ]);

        $evaluation->setCustomField('codigo_linea', 'CodigoLinea', '1234');

        $this->assertDatabaseHas('evaluation_custom_fields', [
            'paper_evaluation_id' => $evaluation->id,
            'key' => 'codigo_linea',
            'key_label' => 'CodigoLinea',
            'value' => '1234',
        ]);
    }

    public function test_set_custom_field_updates_existing_field(): void
    {
        $organization = Organization::factory()->create();
        $evaluation = PaperEvaluation::factory()->likert()->create([
            'organization_id' => $organization->id,
        ]);

        // Create initial field
        $evaluation->setCustomField('supervisor', 'Supervisor', 'Juan Pérez');

        // Update the field
        $evaluation->setCustomField('supervisor', 'Supervisor', 'Pedro García');

        // Should only have one record with the updated value
        $this->assertEquals(1, $evaluation->customFields()->count());
        $this->assertDatabaseHas('evaluation_custom_fields', [
            'paper_evaluation_id' => $evaluation->id,
            'key' => 'supervisor',
            'value' => 'Pedro García',
        ]);
    }

    public function test_custom_field_scopes_work(): void
    {
        $organization = Organization::factory()->create();

        $evaluation1 = PaperEvaluation::factory()->likert()->create([
            'organization_id' => $organization->id,
        ]);
        $evaluation1->setCustomField('supervisor', 'Supervisor', 'Juan Pérez');

        $evaluation2 = PaperEvaluation::factory()->likert()->create([
            'organization_id' => $organization->id,
        ]);
        $evaluation2->setCustomField('supervisor', 'Supervisor', 'María López');

        $evaluation3 = PaperEvaluation::factory()->likert()->create([
            'organization_id' => $organization->id,
        ]);
        $evaluation3->setCustomField('gerente', 'Gerente', 'Pedro García');

        // Test byKey scope
        $supervisorFields = EvaluationCustomField::byKey('supervisor')->get();
        $this->assertCount(2, $supervisorFields);

        // Test byKeyValue scope
        $juanFields = EvaluationCustomField::byKeyValue('supervisor', 'Juan Pérez')->get();
        $this->assertCount(1, $juanFields);
    }

    public function test_custom_fields_are_deleted_when_evaluation_is_deleted(): void
    {
        $organization = Organization::factory()->create();
        $evaluation = PaperEvaluation::factory()->likert()->create([
            'organization_id' => $organization->id,
        ]);

        $evaluation->setCustomField('supervisor', 'Supervisor', 'Juan Pérez');
        $evaluation->setCustomField('gerente', 'Gerente', 'María López');

        $evaluationId = $evaluation->id;

        // Force delete the evaluation
        $evaluation->forceDelete();

        // Custom fields should be deleted (cascade)
        $this->assertDatabaseMissing('evaluation_custom_fields', [
            'paper_evaluation_id' => $evaluationId,
        ]);
    }
}
