<?php

namespace App\Http\Controllers\WorkCenter;

use App\Exports\WorkCenterResponsesMultiSheetExport;
use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Services\WorkCenterOrganizationReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class WorkCenterOrganizationReportController extends Controller
{
    public function __construct(
        private readonly WorkCenterOrganizationReportService $workCenterOrganizationReportService,
    ) {}

    public function download(Request $request, Organization $organization, string $reportType): BinaryFileResponse
    {
        abort_unless($reportType === 'respuestas', 404);

        $source = (string) $request->query('source', 'legacy');

        $workCenters = $source === 'normalized'
            ? $this->workCenterOrganizationReportService->getOrganizationWorkCentersFromNormalizedAnswers($organization)
            : $this->workCenterOrganizationReportService->getOrganizationWorkCenters($organization);

        $filename = 'respuestas_organizacion_'.Str::slug($organization->name).'_'.now()->format('Ymd_His').'.xlsx';

        return Excel::download(
            new WorkCenterResponsesMultiSheetExport($workCenters),
            $filename
        );
    }
}
