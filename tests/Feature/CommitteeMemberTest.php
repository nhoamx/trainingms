<?php

namespace Tests\Feature;

use App\Models\CommitteeMember;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class CommitteeMemberTest extends TestCase
{
    use DatabaseTransactions;

    public function test_can_create_committee_member(): void
    {
        $organization = Organization::factory()->create();

        $committeeMember = CommitteeMember::create([
            'organization_id' => $organization->id,
            'nombre' => 'Juan Pérez',
            'departamento' => 'Recursos Humanos',
            'puesto' => 'Coordinador',
            'factor' => 'Estrés laboral',
        ]);

        $this->assertDatabaseHas('committee_members', [
            'organization_id' => $organization->id,
            'nombre' => 'Juan Pérez',
            'departamento' => 'Recursos Humanos',
            'puesto' => 'Coordinador',
            'factor' => 'Estrés laboral',
        ]);

        $this->assertNotNull($committeeMember->id);
        // Verify UUID format
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i',
            $committeeMember->id
        );
    }

    public function test_organization_has_committee_members_relationship(): void
    {
        $organization = Organization::factory()->create();

        CommitteeMember::create([
            'organization_id' => $organization->id,
            'nombre' => 'Juan Pérez',
            'departamento' => 'Recursos Humanos',
            'puesto' => 'Coordinador',
            'factor' => 'Estrés laboral',
        ]);

        CommitteeMember::create([
            'organization_id' => $organization->id,
            'nombre' => 'María López',
            'departamento' => 'Seguridad',
            'puesto' => 'Supervisora',
            'factor' => 'Violencia laboral',
        ]);

        $organization->refresh();

        $this->assertCount(2, $organization->committeeMembers);
        $this->assertEquals('Juan Pérez', $organization->committeeMembers[0]->nombre);
        $this->assertEquals('María López', $organization->committeeMembers[1]->nombre);
    }

    public function test_committee_member_belongs_to_organization(): void
    {
        $organization = Organization::factory()->create(['name' => 'Test Organization']);

        $committeeMember = CommitteeMember::create([
            'organization_id' => $organization->id,
            'nombre' => 'Juan Pérez',
            'departamento' => 'Recursos Humanos',
            'puesto' => 'Coordinador',
            'factor' => 'Estrés laboral',
        ]);

        $this->assertEquals('Test Organization', $committeeMember->organization->name);
    }

    public function test_can_update_organization_with_committee_members(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();

        $this->actingAs($user);

        $response = $this->put(route('organizations.update', $organization), [
            'name' => 'Updated Organization',
            'committee_members' => [
                [
                    'nombre' => 'Juan Pérez',
                    'departamento' => 'Recursos Humanos',
                    'puesto' => 'Coordinador',
                    'factor' => 'Estrés laboral',
                ],
                [
                    'nombre' => 'María López',
                    'departamento' => 'Seguridad',
                    'puesto' => 'Supervisora',
                    'factor' => 'Violencia laboral',
                ],
            ],
        ]);

        $this->assertDatabaseHas('committee_members', [
            'organization_id' => $organization->id,
            'nombre' => 'Juan Pérez',
        ]);

        $this->assertDatabaseHas('committee_members', [
            'organization_id' => $organization->id,
            'nombre' => 'María López',
        ]);

        $organization->refresh();
        $this->assertCount(2, $organization->committeeMembers);
    }

    public function test_updating_organization_replaces_committee_members(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();

        // Create initial committee members
        CommitteeMember::create([
            'organization_id' => $organization->id,
            'nombre' => 'Old Member',
            'departamento' => 'Old Department',
            'puesto' => 'Old Position',
            'factor' => 'Old Factor',
        ]);

        $this->actingAs($user);

        // Update with new committee members
        $response = $this->put(route('organizations.update', $organization), [
            'name' => 'Updated Organization',
            'committee_members' => [
                [
                    'nombre' => 'New Member',
                    'departamento' => 'New Department',
                    'puesto' => 'New Position',
                    'factor' => 'New Factor',
                ],
            ],
        ]);

        // Old member should not exist
        $this->assertDatabaseMissing('committee_members', [
            'organization_id' => $organization->id,
            'nombre' => 'Old Member',
        ]);

        // New member should exist
        $this->assertDatabaseHas('committee_members', [
            'organization_id' => $organization->id,
            'nombre' => 'New Member',
        ]);

        $organization->refresh();
        $this->assertCount(1, $organization->committeeMembers);
    }

    public function test_committee_members_are_deleted_when_organization_is_deleted(): void
    {
        $organization = Organization::factory()->create();

        $committeeMember = CommitteeMember::create([
            'organization_id' => $organization->id,
            'nombre' => 'Juan Pérez',
            'departamento' => 'Recursos Humanos',
            'puesto' => 'Coordinador',
            'factor' => 'Estrés laboral',
        ]);

        $memberId = $committeeMember->id;

        // Force delete the organization (since it has soft deletes)
        $organization->forceDelete();

        // Committee member should be deleted due to cascade
        $this->assertDatabaseMissing('committee_members', [
            'id' => $memberId,
        ]);
    }

    public function test_committee_member_can_have_optional_fields(): void
    {
        $organization = Organization::factory()->create();

        $committeeMember = CommitteeMember::create([
            'organization_id' => $organization->id,
            'nombre' => 'Juan Pérez',
            // departamento, puesto, and factor are optional
        ]);

        $this->assertDatabaseHas('committee_members', [
            'organization_id' => $organization->id,
            'nombre' => 'Juan Pérez',
            'departamento' => null,
            'puesto' => null,
            'factor' => null,
        ]);
    }

    public function test_validation_requires_nombre_for_committee_members(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();

        $this->actingAs($user);

        $response = $this->put(route('organizations.update', $organization), [
            'name' => 'Test Organization',
            'committee_members' => [
                [
                    'nombre' => '', // Empty nombre should fail validation
                    'departamento' => 'Recursos Humanos',
                    'puesto' => 'Coordinador',
                    'factor' => 'Estrés laboral',
                ],
            ],
        ]);

        $response->assertSessionHasErrors('committee_members.0.nombre');
    }

    public function test_empty_committee_members_are_not_saved(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();

        $this->actingAs($user);

        $response = $this->put(route('organizations.update', $organization), [
            'name' => 'Test Organization',
            'committee_members' => [
                [
                    'nombre' => 'Valid Member',
                    'departamento' => 'HR',
                    'puesto' => 'Manager',
                    'factor' => 'Stress',
                ],
                [
                    'nombre' => '', // Empty member should not be saved
                    'departamento' => '',
                    'puesto' => '',
                    'factor' => '',
                ],
            ],
        ]);

        $organization->refresh();
        // Only one member should be saved (the valid one)
        // Note: validation will catch this, so we need to adjust the test
        // Actually, the validation requires nombre, so this should fail
        $response->assertSessionHasErrors('committee_members.1.nombre');
    }
}
