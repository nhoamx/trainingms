<?php

namespace Database\Factories;

use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\FolioBatch>
 */
class FolioBatchFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startNumber = fake()->numberBetween(1, 100);
        $quantity = fake()->numberBetween(10, 100);

        return [
            'organization_id' => Organization::factory(),
            'name' => fake()->words(3, true),
            'description' => fake()->sentence(),
            'start_number' => $startNumber,
            'end_number' => $startNumber + $quantity - 1,
            'quantity' => $quantity,
            'type' => fake()->randomElement(['presencial', 'en_linea']),
            'active' => true,
        ];
    }

    /**
     * Indicate that the batch is for presencial evaluations.
     */
    public function presencial(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'presencial',
        ]);
    }

    /**
     * Indicate that the batch is for online evaluations.
     */
    public function online(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'en_linea',
        ]);
    }
}
