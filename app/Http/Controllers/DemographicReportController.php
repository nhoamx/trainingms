<?php

namespace App\Http\Controllers;

use App\Services\DemographicReportService;
use App\Services\PaperEvaluationReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class DemographicReportController extends Controller
{
    protected $demographicReportService;

    protected $paperReportService;

    public function __construct(
        DemographicReportService $demographicReportService,
        PaperEvaluationReportService $paperReportService
    ) {
        $this->demographicReportService = $demographicReportService;
        $this->paperReportService = $paperReportService;
    }

    /**
     * Obtiene y devuelve la distribución demográfica por nivel de riesgo
     * UPDATED: Now uses PaperEvaluation model instead of legacy models
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getDemographicDistribution(Request $request)
    {
        try {
            // Verificación de autorización
            $user = $request->user();
            if (! $user->hasRole('organization') && ! $user->hasRole('admin') && ! $user->hasRole('super-admin')) {
                return response()->json(['error' => 'No autorizado'], 403);
            }

            // Obtener ID de organización desde parámetros o del usuario
            $organizationId = $request->get('organization_id') ?? $user->organization_id;

            // Use new PaperEvaluationReportService instead of legacy service
            $distribution = $this->paperReportService->getDemographicDistribution($organizationId);

            return response()->json($distribution);
        } catch (\Exception $e) {
            Log::error('Error al obtener la distribución demográfica: '.$e->getMessage());

            return response()->json(['error' => 'Error al procesar la solicitud'], 500);
        }
    }

    /**
     * Muestra la vista del reporte demográfico
     *
     * @return \Inertia\Response
     */
    public function showDemographicReport(Request $request)
    {
        try {
            // Verificación de autorización
            $user = $request->user();
            if (! $user->hasRole('organization') && ! $user->hasRole('admin') && ! $user->hasRole('super-admin')) {
                abort(403, 'No autorizado');
            }

            // Obtener ID de organización desde parámetros o del usuario
            $organizationId = $request->get('organization_id') ?? $user->organization_id;

            // Obtener los datos desde el servicio para la carga inicial
            $distribution = $this->paperReportService->getDemographicDistribution($organizationId);

            return Inertia::render('Reports/DemographicReport', [
                'demographicDistribution' => $distribution,
                'title' => 'Reporte Demográfico',
            ]);
        } catch (\Exception $e) {
            Log::error('Error al mostrar el reporte demográfico: '.$e->getMessage());
            abort(500, 'Error al procesar la solicitud');
        }
    }
}
