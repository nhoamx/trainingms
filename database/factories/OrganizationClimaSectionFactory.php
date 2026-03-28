<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\OrganizationClimaSection;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\OrganizationClimaSection>
 */
class OrganizationClimaSectionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'section_key' => 'conclusions_config',
            'content' => null,
            'status' => OrganizationClimaSection::STATUS_DRAFT,
            'updated_by' => null,
            'published_at' => null,
        ];
    }
}
