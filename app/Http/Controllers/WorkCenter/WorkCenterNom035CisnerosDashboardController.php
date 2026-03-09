<?php

namespace App\Http\Controllers\WorkCenter;

use App\Http\Controllers\Controller;
use App\Models\PaperEvaluation;
use App\Models\WorkCenter;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Inertia\Inertia;
use Inertia\Response;

class WorkCenterNom035CisnerosDashboardController extends Controller
{
    use AuthorizesRequests;

    public function show(WorkCenter $workCenter): Response
    {
        $this->authorize('viewWorkCenterDashboard', $workCenter);

        $workCenter->load('organization');

        return Inertia::render('WorkCenters/Nom035CisnerosDashboard', [
            'title' => 'NOM-035-STPS-2018 - Escala Cisneros - '.$workCenter->name,
            'dashboardData' => $this->buildDashboardData($workCenter),
            'cisnerosEvaluationsCount' => PaperEvaluation::query()
                ->where('work_center_id', $workCenter->id)
                ->where('evaluation_type', 'cisneros')
                ->where('processing_status', 'completed')
                ->count(),
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
