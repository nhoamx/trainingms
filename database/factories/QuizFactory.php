<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\Quiz;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class QuizFactory extends Factory
{
    protected $model = Quiz::class;

    public function definition()
    {
        return [
            'name' => $this->faker->sentence(3),
            'temp_url' => Str::random(32),
            'expires_at' => $this->faker->dateTimeBetween('now', '+1 week'),
            'is_active' => true,
            'is_reduced' => false,
            'is_cisneros' => false,
            'organization_id' => Organization::factory(),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    public function reduced()
    {
        return $this->state(function (array $attributes) {
            return [
                'is_reduced' => true,
                'is_cisneros' => false,
            ];
        });
    }

    public function cisneros()
    {
        return $this->state(function (array $attributes) {
            return [
                'is_reduced' => false,
                'is_cisneros' => true,
            ];
        });
    }

    public function inactive()
    {
        return $this->state(function (array $attributes) {
            return [
                'is_active' => false,
            ];
        });
    }

    public function expired()
    {
        return $this->state(function (array $attributes) {
            return [
                'expires_at' => $this->faker->dateTimeBetween('-1 week', '-1 day'),
            ];
        });
    }
}
