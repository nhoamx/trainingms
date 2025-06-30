<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Evaluation;
use App\Models\Organization;
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
     * Obtiene y devuelve el conteo global de personas únicas por opción (A-E)
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getGlobalPersonCounts(Request $request)
    {
        try {
            // Verificación de autorización
            $user = $request->user();
            if (!$user->hasRole('organization') && !$user->hasRole('admin') && !$user->hasRole('super-admin')) {
                return response()->json(['error' => 'No autorizado'], 403);
            }

            // Obtener los datos desde el servicio
            $personCounts = $this->globalResponseService->getGlobalPersonCounts();

            return response()->json($personCounts);
        } catch (\Exception $e) {
            Log::error("Error al obtener el conteo global de personas: " . $e->getMessage());
            return response()->json(['error' => 'Error al procesar la solicitud'], 500);
        }
    }

    /**
     * Obtiene y devuelve el conteo de personas únicas por categoría y opción
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getPersonCountByCategoryAndResponse(Request $request)
    {
        try {
            // Verificación de autorización
            $user = $request->user();
            if (!$user->hasRole('organization') && !$user->hasRole('admin') && !$user->hasRole('super-admin')) {
                return response()->json(['error' => 'No autorizado'], 403);
            }

            // Obtener los datos desde el servicio
            $categoryPersonCounts = $this->globalResponseService->getPersonCountByCategoryAndResponse();

            return response()->json($categoryPersonCounts);
        } catch (\Exception $e) {
            Log::error("Error al obtener el conteo de personas por categoría: " . $e->getMessage());
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

    /**
     * Muestra la vista del reporte global de personas
     *
     * @param Request $request
     * @return \Inertia\Response
     */
    public function showGlobalPersonReport(Request $request)
    {
        try {
            // Verificación de autorización
            $user = $request->user();
            if (!$user->hasRole('organization') && !$user->hasRole('admin') && !$user->hasRole('super-admin')) {
                abort(403, 'No autorizado');
            }

            // Obtener los datos desde el servicio para la carga inicial
            $globalPersonCounts = $this->globalResponseService->getGlobalPersonCounts();
            $categoryPersonCounts = $this->globalResponseService->getPersonCountByCategoryAndResponse();

            return Inertia::render('Reports/GlobalPersonReport', [
                'globalPersonCounts' => $globalPersonCounts,
                'categoryPersonCounts' => $categoryPersonCounts,
                'title' => 'Análisis Global de Personas'
            ]);
        } catch (\Exception $e) {
            Log::error("Error al mostrar el reporte global de personas: " . $e->getMessage());
            abort(500, 'Error al procesar la solicitud');
        }
    }

    /**
     * Muestra la vista de las respuestas de una persona
     * 
     */
    public function showPersonResponses($organizationId, $personalId)
    {
        $organization = Organization::find($organizationId);
        $evaluation = Evaluation::where('personal_id', $personalId)
            ->where('organization_id', $organizationId)
            ->where('reference_guide', 'III')
            ->latest()->first();


        // Obtener resultados de otras guías si estamos viendo la guía III
        $guideIResults = null;
        $guideVResults = null;
        $guideIIIResults = null;

        if ($evaluation->reference_guide === 'III') {
            // Buscar la evaluación más reciente de la guía I para el mismo personal_id
            $guideIResults = Evaluation::where('organization_id', $organization->id)
                ->where('reference_guide', 'I')
                ->where('personal_id', $evaluation->personal_id)
                ->latest()
                ->first();

            if ($guideIResults) {
                $answers = $guideIResults->questions()->pluck('answer', 'question');
                $guideIResults = [
                    'id' => $guideIResults->id,
                    'folio' => $guideIResults->folio,
                    'created_at' => $guideIResults->created_at->format('Y-m-d H:i:s'),
                    'answers' => $answers
                ];
            }

            // Buscar la evaluación más reciente de la guía V para el mismo personal_id
            $guideVResults = Evaluation::where('organization_id', $organization->id)
                ->where('reference_guide', 'V')
                ->where('personal_id', $evaluation->personal_id)
                ->latest()
                ->first();

            if ($guideVResults) {
                $questions = $guideVResults->questions()->select('id', 'question', 'answer')->get();
                $guideVResults = [
                    'id' => $guideVResults->id,
                    'folio' => $guideVResults->folio,
                    'created_at' => $guideVResults->created_at->format('Y-m-d H:i:s'),
                    'questions' => $questions
                ];
            }

            // Obtener las preguntas y respuestas de la guía III (actual)
            $questions = $evaluation->questions()->select('id', 'question', 'answer')->get();
            $guideIIIResults = [
                'id' => $evaluation->id,
                'folio' => $evaluation->folio,
                'created_at' => $evaluation->created_at->format('Y-m-d H:i:s'),
                'questions' => $questions
            ];
        }

        $results = Category::with(['domains.dimensions.answers' => function($query) use ($evaluation) {
            $query->where('evaluation_id', $evaluation->id)
                ->select('id', 'dimension_id', 'question', 'answer', 'score');
        }])->get()->map(function ($category) {
            $categoryScore = 0;
            $details = [];

            foreach ($category->domains as $domain) {
                $domainScore = 0;

                foreach ($domain->dimensions as $dimension) {
                    // Calcular score de la dimensión sumando los scores de sus respuestas
                    $dimensionScore = $dimension->answers->sum('score');

                    // Agregar al score del dominio
                    $domainScore += $dimensionScore;

                    // Agregar detalles de las respuestas
                    foreach ($dimension->answers as $answer) {
                        $details[] = [
                            'categoria' => [
                                'nombre' => $category->name,
                                'puntaje' => 0 // Se actualizará después
                            ],
                            'dominio' => [
                                'nombre' => $domain->name,
                                'puntaje' => 0 // Se actualizará después
                            ],
                            'dimension' => $dimension->name,
                            'item' => $answer->question,
                            'respuesta' => $answer->answer,
                            'puntaje' => $answer->score
                        ];
                    }
                }

                // Actualizar el score de dominio en todos los registros relacionados
                foreach ($details as &$detail) {
                    if ($detail['dominio']['nombre'] === $domain->name) {
                        $detail['dominio']['puntaje'] = $domainScore;
                    }
                }

                // Agregar al score de la categoría
                $categoryScore += $domainScore;
            }

            // Actualizar el score de categoría en todos los registros
            foreach ($details as &$detail) {
                if ($detail['categoria']['nombre'] === $category->name) {
                    $detail['categoria']['puntaje'] = $categoryScore;
                }
            }

            return $details;
        })->flatten(1)->values();

        return Inertia::render('Results/Detail', [
            'organization' => $organization->only('id', 'name'),
            'evaluation' => [
                'id' => $evaluation->id,
                'folio' => $evaluation->folio,
                'created_at' => $evaluation->created_at->format('Y-m-d H:i:s'),
                'reference_guide' => $evaluation->reference_guide,
            ],
            'results' => $results,
            'guideIResults' => $guideIResults,
            'guideVResults' => $guideVResults,
            'guideIIIResults' => $guideIIIResults
        ]);

    }
}
