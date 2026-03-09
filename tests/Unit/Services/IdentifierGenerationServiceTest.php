<?php

namespace Tests\Unit\Services;

use App\Models\DepartmentArea;
use App\Models\OccupationPosition;
use App\Models\Organization;
use App\Services\DepartmentAreaService;
use App\Services\OccupationPositionService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use InvalidArgumentException;
use Tests\TestCase;

class IdentifierGenerationServiceTest extends TestCase
{
    use DatabaseTransactions;

    private OccupationPositionService $occupationPositionService;

    private DepartmentAreaService $departmentAreaService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->occupationPositionService = app(OccupationPositionService::class);
        $this->departmentAreaService = app(DepartmentAreaService::class);
    }

    public function test_position_identifier_generation_starts_with_expected_sequence(): void
    {
        $organization = Organization::factory()->create();

        $first = $this->occupationPositionService->createPosition($organization, 'Puesto 1');
        $second = $this->occupationPositionService->createPosition($organization, 'Puesto 2');

        $this->assertSame('1a', $first->identifier);
        $this->assertSame('1b', $second->identifier);
    }

    public function test_position_identifier_generation_skips_used_identifiers(): void
    {
        $organization = Organization::factory()->create();

        OccupationPosition::create([
            'organization_id' => $organization->id,
            'name' => 'Legacy 1',
            'identifier' => '1a',
        ]);

        OccupationPosition::create([
            'organization_id' => $organization->id,
            'name' => 'Legacy 2',
            'identifier' => '1b',
        ]);

        $generated = $this->occupationPositionService->createPosition($organization, 'Nuevo');

        $this->assertSame('1c', $generated->identifier);
    }

    public function test_department_identifier_generation_is_unique_per_organization(): void
    {
        $firstOrganization = Organization::factory()->create();
        $secondOrganization = Organization::factory()->create();

        $firstOrgDepartment = $this->departmentAreaService->createArea($firstOrganization, 'Area A');
        $secondOrgDepartment = $this->departmentAreaService->createArea($secondOrganization, 'Area B');

        $this->assertSame('1a', $firstOrgDepartment->identifier);
        $this->assertSame('1a', $secondOrgDepartment->identifier);
    }

    public function test_department_identifier_generation_advances_after_existing_values(): void
    {
        $organization = Organization::factory()->create();

        DepartmentArea::create([
            'organization_id' => $organization->id,
            'name' => 'Area 1',
            'identifier' => '1a',
        ]);

        DepartmentArea::create([
            'organization_id' => $organization->id,
            'name' => 'Area 2',
            'identifier' => '1b',
        ]);

        $generated = $this->departmentAreaService->createArea($organization, 'Area 3');

        $this->assertSame('1c', $generated->identifier);
    }

    public function test_position_creation_rejects_invalid_custom_identifier(): void
    {
        $organization = Organization::factory()->create();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Formato de identificador invalido');

        $this->occupationPositionService->createPosition($organization, 'Puesto Invalido', '1_a');
    }

    public function test_department_creation_accepts_valid_custom_identifier(): void
    {
        $organization = Organization::factory()->create();

        $area = $this->departmentAreaService->createArea($organization, 'Area Custom', '1ab2cd');

        $this->assertSame('1ab2cd', $area->identifier);
    }
}
