<?php

namespace Database\Seeders;

use App\Models\Instrument;
use Illuminate\Database\Seeder;

class InstrumentSeeder extends Seeder
{
    public function run(): void
    {
        $instruments = [
            [
                'name' => 'nom_035',
                'description' => 'NOM-035-STPS-2018 - Factores de riesgo psicosocial',
            ],
            [
                'name' => 'nom_002',
                'description' => 'NOM-002-STPS - Prevención de incendios y extintores',
            ],
            [
                'name' => 'clima_laboral',
                'description' => 'Evaluación de Clima Laboral',
            ],
        ];

        foreach ($instruments as $instrument) {
            Instrument::firstOrCreate(
                ['name' => $instrument['name']],
                ['description' => $instrument['description']]
            );
        }
    }
}
