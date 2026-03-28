<?php

namespace Database\Factories;

use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\OrganizationConclusionsFile>
 */
class OrganizationConclusionsFileFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $organizationId = fake()->uuid();

        return [
            'organization_id' => Organization::factory(),
            'slot' => fake()->numberBetween(1, 3),
            'title' => fake()->sentence(3),
            'color' => fake()->randomElement(['teal', 'blue', 'red', 'amber', 'slate']),
            'disk' => 'public',
            'path' => "{$organizationId}/conclusions/slot_1.pdf",
            'original_filename' => fake()->word().'.pdf',
            'file_size' => fake()->numberBetween(10000, 5000000),
            'mime_type' => 'application/pdf',
            'is_published' => false,
            'uploaded_by' => null,
        ];
    }
}
