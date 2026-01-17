<?php

namespace Database\Factories;

use App\Models\CommitteeMember;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

class CommitteeMemberFactory extends Factory
{
    protected $model = CommitteeMember::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'nombre' => $this->faker->name(),
            'departamento' => $this->faker->randomElement([
                'Recursos Humanos',
                'Seguridad',
                'Operaciones',
                'Administración',
                'Producción',
            ]),
            'puesto' => $this->faker->randomElement([
                'Coordinador',
                'Supervisor',
                'Gerente',
                'Jefe de Área',
                'Analista',
            ]),
            'factor' => $this->faker->randomElement([
                'Estrés laboral',
                'Violencia laboral',
                'Carga de trabajo',
                'Liderazgo negativo',
                'Falta de control',
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
