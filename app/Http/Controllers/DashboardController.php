<?php

namespace App\Http\Controllers;

use App\Models\Domain;
use App\Models\Organization;
use App\Services\EvaluationService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    protected $evaluationService;

    public function __construct(EvaluationService $evaluationService)
    {
        $this->evaluationService = $evaluationService;
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $data = [];

        if ($user->hasRole('organization') && $user->organization) {
            $data['evaluations'] = $this->evaluationService->getOrganizationEvaluations($user->organization);
            $data['demographic_data'] = $this->evaluationService->getDemographicData($user->organization);
            $data['isAdmin'] = false;
        } else if ($user->hasRole(['admin', 'super-admin'])) {
            $data['organizations'] = $this->evaluationService->getAllEvaluationsByOrganization();
            $data['isAdmin'] = true;
        }

        return Inertia::render('Dashboard', $data);
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
            'organizations' => Organization::all()
        ]);
    }
}
