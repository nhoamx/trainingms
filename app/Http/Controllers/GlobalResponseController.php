<?php

namespace App\Http\Controllers;

use App\Services\GlobalResponseService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Log;

class GlobalResponseController extends Controller
{
    protected $globalResponseService;

    public function __construct(GlobalResponseService $globalResponseService)
    {
        $this->globalResponseService = $globalResponseService;
    }

    /**
     * Obtiene y devuelve el conteo global de respuestas por opción (A-E)
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getGlobalResponseCounts(Request $request)
    {
        try {
            // Verificación de autorización
            $user = $request->user();
            if (!$user->hasRole('organization') && !$user->hasRole('admin') && !$user->hasRole('super-admin')) {
                return response()->json(['error' => 'No autorizado'], 403);
            }

            // Obtener los datos desde el servicio
            $responseCounts = $this->globalResponseService->getGlobalResponseCounts();

            return response()->json($responseCounts);
        } catch (\Exception $e) {
            Log::error("Error al obtener el conteo global de respuestas: " . $e->getMessage());
            return response()->json(['error' => 'Error al procesar la solicitud'], 500);
        }
    }

    /**
     * Obtiene y devuelve el conteo de respuestas por categoría y opción
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getCategoryResponseCounts(Request $request)
    {
        try {
            // Verificación de autorización
            $user = $request->user();
            if (!$user->hasRole('organization') && !$user->hasRole('admin') && !$user->hasRole('super-admin')) {
                return response()->json(['error' => 'No autorizado'], 403);
            }

            // Obtener los datos desde el servicio
            $categoryCounts = $this->globalResponseService->getCategoryResponseCounts();

            return response()->json($categoryCounts);
        } catch (\Exception $e) {
            Log::error("Error al obtener el conteo de respuestas por categoría: " . $e->getMessage());
            return response()->json(['error' => 'Error al procesar la solicitud'], 500);
        }
    }

    /**
     * Muestra la vista del reporte global de respuestas
     *
     * @param Request $request
     * @return \Inertia\Response
     */
    public function showGlobalResponseReport(Request $request)
    {
        try {
            // Verificación de autorización
            $user = $request->user();
            if (!$user->hasRole('organization') && !$user->hasRole('admin') && !$user->hasRole('super-admin')) {
                abort(403, 'No autorizado');
            }

            // Obtener los datos desde el servicio para la carga inicial
            $globalCounts = $this->globalResponseService->getGlobalResponseCounts();
            $categoryCounts = $this->globalResponseService->getCategoryResponseCounts();

            return Inertia::render('Reports/GlobalResponseReport', [
                'globalCounts' => $globalCounts,
                'categoryCounts' => $categoryCounts,
                'title' => 'Análisis Global de Respuestas'
            ]);
        } catch (\Exception $e) {
            Log::error("Error al mostrar el reporte global de respuestas: " . $e->getMessage());
            abort(500, 'Error al procesar la solicitud');
        }
    }
}
