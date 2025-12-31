<?php

namespace Database\Factories;

use App\Models\Asset;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AssetInspection>
 */
class AssetInspectionFactory extends Factory
{
    public function definition(): array
    {
        $checklistResults = [];
        for ($i = 1; $i <= 27; $i++) {
            $checklistResults[$i] = [
                'date' => fake()->dateTimeBetween('-30 days', 'now')->format('Y-m-d'),
                'result' => fake()->randomElement(['OK', 'Revisar', 'Atención requerida', 'N/A']),
            ];
        }

        return [
            'asset_id' => Asset::factory(),
            'inspector_name' => fake()->name(),
            'inspection_date' => fake()->dateTimeBetween('-60 days', 'now'),
            'checklist_results' => $checklistResults,
            'anomalies_followup' => fake()->optional(0.3)->paragraph(),
        ];
    }
}
