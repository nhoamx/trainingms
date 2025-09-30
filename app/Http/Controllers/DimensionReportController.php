<?php

namespace App\Http\Controllers;

use App\Services\DimensionReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class DimensionReportController extends Controller
{
    protected $dimensionReportService;

    public function __construct(DimensionReportService $dimensionReportService)
    {
        $this->dimensionReportService = $dimensionReportService;
    }

    /**
     * Obtiene y devuelve la distribución de respuestas por dimensión y tipo
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getDimensionRiskLevelDistribution(Request $request)
    {
        try {
            // Verificación de autorización - ajusta según tus necesidades
            $user = $request->user();
            if (! $user->hasRole('organization') && ! $user->hasRole('admin') && ! $user->hasRole('super-admin')) {
                return response()->json(['error' => 'No autorizado'], 403);
            }

            // Obtener los datos desde el servicio
            $distribution = $this->dimensionReportService->getDimensionRiskLevelDistribution();

            return response()->json($distribution);
        } catch (\Exception $e) {
            Log::error('Error al obtener la distribución de personas por nivel de riesgo y dimensión: '.$e->getMessage());

            return response()->json(['error' => 'Error al procesar la solicitud'], 500);
        }
    }

    /**
     * Muestra la vista del reporte de visualización de dimensiones
     *
     * @return \Inertia\Response
     */
    public function showDimensionReport(Request $request)
    {
        try {
            // Verificación de autorización - ajusta según tus necesidades
            $user = $request->user();
            if (! $user->hasRole('organization') && ! $user->hasRole('admin') && ! $user->hasRole('super-admin')) {
                abort(403, 'No autorizado');
            }

            // Obtener los datos desde el servicio para la carga inicial
            $distribution = $this->dimensionReportService->getDimensionRiskLevelDistribution();

            return Inertia::render('Reports/DimensionReport', [
                'dimensionDistribution' => $distribution,
                'title' => 'Reporte por Dimensiones',
            ]);
        } catch (\Exception $e) {
            Log::error('Error al mostrar el reporte de dimensiones: '.$e->getMessage());
            abort(500, 'Error al procesar la solicitud');
        }
    }

    /**
     * Obtiene y devuelve la suma total del valor de respuestas por dimensión
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getDimensionTotalScores(Request $request)
    {
        try {
            // Verificación de autorización - ajusta según tus necesidades
            $user = $request->user();
            if (! $user->hasRole('organization') && ! $user->hasRole('admin') && ! $user->hasRole('super-admin')) {
                return response()->json(['error' => 'No autorizado'], 403);
            }

            // Obtener los datos desde el servicio
            $totalScores = $this->dimensionReportService->getDimensionTotalScores();

            return response()->json($totalScores);
        } catch (\Exception $e) {
            Log::error('Error al obtener la suma total de respuestas por dimensión: '.$e->getMessage());

            return response()->json(['error' => 'Error al procesar la solicitud'], 500);
        }
    }

    /**
     * Muestra la vista del reporte de puntuación total por dimensión
     *
     * @return \Inertia\Response
     */
    public function showDimensionTotalScoreReport(Request $request)
    {
        try {
            // Verificación de autorización - ajusta según tus necesidades
            $user = $request->user();
            if (! $user->hasRole('organization') && ! $user->hasRole('admin') && ! $user->hasRole('super-admin')) {
                abort(403, 'No autorizado');
            }

            // Obtener los datos desde el servicio para la carga inicial
            $totalScores = $this->dimensionReportService->getDimensionTotalScores();

            return Inertia::render('Reports/DimensionTotalScoreReport', [
                'dimensionTotalScores' => $totalScores,
                'title' => 'Puntuación Total por Dimensiones',
            ]);
        } catch (\Exception $e) {
            Log::error('Error al mostrar el reporte de puntuación total por dimensiones: '.$e->getMessage());
            abort(500, 'Error al procesar la solicitud');
        }
    }
}
