<?php

namespace Database\Factories;

use App\Enums\WorkCenterType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\WorkCenter>
 */
class WorkCenterFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'organization_id' => \App\Models\Organization::factory(),
            'code' => fake()->unique()->numerify('####'),
            'name' => fake()->randomElement(['Plant', 'Branch', 'Warehouse', 'Office']).' '.fake()->city(),
            'type' => fake()->randomElement(WorkCenterType::cases())->value,
            'is_primary' => false,
            'legal_name' => fake()->company().' S.A. de C.V.',
            'tax_id' => fake()->regexify('[A-Z]{3}[0-9]{6}[A-Z0-9]{3}'),
            'employer_registration' => fake()->optional()->numerify('A##########'),
            'street_address' => fake()->streetAddress(),
            'neighborhood' => fake()->citySuffix(),
            'postal_code' => fake()->postcode(),
            'municipality' => fake()->city(),
            'state' => fake()->state(),
            'phone' => fake()->optional()->phoneNumber(),
            'contact_email' => fake()->optional()->companyEmail(),
        ];
    }

    /**
     * Indicate that the work center is primary
     */
    public function primary(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_primary' => true,
            'type' => WorkCenterType::Headquarters->value,
        ]);
    }

    /**
     * Indicate that the work center is a plant
     */
    public function plant(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => WorkCenterType::Plant->value,
            'name' => 'Plant '.fake()->city(),
        ]);
    }

    /**
     * Indicate that the work center is a branch
     */
    public function branch(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => WorkCenterType::Branch->value,
            'name' => 'Branch '.fake()->city(),
        ]);
    }
}
