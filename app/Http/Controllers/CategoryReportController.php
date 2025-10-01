<?php

namespace App\Http\Controllers;

use App\Services\CategoryReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class CategoryReportController extends Controller
{
    protected $categoryReportService;

    public function __construct(CategoryReportService $categoryReportService)
    {
        $this->categoryReportService = $categoryReportService;
    }

    /**
     * Obtiene y devuelve la distribución de respuestas por categoría y tipo
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getCategoryAnswerTypeDistribution(Request $request)
    {
        try {
            // Verificación de autorización - ajusta según tus necesidades
            $user = $request->user();
            if (! $user->hasRole('organization') && ! $user->hasRole('admin') && ! $user->hasRole('super-admin')) {
                return response()->json(['error' => 'No autorizado'], 403);
            }

            // Obtener los datos desde el servicio (por nivel de riesgo)
            $distribution = $this->categoryReportService->getCategoryRiskLevelDistribution();

            return response()->json($distribution);
        } catch (\Exception $e) {
            Log::error('Error al obtener la distribución de respuestas por categoría: '.$e->getMessage());

            return response()->json(['error' => 'Error al procesar la solicitud'], 500);
        }
    }

    /**
     * Muestra la vista del reporte de visualización de categorías
     *
     * @return \Inertia\Response
     */
    public function showCategoryReport(Request $request)
    {
        try {
            // Verificación de autorización - ajusta según tus necesidades
            $user = $request->user();
            if (! $user->hasRole('organization') && ! $user->hasRole('admin') && ! $user->hasRole('super-admin')) {
                abort(403, 'No autorizado');
            }

            // Obtener los datos desde el servicio para la carga inicial (por nivel de riesgo)
            $distribution = $this->categoryReportService->getCategoryRiskLevelDistribution();

            return Inertia::render('Reports/CategoryReport', [
                'categoryDistribution' => $distribution,
                'title' => 'Reporte por Categorías',
            ]);
        } catch (\Exception $e) {
            Log::error('Error al mostrar el reporte de categorías: '.$e->getMessage());
            abort(500, 'Error al procesar la solicitud');
        }
    }

    /**
     * Obtiene y devuelve la suma total del valor de respuestas por categoría
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getCategoryTotalScores(Request $request)
    {
        try {
            // Verificación de autorización - ajusta según tus necesidades
            $user = $request->user();
            if (! $user->hasRole('organization') && ! $user->hasRole('admin') && ! $user->hasRole('super-admin')) {
                return response()->json(['error' => 'No autorizado'], 403);
            }

            // Obtener los datos desde el servicio
            $totalScores = $this->categoryReportService->getCategoryTotalScores();

            return response()->json($totalScores);
        } catch (\Exception $e) {
            Log::error('Error al obtener la suma total de respuestas por categoría: '.$e->getMessage());

            return response()->json(['error' => 'Error al procesar la solicitud'], 500);
        }
    }

    /**
     * Muestra la vista del reporte de puntuación total por categoría
     *
     * @return \Inertia\Response
     */
    public function showCategoryTotalScoreReport(Request $request)
    {
        try {
            // Verificación de autorización - ajusta según tus necesidades
            $user = $request->user();
            if (! $user->hasRole('organization') && ! $user->hasRole('admin') && ! $user->hasRole('super-admin')) {
                abort(403, 'No autorizado');
            }

            // Obtener los datos desde el servicio para la carga inicial
            $totalScores = $this->categoryReportService->getCategoryTotalScores();

            return Inertia::render('Reports/CategoryTotalScoreReport', [
                'categoryTotalScores' => $totalScores,
                'title' => 'Puntuación Total por Categorías',
            ]);
        } catch (\Exception $e) {
            Log::error('Error al mostrar el reporte de puntuación total por categorías: '.$e->getMessage());
            abort(500, 'Error al procesar la solicitud');
        }
    }
}
