<?php

namespace App\Console\Commands;

use App\Models\Asset;
use App\Models\Organization;
use Illuminate\Console\Command;

class CreateTestAssets extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'assets:create-test {organization_id} {--count=150}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Crea extintores de prueba para una organización';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $organizationId = $this->argument('organization_id');
        $count = (int) $this->option('count');

        $organization = Organization::find($organizationId);

        if (! $organization) {
            $this->error("❌ Organización con ID {$organizationId} no encontrada.");

            return Command::FAILURE;
        }

        $this->info("🚀 Creando {$count} extintores para: {$organization->name}");

        $progressBar = $this->output->createProgressBar($count);
        $progressBar->start();

        $locations = [
            'Oficina Principal - Piso 1',
            'Oficina Principal - Piso 2',
            'Oficina Principal - Piso 3',
            'Almacén General',
            'Área de Producción',
            'Recepción',
            'Pasillo A',
            'Pasillo B',
            'Pasillo C',
            'Cafetería',
            'Sala de Juntas',
            'Estacionamiento',
            'Bodega',
        ];

        $classes = ['Clase ABC', 'Clase BC', 'Clase K', 'Clase A'];
        $capacities = ['5 lbs', '10 lbs', '20 lbs', '30 lbs'];

        for ($i = 1; $i <= $count; $i++) {
            Asset::create([
                'organization_id' => $organization->id,
                'asset_type' => 'extintor',
                'serial_number' => 'EXT-'.strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 8)),
                'location' => $locations[array_rand($locations)].' - Zona '.chr(65 + ($i % 26)),
                'capacity' => $capacities[array_rand($capacities)],
                'fire_class' => $classes[array_rand($classes)],
            ]);

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        $this->info("✅ Se crearon {$count} extintores exitosamente para {$organization->name}");

        return Command::SUCCESS;
    }
}
