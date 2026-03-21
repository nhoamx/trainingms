<?php

namespace App\Http\Controllers\WorkCenter;

use App\Http\Controllers\Controller;
use App\Models\PaperEvaluation;
use App\Models\WorkCenter;
use App\Services\WorkCenter\WorkCenterNom035CisnerosStatisticsService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Inertia\Inertia;
use Inertia\Response;

class WorkCenterNom035CisnerosDashboardController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        protected WorkCenterNom035CisnerosStatisticsService $statisticsService
    ) {}

    public function show(WorkCenter $workCenter): Response
    {
        $this->authorize('viewWorkCenterDashboard', $workCenter);

        $workCenter->load('organization');
        $cisnerosDashboard = $this->statisticsService->getDashboardData($workCenter);

        return Inertia::render('WorkCenters/Nom035CisnerosDashboard', [
            'title' => 'NOM-035-STPS-2018 - Escala Cisneros - '.$workCenter->name,
            'dashboardData' => $this->buildDashboardData($workCenter),
            'cisnerosEvaluationsCount' => PaperEvaluation::query()
                ->where('work_center_id', $workCenter->id)
                ->where('evaluation_type', 'cisneros')
                ->where('processing_status', 'completed')
                ->count(),
            'cisnerosSummary' => $cisnerosDashboard['summary'],
            'authorsChart' => $cisnerosDashboard['authors_chart'],
            'frequencyChart' => $cisnerosDashboard['frequency_chart'],
            'participants' => $cisnerosDashboard['participants'],
            'responsesTable' => $cisnerosDashboard['responses_table'],
        ]);
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function buildDashboardData(WorkCenter $workCenter): array
    {
        $organization = $workCenter->organization;

        return [
            'organization' => [
                'id' => $organization->id,
                'name' => $organization->name,
                'logo' => $organization->logo ? asset('storage/'.$organization->logo) : null,
            ],
            'work_center' => [
                'id' => $workCenter->id,
                'name' => $workCenter->name,
                'code' => $workCenter->code,
            ],
        ];
    }
}
