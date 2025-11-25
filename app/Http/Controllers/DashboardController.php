<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Dimension;
use App\Models\Domain;
use App\Models\Organization;
use App\Services\EvaluationService;
use App\Services\ReportService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    protected $evaluationService;

    protected $reportService;

    public function __construct(
        EvaluationService $evaluationService,
        ReportService $reportService
    ) {
        $this->evaluationService = $evaluationService;
        $this->reportService = $reportService;
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $data = [];

        // Determine the scope for fetching data (all personnel for admin/super-admin, specific org for org user)
        $personalIdsForDemographics = [];

        if ($user->hasRole('organization') && $user->organization) {
            // Base flags
            $data['isAdmin'] = false;
            $data['isSuperAdmin'] = false;

            $organization = $user->organization;
            $data['organization'] = [
                'id' => $organization->id,
                'name' => $organization->name,
            ];

            // Fetch all individual evaluations (one row per evaluation for status per guía)
            $rawEvaluations = \App\Models\PaperEvaluation::where('organization_id', $organization->id)
                ->orderByDesc('created_at')
                ->get();

            $data['recent_evaluations'] = $rawEvaluations->map(function ($ev) {
                $evaluationType = $ev->evaluation_type; // referencia_i, referencia_iii, referencia_v, cisneros, likert

                // Detect missing demographic data
                $demographicMissing = false;
                $gender = null;
                if (in_array($evaluationType, ['referencia_v'])) { // referencia_v holds demographic_data
                    if (! is_array($ev->demographic_data) || empty($ev->demographic_data)) {
                        $demographicMissing = true;
                    } else {
                        if (! isset($ev->demographic_data['sexo']) || empty($ev->demographic_data['sexo'])) {
                            $demographicMissing = true;
                        } else {
                            $gender = strtolower($ev->demographic_data['sexo']);
                        }
                    }
                } elseif ($evaluationType === 'likert') {
                    // For likert we rely on likert_answers genero only
                    if (is_array($ev->likert_answers) && isset($ev->likert_answers['genero']) && ! empty($ev->likert_answers['genero'])) {
                        $gender = strtolower($ev->likert_answers['genero']);
                    } else {
                        $demographicMissing = true;
                    }
                } else {
                    // For other references & cisneros, attempt to infer gender from likert answers if present
                    if (is_array($ev->likert_answers) && isset($ev->likert_answers['genero']) && ! empty($ev->likert_answers['genero'])) {
                        $gender = strtolower($ev->likert_answers['genero']);
                    }
                }

                // Detect missing questions (only for non-likert types)
                $missingQuestions = false;
                if (in_array($evaluationType, ['referencia_i', 'referencia_iii', 'referencia_v', 'cisneros'])) {
                    $answers = match ($evaluationType) {
                        'referencia_i' => $ev->referencia_i_answers,
                        'referencia_iii' => $ev->referencia_iii_answers ?: $ev->referencia_iii_conditional,
                        'referencia_v' => $ev->demographic_data, // treat demographic fields as answers presence
                        'cisneros' => $ev->cisneros_answers,
                        default => null,
                    };
                    if (is_array($answers)) {
                        foreach ($answers as $val) {
                            if ($val === null || $val === '' || (is_string($val) && trim($val) === '')) {
                                $missingQuestions = true;
                                break;
                            }
                        }
                    } else {
                        $missingQuestions = true; // no answers array means missing
                    }
                }

                // Determine status combining missing flags (likert ignores missingQuestions)
                $status = 'completo';
                if ($evaluationType === 'likert') {
                    if ($demographicMissing) {
                        $status = 'faltan_datos';
                    }
                } else {
                    if ($missingQuestions && $demographicMissing) {
                        $status = 'faltan_preguntas_y_datos';
                    } elseif ($missingQuestions) {
                        $status = 'faltan_preguntas';
                    } elseif ($demographicMissing) {
                        $status = 'faltan_datos';
                    }
                }

                return [
                    'id' => $ev->id,
                    'personal_folio' => $ev->personal_folio,
                    'evaluee_name' => $ev->evaluee_name,
                    'evaluation_type' => $evaluationType,
                    'status' => $status,
                    'missing_questions' => $missingQuestions,
                    'demographic_missing' => $demographicMissing,
                    'gender' => $gender,
                    'created_at' => $ev->created_at?->toDateTimeString(),
                ];
            });

            // Evaluation counts aggregated (ATS combines Referencias I, III, V)
            $counts = \App\Models\PaperEvaluation::where('organization_id', $organization->id)
                ->selectRaw('evaluation_type, COUNT(*) as total')
                ->groupBy('evaluation_type')
                ->pluck('total', 'evaluation_type');

            $atsCount = ($counts['referencia_i'] ?? 0) + ($counts['referencia_iii'] ?? 0) + ($counts['referencia_v'] ?? 0);
            $cisnerosCount = ($counts['cisneros'] ?? 0);
            $climaCount = ($counts['likert'] ?? 0);

            $stats = [];
            if ($atsCount > 0) {
                $stats[] = [
                    'key' => 'ats',
                    'label' => 'ATS',
                    'description' => 'Conjunto de instrumentos Referencia I, III y V.',
                    'count' => $atsCount,
                    'highlight' => true,
                ];
            }
            if ($cisnerosCount > 0) {
                $stats[] = [
                    'key' => 'cisneros',
                    'label' => 'Cisneros',
                    'description' => 'Medición de violencia / acoso laboral.',
                    'count' => $cisnerosCount,
                    'highlight' => false,
                ];
            }
            if ($climaCount > 0) {
                $stats[] = [
                    'key' => 'clima_laboral',
                    'label' => 'Clima laboral',
                    'description' => 'Percepción general del clima y satisfacción.',
                    'count' => $climaCount,
                    'highlight' => true,
                ];
            }
            $data['evaluation_stats'] = $stats;

            // Routes for actions per instrument (existing routes or fallback)
            $data['instrument_routes'] = [
                'ats' => route('organization.results.list', ['organization' => $organization->id]).'?grupo=ats',
                'cisneros' => route('organization.results.list', ['organization' => $organization->id]).'?tipo=cisneros',
                'clima_laboral' => route('organization.likert.report', ['organization' => $organization->id]),
                'results_list' => route('organization.results.list', ['organization' => $organization->id]),
            ];

            // High-level help / guidance (static copy for initial UX)
            $data['onboarding_tips'] = [
                'Explora cada tarjeta para acceder al reporte específico.',
                'Usa el listado reciente para validar procesamiento y folios.',
                'Descarga reportes avanzados desde la sección de resultados detallados.',
            ];
        } elseif ($user->hasRole('admin')) {
            $data['organizations'] = $this->evaluationService->getAllEvaluationsByOrganization();
            // Admins/SuperAdmins see global demographics
            $data['demographic_distributions'] = $this->reportService->getDemographicDistributions();
            $data['isAdmin'] = true;
            $data['isSuperAdmin'] = false;

            // Add online evaluation counts for each organization
            $data['organizations'] = $this->addOnlineEvaluationCounts($data['organizations']);
            // Add likert-only flag per organization
            $data['organizations'] = $this->addLikertOnlyFlag($data['organizations']);
        } elseif ($user->hasRole('super-admin')) {
            $data['organizations'] = $this->evaluationService->getAllEvaluationsByOrganization();
            // Admins/SuperAdmins see global demographics
            $data['demographic_distributions'] = $this->reportService->getDemographicDistributions();
            $data['isAdmin'] = false;
            $data['isSuperAdmin'] = true;

            // Add online evaluation counts for each organization
            $data['organizations'] = $this->addOnlineEvaluationCounts($data['organizations']);
            // Add likert-only flag per organization
            $data['organizations'] = $this->addLikertOnlyFlag($data['organizations']);
        }

        return Inertia::render('Dashboard', $data);
    }

    /**
     * Get raw answer distribution for a specific category.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getCategoryAnswerDistribution(Request $request, string $categoryId)
    {
        // Authorization check
        $user = $request->user();
        if (! $user->hasRole('organization') || ! $user->organization) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $distribution = $this->reportService->getCategoryAnswerDistribution($categoryId);

        return response()->json($distribution);
    }

    /**
     * Get raw answer distribution for a specific domain.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getDomainAnswerDistribution(Request $request, string $domainId)
    {
        // Authorization check
        $user = $request->user();
        if (! $user->hasRole('organization') || ! $user->organization) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $distribution = $this->reportService->getDomainAnswerDistribution($domainId);

        return response()->json($distribution);
    }

    public function uploadFiles(Request $request)
    {
        try {
            $fileName = $request->file->getClientOriginalName();
            $folioId = $request->folio_id;
            $organizationId = $request->organization_id;

            $request->file->storeAs('public/evaluations', $fileName);

            return response()->json(['message' => 'Archivo subido correctamente']);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function evaluationResults()
    {
        return Inertia::render('Evaluations/Results', [
            'title' => 'Resultados',
            'organizations' => Organization::all(),
        ]);
    }

    /**
     * Get dimension qualifications for a specific domain.
     */
    public function getDimensionQualifications(Request $request, string $domainId)
    {
        // Auth check
        $user = $request->user();
        if (! $user->hasRole('organization') || ! $user->organization) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $qualifications = $this->reportService->calculateDimensionQualifications($domainId);

        return response()->json($qualifications);
    }

    /**
     * Get raw answer distribution for a specific dimension.
     */
    public function getDimensionAnswerDistribution(Request $request, string $dimensionId)
    {
        // Auth check
        $user = $request->user();
        if (! $user->hasRole('organization') || ! $user->organization) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $distribution = $this->reportService->getDimensionAnswerDistribution($dimensionId);

        return response()->json($distribution);
    }

    public function reportByOrganization(Request $request)
    {
        $orgaization = $request->organization_id;

        $data['evaluations'] = $this->evaluationService->getOrganizationEvaluations($orgaization);

    }

    /**
     * Add online evaluation counts to organization data
     */
    protected function addOnlineEvaluationCounts($organizations)
    {
        return $organizations->map(function ($org) {
            // Count paper evaluations with source='online' grouped by personal_folio
            $onlineEvaluationsCount = \App\Models\PaperEvaluation::where('organization_id', $org['id'])
                ->where('source', 'online')
                ->where('processing_status', 'completed')
                ->distinct('personal_folio')
                ->count('personal_folio');

            $org['online_evaluations_count'] = $onlineEvaluationsCount;

            return $org;
        });
    }

    /**
     * Add a boolean flag indicating if the organization has only Likert paper evaluations completed.
     */
    protected function addLikertOnlyFlag($organizations)
    {
        return $organizations->map(function ($org) {
            $orgId = $org['id'];

            $baseQuery = \App\Models\PaperEvaluation::where('organization_id', $orgId)
                ->where('processing_status', 'completed');

            $likertCount = (clone $baseQuery)->where('evaluation_type', 'likert')->count();
            $otherCount = (clone $baseQuery)->whereIn('evaluation_type', ['referencia_i', 'referencia_iii', 'referencia_v', 'cisneros'])->count();

            $org['is_likert_only'] = $likertCount > 0 && $otherCount === 0;

            return $org;
        });
    }
}
