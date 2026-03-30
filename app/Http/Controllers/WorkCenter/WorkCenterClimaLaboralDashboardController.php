<?php

namespace App\Http\Controllers\WorkCenter;

use App\Exports\WorkCenterCommentsTemplateExport;
use App\Http\Controllers\Controller;
use App\Jobs\ProcessBulkCommentsImport;
use App\Jobs\ProcessBulkEvaluationImport;
use App\Models\BulkImportJob;
use App\Models\PaperEvaluation;
use App\Models\WorkCenter;
use App\Models\WorkCenterClimaSection;
use App\Models\WorkCenterConclusionsFile;
use App\Services\LikertScoreService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

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

        $canManageClima = $request->user()?->hasRole(['admin', 'super-admin']) ?? false;

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

        $sectionsQuery = $workCenter->climaSections();
        if (! $canManageClima) {
            $sectionsQuery->where('status', WorkCenterClimaSection::STATUS_PUBLISHED);
        }
        $sections = $sectionsQuery->get()->keyBy('section_key');

        $reportsQuery = $workCenter->climaReports()->orderByDesc('is_active')->orderByDesc('created_at');
        if (! $canManageClima) {
            $reportsQuery->where('is_published', true);
        }
        $reports = $reportsQuery->get()->map(fn ($report): array => [
            'id' => $report->id,
            'title' => $report->title,
            'language' => $report->language,
            'original_filename' => $report->original_filename,
            'file_size_human' => $report->file_size_human,
            'is_published' => $report->is_published,
            'is_active' => $report->is_active,
            'published_at' => $report->published_at?->format('Y-m-d H:i'),
            'created_at' => $report->created_at?->format('Y-m-d H:i'),
        ])->values()->all();

        $evidencesQuery = $workCenter->climaEvidences()->orderByDesc('created_at');
        if (! $canManageClima) {
            $evidencesQuery->where('is_published', true);
        }
        $evidences = $evidencesQuery->get()->map(fn ($evidence): array => [
            'id' => $evidence->id,
            'title' => $evidence->title,
            'description' => $evidence->description,
            'preview_url' => asset('storage/'.$evidence->storage_path),
            'original_filename' => $evidence->original_filename,
            'file_size_human' => $evidence->file_size_human,
            'is_published' => $evidence->is_published,
            'published_at' => $evidence->published_at?->format('Y-m-d H:i'),
            'created_at' => $evidence->created_at?->format('Y-m-d H:i'),
        ])->values()->all();

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
            'climaContent' => [
                'sections' => [
                    'analysis_department' => [
                        'content' => $sections->get('analysis_department')?->content,
                        'status' => $sections->get('analysis_department')?->status ?? WorkCenterClimaSection::STATUS_DRAFT,
                    ],
                    'analysis_position' => [
                        'content' => $sections->get('analysis_position')?->content,
                        'status' => $sections->get('analysis_position')?->status ?? WorkCenterClimaSection::STATUS_DRAFT,
                    ],
                    'recommendations' => [
                        'content' => $sections->get('recommendations')?->content,
                        'status' => $sections->get('recommendations')?->status ?? WorkCenterClimaSection::STATUS_DRAFT,
                    ],
                    'foda' => [
                        'content' => $sections->get('foda')?->content,
                        'status' => $sections->get('foda')?->status ?? WorkCenterClimaSection::STATUS_DRAFT,
                    ],
                    'recommendations_factors' => [
                        'content' => $sections->get('recommendations_factors')?->content,
                        'status' => $sections->get('recommendations_factors')?->status ?? WorkCenterClimaSection::STATUS_DRAFT,
                    ],
                    'report_card_config' => [
                        'content' => $sections->get('report_card_config')?->content,
                        'status' => $sections->get('report_card_config')?->status ?? WorkCenterClimaSection::STATUS_DRAFT,
                    ],
                    'conclusions' => [
                        'content' => $sections->get('conclusions')?->content,
                        'status' => $sections->get('conclusions')?->status ?? WorkCenterClimaSection::STATUS_DRAFT,
                    ],
                ],
                'reports' => $reports,
                'evidences' => $evidences,
            ],
            'canManageClima' => $canManageClima,
            'evaluations' => $evaluations,
            'conclusionsContent' => $this->getWorkCenterConclusionsContent($workCenter, $canManageClima),
        ]);
    }

    /**
     * Gather conclusions section and file slots for the work center dashboard.
     *
     * @return array{section: array{content: string|null, status: string}, files: array<int|string, mixed>}
     */
    private function getWorkCenterConclusionsContent(WorkCenter $workCenter, bool $canManage): array
    {
        $section = $workCenter->climaSections()
            ->where('section_key', 'conclusions_config')
            ->first();

        $filesQuery = $workCenter->conclusionsFiles()->orderBy('slot');
        if (! $canManage) {
            $filesQuery->where('is_published', true);
        }

        $files = $filesQuery->get()
            ->map(fn (WorkCenterConclusionsFile $file): array => [
                'id' => $file->id,
                'slot' => $file->slot,
                'title' => $file->title,
                'color' => $file->color,
                'original_filename' => $file->original_filename,
                'file_size_human' => $file->file_size_human,
                'is_published' => $file->is_published,
            ])
            ->keyBy('slot')
            ->toArray();

        return [
            'section' => [
                'content' => $section?->content,
                'status' => $section?->status ?? WorkCenterClimaSection::STATUS_DRAFT,
            ],
            'files' => $files,
        ];
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

    /**
     * Import/update Clima Laboral evaluations for a work center from an uploaded Excel file.
     */
    public function bulkUpdate(Request $request, WorkCenter $workCenter): JsonResponse
    {
        $this->authorize('viewWorkCenterDashboard', $workCenter);

        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls', 'max:10240'],
        ]);

        $file = $request->file('file');
        $filePath = $file->store('bulk-imports', 'local');

        $bulkImportJob = BulkImportJob::create([
            'organization_id' => $workCenter->organization_id,
            'work_center_id' => $workCenter->id,
            'user_id' => $request->user()->id,
            'file_name' => $file->getClientOriginalName(),
            'file_path' => $filePath,
            'source' => null,
            'evaluation_type' => 'likert',
            'status' => 'pending',
        ]);

        ProcessBulkEvaluationImport::dispatch($bulkImportJob);

        return response()->json([
            'success' => true,
            'message' => 'El archivo se está procesando en segundo plano. Los registros serán actualizados en breve.',
            'bulk_import_job_id' => $bulkImportJob->id,
        ]);
    }

    public function downloadCommentsTemplate(Request $request, WorkCenter $workCenter): BinaryFileResponse
    {
        $this->authorize('viewWorkCenterDashboard', $workCenter);

        $filename = 'plantilla_comentarios_'.str_replace(' ', '_', $workCenter->name).'_'.now()->format('Y-m-d').'.xlsx';

        return Excel::download(new WorkCenterCommentsTemplateExport($workCenter), $filename);
    }

    public function bulkCommentsUpdate(Request $request, WorkCenter $workCenter): JsonResponse
    {
        $this->authorize('viewWorkCenterDashboard', $workCenter);

        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls', 'max:10240'],
        ]);

        $file = $request->file('file');
        $filePath = $file->store('bulk-imports', 'local');

        $bulkImportJob = BulkImportJob::create([
            'organization_id' => $workCenter->organization_id,
            'work_center_id' => $workCenter->id,
            'user_id' => $request->user()->id,
            'file_name' => $file->getClientOriginalName(),
            'file_path' => $filePath,
            'source' => null,
            'evaluation_type' => 'likert',
            'status' => 'pending',
        ]);

        ProcessBulkCommentsImport::dispatch($bulkImportJob);

        return response()->json([
            'success' => true,
            'message' => 'El archivo de comentarios se está procesando en segundo plano. Verás el avance en breve.',
            'bulk_import_job_id' => $bulkImportJob->id,
        ]);
    }
}
