<?php

namespace Database\Factories;

use App\Models\Asset;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Asset>
 */
class AssetFactory extends Factory
{
    protected $model = Asset::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'asset_type' => fake()->randomElement(['PQS', 'CO2', 'Agua', 'Espuma', 'Agente Limpio']),
            'asset_category' => 'extintor',
            'consecutive_number' => fake()->unique()->numerify('###'),
            'serial_number' => 'EXT-'.fake()->unique()->numerify('####'),
            'location' => fake()->randomElement(['Oficina Principal', 'Almacén', 'Pasillo Principal', 'Recepción']).' - '.fake()->randomElement(['Pasillo 1', 'Pasillo 2', 'Entrada', 'Salida de emergencia']),
            'capacity' => fake()->randomElement(['5 lbs', '10 lbs', '20 lbs', '30 lbs']),
            'fire_class' => fake()->randomElement(['Clase A', 'Clase B', 'Clase C', 'Clase ABC', 'Clase BC']),
        ];
    }

    public function extintor(): static
    {
        return $this->state(fn (array $attributes) => [
            'asset_category' => 'extintor',
        ]);
    }
}
