<?php

namespace Database\Factories;

use App\Models\Instrument;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Instrument>
 */
class InstrumentFactory extends Factory
{
    protected $model = Instrument::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->slug(2),
            'description' => fake()->sentence(),
        ];
    }

    public function nom002(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'nom_002',
            'description' => 'NOM-002 Extintores',
        ]);
    }
}
