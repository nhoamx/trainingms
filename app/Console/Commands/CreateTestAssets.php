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

        Asset::factory()
            ->count($count)
            ->create([
                'organization_id' => $organization->id,
            ]);

        $progressBar->finish();
        $this->newLine(2);

        $this->info("✅ Se crearon {$count} extintores exitosamente para {$organization->name}");

        return Command::SUCCESS;
    }
}
