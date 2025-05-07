<?php

namespace App\Http\Controllers;

use App\Services\DomainReportService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Log;

class DomainReportController extends Controller
{
    protected $domainReportService;

    public function __construct(DomainReportService $domainReportService)
    {
        $this->domainReportService = $domainReportService;
    }

    /**
     * Obtiene y devuelve la distribución de respuestas por dominio y tipo
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getDomainAnswerTypeDistribution(Request $request)
    {
        try {
            // Verificación de autorización - ajusta según tus necesidades
            $user = $request->user();
            if (!$user->hasRole('organization') && !$user->hasRole('admin') && !$user->hasRole('super-admin')) {
                return response()->json(['error' => 'No autorizado'], 403);
            }

            // Obtener el organization_id del usuario autenticado o del request
            $organizationId = $user->organization_id ?? $request->input('organization_id');
            if (!$organizationId) {
                return response()->json(['error' => 'No se encontró organization_id'], 400);
            }

            // Obtener los datos desde el servicio
            $distribution = $this->domainReportService->getDomainAnswerTypeDistribution('III', $organizationId);

            return response()->json($distribution);
        } catch (\Exception $e) {
            Log::error("Error al obtener la distribución de personas por dominio y nivel de riesgo: " . $e->getMessage());
            return response()->json(['error' => 'Error al procesar la solicitud'], 500);
        }
    }

    /**
     * Muestra la vista del reporte de visualización de dominios
     *
     * @param Request $request
     * @return \Inertia\Response
     */
    public function showDomainReport(Request $request)
    {
        try {
            // Verificación de autorización - ajusta según tus necesidades
            $user = $request->user();
            if (!$user->hasRole('organization') && !$user->hasRole('admin') && !$user->hasRole('super-admin')) {
                abort(403, 'No autorizado');
            }

            // Obtener los datos desde el servicio para la carga inicial
            $distribution = $this->domainReportService->getDomainAnswerTypeDistribution();

            return Inertia::render('Reports/DomainReport', [
                'domainDistribution' => $distribution,
                'title' => 'Reporte por Dominios'
            ]);
        } catch (\Exception $e) {
            Log::error("Error al mostrar el reporte de dominios: " . $e->getMessage());
            abort(500, 'Error al procesar la solicitud');
        }
    }

    /**
     * Obtiene y devuelve la suma total del valor de respuestas por dominio
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getDomainTotalScores(Request $request)
    {
        try {
            // Verificación de autorización - ajusta según tus necesidades
            $user = $request->user();
            if (!$user->hasRole('organization') && !$user->hasRole('admin') && !$user->hasRole('super-admin')) {
                return response()->json(['error' => 'No autorizado'], 403);
            }

            // Obtener los datos desde el servicio
            $totalScores = $this->domainReportService->getDomainTotalScores();

            return response()->json($totalScores);
        } catch (\Exception $e) {
            Log::error("Error al obtener la suma total de respuestas por dominio: " . $e->getMessage());
            return response()->json(['error' => 'Error al procesar la solicitud'], 500);
        }
    }

    /**
     * Muestra la vista del reporte de puntuación total por dominio
     *
     * @param Request $request
     * @return \Inertia\Response
     */
    public function showDomainTotalScoreReport(Request $request)
    {
        try {
            // Verificación de autorización - ajusta según tus necesidades
            $user = $request->user();
            if (!$user->hasRole('organization') && !$user->hasRole('admin') && !$user->hasRole('super-admin')) {
                abort(403, 'No autorizado');
            }

            // Obtener los datos desde el servicio para la carga inicial
            $totalScores = $this->domainReportService->getDomainTotalScores();

            return Inertia::render('Reports/DomainTotalScoreReport', [
                'domainTotalScores' => $totalScores,
                'title' => 'Puntuación Total por Dominios'
            ]);
        } catch (\Exception $e) {
            Log::error("Error al mostrar el reporte de puntuación total por dominios: " . $e->getMessage());
            abort(500, 'Error al procesar la solicitud');
        }
    }
}
