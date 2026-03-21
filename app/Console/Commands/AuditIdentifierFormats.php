<?php

namespace App\Console\Commands;

use App\Models\DepartmentArea;
use App\Models\OccupationPosition;
use App\Models\Organization;
use App\Support\OmrIdentifierSequence;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;

class AuditIdentifierFormats extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'identifiers:audit
                            {--organization= : UUID de organizacion para auditar solo una organizacion}
                            {--export= : Ruta de salida relativa a storage/app (ej: reports/identifier-audit.json)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Audita identifiers de puestos y departamentos (nuevo formato, legacy, invalido, colisiones)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $organizationId = $this->option('organization');
        $exportPath = $this->option('export');

        if ($organizationId !== null && ! Organization::query()->whereKey($organizationId)->exists()) {
            $this->error('La organizacion indicada no existe.');

            return self::FAILURE;
        }

        $positions = $this->getAuditedRows(OccupationPosition::query(), 'position', $organizationId);
        $departments = $this->getAuditedRows(DepartmentArea::query(), 'department', $organizationId);

        $positionCollisions = $this->getCollisions(OccupationPosition::query(), 'position', $organizationId);
        $departmentCollisions = $this->getCollisions(DepartmentArea::query(), 'department', $organizationId);

        $summary = $this->buildSummary($positions, $departments, $positionCollisions, $departmentCollisions);

        $this->displaySummary($summary);

        $invalidOrLegacy = array_values(array_filter(
            [...$positions, ...$departments],
            fn (array $row): bool => $row['status'] !== 'nuevo_formato'
        ));

        if ($invalidOrLegacy !== []) {
            $this->newLine();
            $this->warn('Registros legacy/invalidos detectados:');
            $this->table(
                ['Tipo', 'Organizacion', 'Registro ID', 'Identifier', 'Status'],
                array_map(fn (array $row): array => [
                    $row['entity'],
                    $row['organization_id'],
                    $row['id'],
                    $row['identifier'],
                    $row['status'],
                ], $invalidOrLegacy)
            );
        }

        $allCollisions = [...$positionCollisions, ...$departmentCollisions];

        if ($allCollisions !== []) {
            $this->newLine();
            $this->warn('Colisiones detectadas dentro de la misma organizacion:');
            $this->table(
                ['Tipo', 'Organizacion', 'Identifier', 'Cantidad', 'IDs'],
                array_map(fn (array $collision): array => [
                    $collision['entity'],
                    $collision['organization_id'],
                    $collision['identifier'],
                    $collision['count'],
                    implode(', ', $collision['record_ids']),
                ], $allCollisions)
            );
        }

        $report = [
            'generated_at' => now()->toIso8601String(),
            'filters' => [
                'organization' => $organizationId,
            ],
            'summary' => $summary,
            'invalid_or_legacy' => $invalidOrLegacy,
            'collisions' => $allCollisions,
        ];

        $targetPath = $exportPath ?: 'reports/identifier-audit-'.now()->format('Ymd_His').'.json';
        Storage::disk('local')->put($targetPath, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        $this->newLine();
        $this->info('Reporte guardado en storage/app/'.$targetPath);

        return self::SUCCESS;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getAuditedRows(Builder $query, string $entity, ?string $organizationId): array
    {
        if ($organizationId) {
            $query->where('organization_id', $organizationId);
        }

        return $query
            ->orderBy('organization_id')
            ->orderBy('id')
            ->get(['id', 'organization_id', 'identifier', 'name'])
            ->map(function ($row) use ($entity): array {
                $normalized = OmrIdentifierSequence::normalize((string) $row->identifier);

                return [
                    'entity' => $entity,
                    'id' => (int) $row->id,
                    'organization_id' => (string) $row->organization_id,
                    'identifier' => (string) $row->identifier,
                    'normalized_identifier' => $normalized,
                    'name' => (string) $row->name,
                    'status' => $this->classifyIdentifier($normalized),
                ];
            })
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getCollisions(Builder $query, string $entity, ?string $organizationId): array
    {
        if ($organizationId) {
            $query->where('organization_id', $organizationId);
        }

        $baseQuery = clone $query;

        $groups = (clone $baseQuery)
            ->selectRaw('organization_id, identifier, COUNT(*) as total')
            ->groupBy('organization_id', 'identifier')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        return $groups->map(function ($group) use ($baseQuery, $entity): array {
            $recordIds = (clone $baseQuery)
                ->where('organization_id', $group->organization_id)
                ->where('identifier', $group->identifier)
                ->select('id')
                ->pluck('id')
                ->map(fn ($id): int => (int) $id)
                ->all();

            return [
                'entity' => $entity,
                'organization_id' => (string) $group->organization_id,
                'identifier' => (string) $group->identifier,
                'count' => (int) $group->total,
                'record_ids' => $recordIds,
            ];
        })->all();
    }

    /**
     * @param  array<int, array<string, mixed>>  $positions
     * @param  array<int, array<string, mixed>>  $departments
     * @param  array<int, array<string, mixed>>  $positionCollisions
     * @param  array<int, array<string, mixed>>  $departmentCollisions
     * @return array<string, mixed>
     */
    private function buildSummary(array $positions, array $departments, array $positionCollisions, array $departmentCollisions): array
    {
        return [
            'positions' => $this->buildEntitySummary($positions, $positionCollisions),
            'departments' => $this->buildEntitySummary($departments, $departmentCollisions),
            'totals' => [
                'records' => count($positions) + count($departments),
                'collisions' => count($positionCollisions) + count($departmentCollisions),
            ],
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @param  array<int, array<string, mixed>>  $collisions
     * @return array<string, int>
     */
    private function buildEntitySummary(array $rows, array $collisions): array
    {
        return [
            'total' => count($rows),
            'nuevo_formato' => count(array_filter($rows, fn (array $row): bool => $row['status'] === 'nuevo_formato')),
            'legacy' => count(array_filter($rows, fn (array $row): bool => $row['status'] === 'legacy')),
            'invalido' => count(array_filter($rows, fn (array $row): bool => $row['status'] === 'invalido')),
            'collisions' => count($collisions),
        ];
    }

    private function classifyIdentifier(string $normalizedIdentifier): string
    {
        if (OmrIdentifierSequence::isValid($normalizedIdentifier)) {
            return 'nuevo_formato';
        }

        if ((bool) preg_match('/^\d+_[a-z]$/', $normalizedIdentifier)) {
            return 'legacy';
        }

        return 'invalido';
    }

    /**
     * @param  array<string, mixed>  $summary
     */
    private function displaySummary(array $summary): void
    {
        $this->info('Resumen de auditoria de identifiers');
        $this->table(
            ['Entidad', 'Total', 'Nuevo', 'Legacy', 'Invalido', 'Colisiones'],
            [
                [
                    'Puestos',
                    $summary['positions']['total'],
                    $summary['positions']['nuevo_formato'],
                    $summary['positions']['legacy'],
                    $summary['positions']['invalido'],
                    $summary['positions']['collisions'],
                ],
                [
                    'Departamentos',
                    $summary['departments']['total'],
                    $summary['departments']['nuevo_formato'],
                    $summary['departments']['legacy'],
                    $summary['departments']['invalido'],
                    $summary['departments']['collisions'],
                ],
                [
                    'TOTAL',
                    $summary['totals']['records'],
                    $summary['positions']['nuevo_formato'] + $summary['departments']['nuevo_formato'],
                    $summary['positions']['legacy'] + $summary['departments']['legacy'],
                    $summary['positions']['invalido'] + $summary['departments']['invalido'],
                    $summary['totals']['collisions'],
                ],
            ]
        );
    }
}
