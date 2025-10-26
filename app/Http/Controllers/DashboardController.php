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
            $data['evaluations'] = $this->evaluationService->getOrganizationEvaluations($user->organization);
            // Fetch personal_ids specifically for this organization for accurate demographics
            // Assuming EvaluationService can provide this or we query Questions table
            // Placeholder: $personalIdsForDemographics = $this->evaluationService->getPersonalIdsForOrganization($user->organization);
            // A simpler way for now, if acceptable, is to fetch all guide V answers and let the service handle it.
            // If filtering is strictly required per-org, we need the list of personal_ids.
            // Let's assume for now the ReportService implicitly handles context or we fetch all.

            // $data['demographic_data'] = $this->evaluationService->getDemographicData($user->organization); // Keep this if still used elsewhere?
            $data['category_qualifications'] = $this->reportService->calculateCategoryQualifications();
            $data['domain_qualifications'] = $this->reportService->calculateDomainQualifications();
            // NEW: Get Demographic Distributions
            $data['demographic_distributions'] = $this->reportService->getDemographicDistributions(); // Pass specific $personalIds if available/needed

            $data['isAdmin'] = false;
            $data['isSuperAdmin'] = false;
        } elseif ($user->hasRole('admin')) {
            $data['organizations'] = $this->evaluationService->getAllEvaluationsByOrganization();
            // Admins/SuperAdmins see global demographics
            $data['demographic_distributions'] = $this->reportService->getDemographicDistributions();
            $data['isAdmin'] = true;
            $data['isSuperAdmin'] = false;

            // Add online evaluation counts for each organization
            $this->addOnlineEvaluationCounts($data['organizations']);
        } elseif ($user->hasRole('super-admin')) {
            $data['organizations'] = $this->evaluationService->getAllEvaluationsByOrganization();
            // Admins/SuperAdmins see global demographics
            $data['demographic_distributions'] = $this->reportService->getDemographicDistributions();
            $data['isAdmin'] = false;
            $data['isSuperAdmin'] = true;

            // Add online evaluation counts for each organization
            $this->addOnlineEvaluationCounts($data['organizations']);
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
    protected function addOnlineEvaluationCounts(&$organizations): void
    {
        foreach ($organizations as &$org) {
            // Count paper evaluations with source='online' grouped by personal_folio
            $onlineEvaluationsCount = \App\Models\PaperEvaluation::where('organization_id', $org['id'])
                ->where('source', 'online')
                ->where('processing_status', 'completed')
                ->distinct('personal_folio')
                ->count('personal_folio');

            $org['online_evaluations_count'] = $onlineEvaluationsCount;
        }
    }
}
