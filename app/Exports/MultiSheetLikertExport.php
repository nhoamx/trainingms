<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class MultiSheetLikertExport implements WithMultipleSheets
{
    /**
     * @param  array<int, LikertCombinationSheetExport>  $sheets
     */
    public function __construct(
        protected array $sheets
    ) {}

    /**
     * @return array<int, LikertCombinationSheetExport>
     */
    public function sheets(): array
    {
        return $this->sheets;
    }
}
