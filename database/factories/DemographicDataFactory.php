<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DemographicData>
 */
class DemographicDataFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'gender' => null,
            'age' => null,
            'marital_status' => null,
            'education_level' => null,
            'position' => null,
            'department' => null,
            'position_type' => null,
            'contract_type' => null,
            'personnel_type' => null,
            'work_schedule' => null,
            'shift_rotation' => null,
            'time_in_current_position' => null,
            'work_experience' => null,
            'extra_fields' => null,
        ];
    }
}
