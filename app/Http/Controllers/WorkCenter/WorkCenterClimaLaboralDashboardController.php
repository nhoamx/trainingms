<?php

namespace App\Http\Controllers\WorkCenter;

use App\Http\Controllers\Controller;
use App\Models\PaperEvaluation;
use App\Models\WorkCenter;
use App\Services\LikertScoreService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Dashboard de Clima Laboral para un centro de trabajo.
 */
class WorkCenterClimaLaboralDashboardController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        protected LikertScoreService $likertScoreService,
    ) {}

    public function show(Request $request, WorkCenter $workCenter): Response
    {
        $this->authorize('viewWorkCenterDashboard', $workCenter);

        $workCenter->load('organization');

        $evaluations = PaperEvaluation::query()
            ->where('work_center_id', $workCenter->id)
            ->where('evaluation_type', 'likert')
            ->where('processing_status', 'completed')
            ->with(['demographicData'])
            ->get()
            ->map(function (PaperEvaluation $evaluation): array {
                $scores = $this->likertScoreService->calculateLikertScores($evaluation);

                return [
                    'id' => $evaluation->id,
                    'folio' => $evaluation->folio,
                    'personal_folio' => $evaluation->personal_folio,
                    'total_score' => $scores['total_score'],
                    'interpretation' => $scores['interpretation'],
                    'demographicData' => $evaluation->demographicData ? [
                        'gender' => $evaluation->demographicData->gender,
                        'contract_type' => $evaluation->demographicData->contract_type,
                        'position' => $evaluation->demographicData->position,
                        'department' => $evaluation->demographicData->department,
                        'work_schedule' => $evaluation->demographicData->work_schedule,
                    ] : null,
                ];
            })
            ->values()
            ->all();

        return Inertia::render('WorkCenters/ClimaLaboralDashboard', [
            'title' => 'Clima Laboral - '.$workCenter->name,
            'workCenter' => [
                'id' => $workCenter->id,
                'name' => $workCenter->name,
                'code' => $workCenter->code,
            ],
            'organization' => [
                'id' => $workCenter->organization->id,
                'name' => $workCenter->organization->name,
                'logo' => $workCenter->organization->logo
                    ? asset('storage/'.$workCenter->organization->logo)
                    : null,
            ],
            'evaluations' => $evaluations,
            'totalEvaluations' => count($evaluations),
        ]);
    }
}
