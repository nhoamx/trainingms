<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\BulkImportJob>
 */
class BulkImportJobFactory extends Factory
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
            'user_id' => User::factory(),
            'file_name' => fake()->word().'.xlsx',
            'file_path' => 'bulk-imports/'.fake()->uuid().'.xlsx',
            'source' => fake()->optional()->randomElement(['paper', 'online']),
            'status' => 'pending',
            'total_rows' => 0,
            'processed_rows' => 0,
            'updated_count' => 0,
            'skipped_count' => 0,
        ];
    }

    public function processing(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'processing',
            'started_at' => now(),
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'started_at' => now()->subMinutes(5),
            'completed_at' => now(),
            'total_rows' => 100,
            'processed_rows' => 100,
            'updated_count' => 80,
            'skipped_count' => 20,
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'failed',
            'started_at' => now()->subMinutes(2),
            'completed_at' => now(),
            'error_message' => 'Error durante el procesamiento',
        ]);
    }
}
