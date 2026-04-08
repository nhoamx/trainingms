<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class WorkCenterResponsesMultiSheetExport implements WithMultipleSheets
{
    /**
     * @param  Collection<int, array{id: string, code: string, name: string, is_primary: bool, evaluations: array<int, array{folio: string, source: string, referencia_iii: mixed, referencia_i_acontecimientos_traumaticos: mixed, referencia_i: mixed, referencia_v: mixed}>}>  $workCenters
     */
    public function __construct(
        private readonly Collection $workCenters,
    ) {}

    /**
     * @return array<int, WorkCenterResponsesSheetExport>
     */
    public function sheets(): array
    {
        return $this->workCenters
            ->map(fn (array $workCenter): WorkCenterResponsesSheetExport => new WorkCenterResponsesSheetExport($workCenter))
            ->values()
            ->all();
    }
}
