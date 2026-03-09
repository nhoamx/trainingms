<?php

namespace App\Console\Commands;

use App\Models\DepartmentArea;
use App\Models\OccupationPosition;
use App\Models\Organization;
use App\Support\OmrIdentifierSequence;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class MigrateIdentifierFormats extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'identifiers:migrate
                            {--dry-run : Simula los cambios sin escribir en base de datos}
                            {--organization= : UUID de organizacion para migrar solo una organizacion}
                            {--export= : Ruta de salida relativa a storage/app/private (ej: reports/identifier-migration.json)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Elimina soft deletes y migra identifiers activos al nuevo formato OMR';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $organizationId = $this->option('organization');
        $exportPath = $this->option('export');

        if ($organizationId !== null && ! Organization::query()->whereKey($organizationId)->exists()) {
            $this->error('La organizacion indicada no existe.');

            return self::FAILURE;
        }

        $organizations = Organization::query()
            ->when($organizationId, fn (Builder $query): Builder => $query->whereKey($organizationId))
            ->orderBy('id')
            ->get(['id', 'name']);

        $report = [
            'generated_at' => now()->toIso8601String(),
            'dry_run' => $dryRun,
            'filters' => [
                'organization' => $organizationId,
            ],
            'soft_deleted_cleanup' => [],
            'migrations' => [],
            'summary' => [
                'organizations_processed' => $organizations->count(),
                'soft_deleted_removed' => [
                    'positions' => 0,
                    'departments' => 0,
                ],
                'records_updated' => [
                    'positions' => 0,
                    'departments' => 0,
                ],
                'records_already_migrated' => [
                    'positions' => 0,
                    'departments' => 0,
                ],
            ],
        ];

        if ($organizations->isEmpty()) {
            $this->warn('No se encontraron organizaciones para migrar.');

            return self::SUCCESS;
        }

        foreach ($organizations as $organization) {
            try {
                $organizationReport = $this->migrateOrganization((string) $organization->id, $dryRun);

                $report['soft_deleted_cleanup'][] = [
                    'organization_id' => (string) $organization->id,
                    'organization_name' => (string) $organization->name,
                    'positions_removed' => $organizationReport['cleanup']['positions_removed'],
                    'departments_removed' => $organizationReport['cleanup']['departments_removed'],
                ];

                $report['migrations'][] = [
                    'organization_id' => (string) $organization->id,
                    'organization_name' => (string) $organization->name,
                    'positions' => $organizationReport['positions'],
                    'departments' => $organizationReport['departments'],
                ];

                $report['summary']['soft_deleted_removed']['positions'] += $organizationReport['cleanup']['positions_removed'];
                $report['summary']['soft_deleted_removed']['departments'] += $organizationReport['cleanup']['departments_removed'];

                $report['summary']['records_updated']['positions'] += $organizationReport['positions']['updated'];
                $report['summary']['records_updated']['departments'] += $organizationReport['departments']['updated'];

                $report['summary']['records_already_migrated']['positions'] += $organizationReport['positions']['unchanged'];
                $report['summary']['records_already_migrated']['departments'] += $organizationReport['departments']['unchanged'];
            } catch (RuntimeException $exception) {
                $this->error("Organizacion {$organization->id}: {$exception->getMessage()}");

                return self::FAILURE;
            }
        }

        $targetPath = $exportPath ?: 'reports/identifier-migration-'.now()->format('Ymd_His').'.json';
        Storage::disk('local')->put('private/'.$targetPath, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        $this->table(
            ['Metrica', 'Puestos', 'Departamentos'],
            [
                [
                    'Soft deleted eliminados'.($dryRun ? ' (simulado)' : ''),
                    $report['summary']['soft_deleted_removed']['positions'],
                    $report['summary']['soft_deleted_removed']['departments'],
                ],
                [
                    'Registros actualizados'.($dryRun ? ' (simulado)' : ''),
                    $report['summary']['records_updated']['positions'],
                    $report['summary']['records_updated']['departments'],
                ],
                [
                    'Registros sin cambio',
                    $report['summary']['records_already_migrated']['positions'],
                    $report['summary']['records_already_migrated']['departments'],
                ],
            ]
        );

        $this->info('Reporte guardado en storage/app/private/'.$targetPath);

        if ($dryRun) {
            $this->warn('Ejecucion en modo dry-run: no se aplicaron cambios en la base de datos.');
        }

        return self::SUCCESS;
    }

    /**
     * @return array<string, mixed>
     */
    private function migrateOrganization(string $organizationId, bool $dryRun): array
    {
        $positionsCleanupCount = $this->cleanupSoftDeleted(OccupationPosition::query(), $organizationId, $dryRun);
        $departmentsCleanupCount = $this->cleanupSoftDeleted(DepartmentArea::query(), $organizationId, $dryRun);

        $positionMigration = $this->migrateEntity(OccupationPosition::query(), $organizationId, $dryRun);
        $departmentMigration = $this->migrateEntity(DepartmentArea::query(), $organizationId, $dryRun);

        return [
            'cleanup' => [
                'positions_removed' => $positionsCleanupCount,
                'departments_removed' => $departmentsCleanupCount,
            ],
            'positions' => $positionMigration,
            'departments' => $departmentMigration,
        ];
    }

    private function cleanupSoftDeleted(Builder $query, string $organizationId, bool $dryRun): int
    {
        $softDeletedQuery = $query
            ->onlyTrashed()
            ->where('organization_id', $organizationId);

        $count = (clone $softDeletedQuery)->count();

        if (! $dryRun && $count > 0) {
            $softDeletedQuery->forceDelete();
        }

        return $count;
    }

    /**
     * @return array<string, mixed>
     */
    private function migrateEntity(Builder $query, string $organizationId, bool $dryRun): array
    {
        $records = $query
            ->where('organization_id', $organizationId)
            ->orderBy('id')
            ->get(['id', 'identifier']);

        if ($records->count() > OmrIdentifierSequence::totalCombinations()) {
            throw new RuntimeException('No hay suficientes combinaciones OMR para migrar todos los registros activos.');
        }

        $catalog = OmrIdentifierSequence::catalog();
        $updated = 0;
        $unchanged = 0;
        $changes = [];

        $records->values()->each(function ($record, int $index) use (&$updated, &$unchanged, &$changes, $catalog, $dryRun): void {
            $targetIdentifier = $catalog[$index];
            $currentIdentifier = OmrIdentifierSequence::normalize((string) $record->identifier);

            if ($currentIdentifier === $targetIdentifier) {
                $unchanged++;

                return;
            }

            $updated++;

            $changes[] = [
                'id' => (int) $record->id,
                'from' => (string) $record->identifier,
                'to' => $targetIdentifier,
            ];

            if (! $dryRun) {
                $record->identifier = $targetIdentifier;
                $record->save();
            }
        });

        return [
            'total' => $records->count(),
            'updated' => $updated,
            'unchanged' => $unchanged,
            'changes' => $changes,
        ];
    }
}
