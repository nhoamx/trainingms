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

        $paperEvaluations = PaperEvaluation::query()
            ->where('work_center_id', $workCenter->id)
            ->where('evaluation_type', 'likert')
            ->where('processing_status', 'completed')
            ->with(['demographicData', 'comments'])
            ->get();

        $demographicDetails = $this->buildDemographicDetails($paperEvaluations);

        $evaluations = $paperEvaluations->map(function (PaperEvaluation $evaluation): array {
            $scores = $this->likertScoreService->calculateLikertScores($evaluation);

            $dimensions = [];
            foreach ($scores['dimensions'] as $dimensionName => $dimensionData) {
                $dimensions[] = [
                    'name' => $dimensionName,
                    'score' => $dimensionData['score'],
                    'interpretation' => $dimensionData['interpretation'],
                ];
            }

            $comments = $evaluation->comments->map(fn ($comment): array => [
                'factor' => $comment->factor,
                'comment' => $comment->comment,
            ]);

            return [
                'id' => $evaluation->id,
                'folio' => $evaluation->folio,
                'personal_folio' => $evaluation->personal_folio,
                'demographicData' => $evaluation->demographicData ? [
                    'gender' => $evaluation->demographicData->gender,
                    'contract_type' => $evaluation->demographicData->contract_type,
                    'position' => $evaluation->demographicData->position,
                    'department' => $evaluation->demographicData->department,
                    'work_schedule' => $evaluation->demographicData->work_schedule,
                ] : null,
                'dimensions' => $dimensions,
                'comments' => $comments,
                'total_score' => $scores['total_score'],
                'interpretation' => $scores['interpretation'],
            ];
        })->values()->all();

        return Inertia::render('WorkCenters/ClimaLaboralDashboard', [
            'title' => 'Clima Laboral - '.$workCenter->name,
            'workCenter' => [
                'id' => $workCenter->id,
                'name' => $workCenter->name,
                'code' => $workCenter->code,
            ],
            'dashboardData' => [
                'organization' => [
                    'id' => $workCenter->organization->id,
                    'name' => $workCenter->organization->name,
                    'logo' => $workCenter->organization->logo
                        ? asset('storage/'.$workCenter->organization->logo)
                        : null,
                ],
                'demographic_details' => $demographicDetails,
            ],
            'evaluations' => $evaluations,
        ]);
    }

    /**
     * Builds demographic details from a collection of PaperEvaluations.
     *
     * @param  \Illuminate\Support\Collection<int, PaperEvaluation>  $evaluations
     * @return array{genders: string[], contract_types: string[], positions: string[], areas: string[], shifts: string[], total_evaluations: int}
     */
    private function buildDemographicDetails(\Illuminate\Support\Collection $evaluations): array
    {
        $genders = [];
        $contractTypes = [];
        $positions = [];
        $areas = [];
        $shifts = [];

        foreach ($evaluations as $evaluation) {
            $demo = $evaluation->demographicData;
            if ($demo) {
                if ($demo->gender) {
                    $genders[$demo->gender] = true;
                }
                if ($demo->contract_type) {
                    $contractTypes[$demo->contract_type] = true;
                }
                if ($demo->position) {
                    $positions[$demo->position] = true;
                }
                if ($demo->department) {
                    $areas[$demo->department] = true;
                }
                if ($demo->work_schedule) {
                    $shifts[$demo->work_schedule] = true;
                }
            }
        }

        return [
            'genders' => array_keys($genders),
            'contract_types' => array_keys($contractTypes),
            'positions' => array_keys($positions),
            'areas' => array_keys($areas),
            'shifts' => array_keys($shifts),
            'total_evaluations' => $evaluations->count(),
        ];
    }
}
