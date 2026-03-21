<?php

namespace Tests\Unit\Imports;

use App\Imports\DepartmentAreasImport;
use App\Imports\OccupationPositionsImport;
use App\Models\Organization;
use App\Services\DepartmentAreaService;
use App\Services\OccupationPositionService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class IdentifierImportValidationTest extends TestCase
{
    use DatabaseTransactions;

    public function test_occupation_import_rejects_invalid_identifier_format(): void
    {
        $organization = Organization::factory()->create();
        $import = new OccupationPositionsImport($organization, app(OccupationPositionService::class));

        $validator = Validator::make(
            [
                'nombre_del_puesto' => 'Puesto Test',
                'identificador' => '1_a',
            ],
            $import->rules(),
            $import->customValidationMessages()
        );

        $this->assertTrue($validator->fails());
        $this->assertStringContainsString('Formato de identificador invalido', $validator->errors()->first('identificador'));
    }

    public function test_occupation_import_accepts_empty_identifier_for_auto_assignment(): void
    {
        $organization = Organization::factory()->create();
        $import = new OccupationPositionsImport($organization, app(OccupationPositionService::class));

        $validator = Validator::make(
            [
                'nombre_del_puesto' => 'Puesto Test',
                'identificador' => null,
            ],
            $import->rules(),
            $import->customValidationMessages()
        );

        $this->assertTrue($validator->passes());
    }

    public function test_department_import_accepts_valid_identifier_format(): void
    {
        $organization = Organization::factory()->create();
        $import = new DepartmentAreasImport($organization, app(DepartmentAreaService::class));

        $validator = Validator::make(
            [
                'nombre_del_departamento' => 'Area Test',
                'identificador' => '1ab2cd',
            ],
            $import->rules(),
            $import->customValidationMessages()
        );

        $this->assertTrue($validator->passes());
    }

    public function test_department_import_rejects_identifier_with_wrong_row_order(): void
    {
        $organization = Organization::factory()->create();
        $import = new DepartmentAreasImport($organization, app(DepartmentAreaService::class));

        $validator = Validator::make(
            [
                'nombre_del_departamento' => 'Area Test',
                'identificador' => '2a1b',
            ],
            $import->rules(),
            $import->customValidationMessages()
        );

        $this->assertTrue($validator->fails());
        $this->assertStringContainsString('solo se permite 1...2...', $validator->errors()->first('identificador'));
    }

    public function test_department_import_rejects_identifier_longer_than_twelve_characters(): void
    {
        $organization = Organization::factory()->create();
        $import = new DepartmentAreasImport($organization, app(DepartmentAreaService::class));

        $validator = Validator::make(
            [
                'nombre_del_departamento' => 'Area Test',
                'identificador' => '1abcde2abcde1',
            ],
            $import->rules(),
            $import->customValidationMessages()
        );

        $this->assertTrue($validator->fails());
        $this->assertSame('El identificador no debe exceder 12 caracteres', $validator->errors()->first('identificador'));
    }
}
