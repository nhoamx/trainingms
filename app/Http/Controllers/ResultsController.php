<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use App\Models\Category;
use App\Models\Evaluation;
use App\Models\Question;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ResultsController extends Controller
{
    use AuthorizesRequests;

    public function organizationResults(Organization $organization, Request $request)
    {
        // Si se proporciona un folio específico, buscar esa evaluación
        if ($request->has('folio')) {
            $evaluation = $organization->evaluations()
                ->where('folio', $request->folio)
                ->first();
        } else {
            // Si no se proporciona folio, usar la última evaluación
            $evaluation = $organization->evaluations()->latest()->first();
        }

        if (!$evaluation) {
            return response()->json(['error' => 'No evaluation found for this organization'], 404);
        }

        $results = Category::with(['domains.dimensions' => function($query) use ($evaluation) {
            $query->withSum(['answers' => function($query) use ($evaluation) {
                $query->where('evaluation_id', $evaluation->id);
            }], 'score');
        }])->get()->map(function ($category) {
            $categoryScore = 0;

            $domains = $category->domains->map(function ($domain) use (&$categoryScore) {
                $domainScore = 0;

                $dimensions = $domain->dimensions->map(function ($dimension) use (&$domainScore) {
                    $score = $dimension->answers_sum_score ?? 0;
                    $domainScore += $score;

                    return [
                        'id' => $dimension->id,
                        'name' => $dimension->name,
                        'score' => $score
                    ];
                });

                $categoryScore += $domainScore;

                return [
                    'id' => $domain->id,
                    'name' => $domain->name,
                    'score' => $domainScore,
                    'dimensions' => $dimensions
                ];
            });

            return [
                'id' => $category->id,
                'name' => $category->name,
                'score' => $categoryScore,
                'domains' => $domains
            ];
        });

        return response()->json([
            'organization' => $organization->name,
            'evaluation_id' => $evaluation->id,
            'folio' => $evaluation->folio,
            'created_at' => $evaluation->created_at,
            'results' => $results
        ]);
    }

    public function listResults(Organization $organization)
    {
        $this->authorize('view-organization-results', $organization);

        $evaluations = $organization->evaluations()
            ->where('reference_guide', 'III')
            ->select('id', 'folio', 'created_at')
            ->withSum('answers', 'score')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($evaluation) {
                return [
                    'id' => $evaluation->id,
                    'folio' => $evaluation->folio,
                    'created_at' => $evaluation->created_at->format('Y-m-d H:i:s'),
                    'total_score' => $evaluation->answers_sum_score ?? 0
                ];
            });

        return Inertia::render('Results/List', [
            'organization' => $organization->only('id', 'name'),
            'evaluations' => $evaluations
        ]);
    }

    public function showDetailedResults(Organization $organization, Evaluation $evaluation)
    {
        $this->authorize('view-organization-results', $organization);

        if ($evaluation->organization_id !== $organization->id) {
            abort(403, 'La evaluación no pertenece a esta organización');
        }

        // Obtener resultados de las otras guías relacionadas
        $guideIResults = null;
        $guideVResults = null;
        $guideIIIResults = null;
        $evaluationForResults = $evaluation;

        if ($evaluation->reference_guide === 'III') {
            // Cargar las evaluaciones de la guía I y V relacionadas
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
                    'answers' => $answers,
                ];
            }

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
                    'questions' => $questions,
                ];
            }

            $questions = $evaluation->questions()->select('id', 'question', 'answer')->get();
            $guideIIIResults = [
                'id' => $evaluation->id,
                'folio' => $evaluation->folio,
                'created_at' => $evaluation->created_at->format('Y-m-d H:i:s'),
                'questions' => $questions,
            ];
        } elseif ($evaluation->reference_guide === 'I') {
            // Resultados de la guía I corresponden a esta evaluación
            $answers = $evaluation->questions()->pluck('answer', 'question');
            $guideIResults = [
                'id' => $evaluation->id,
                'folio' => $evaluation->folio,
                'created_at' => $evaluation->created_at->format('Y-m-d H:i:s'),
                'answers' => $answers,
            ];

            // Intentar cargar la guía V relacionada (independiente de que exista la III)
            $guideVResultsModel = Evaluation::where('organization_id', $organization->id)
                ->where('reference_guide', 'V')
                ->where('personal_id', $evaluation->personal_id)
                ->latest()
                ->first();

            if ($guideVResultsModel) {
                $questions = $guideVResultsModel->questions()->select('id', 'question', 'answer')->get();
                $guideVResults = [
                    'id' => $guideVResultsModel->id,
                    'folio' => $guideVResultsModel->folio,
                    'created_at' => $guideVResultsModel->created_at->format('Y-m-d H:i:s'),
                    'questions' => $questions,
                ];
            }

            // Buscar evaluación de la guía III relacionada
            $relatedGuideIII = Evaluation::where('organization_id', $organization->id)
                ->where('reference_guide', 'III')
                ->where('personal_id', $evaluation->personal_id)
                ->latest()
                ->first();

            if ($relatedGuideIII) {
                $questions = $relatedGuideIII->questions()->select('id', 'question', 'answer')->get();
                $guideIIIResults = [
                    'id' => $relatedGuideIII->id,
                    'folio' => $relatedGuideIII->folio,
                    'created_at' => $relatedGuideIII->created_at->format('Y-m-d H:i:s'),
                    'questions' => $questions,
                ];
                $evaluationForResults = $relatedGuideIII;
            }
        } elseif ($evaluation->reference_guide === 'V') {
            // Resultados de la guía V corresponden a esta evaluación
            $questions = $evaluation->questions()->select('id', 'question', 'answer')->get();
            $guideVResults = [
                'id' => $evaluation->id,
                'folio' => $evaluation->folio,
                'created_at' => $evaluation->created_at->format('Y-m-d H:i:s'),
                'questions' => $questions,
            ];

            // Siempre intentar cargar la guía I relacionada
            $guideIResultsModel = Evaluation::where('organization_id', $organization->id)
                ->where('reference_guide', 'I')
                ->where('personal_id', $evaluation->personal_id)
                ->latest()
                ->first();

            if ($guideIResultsModel) {
                $answers = $guideIResultsModel->questions()->pluck('answer', 'question');
                $guideIResults = [
                    'id' => $guideIResultsModel->id,
                    'folio' => $guideIResultsModel->folio,
                    'created_at' => $guideIResultsModel->created_at->format('Y-m-d H:i:s'),
                    'answers' => $answers,
                ];
            }

            // Buscar evaluación de la guía III relacionada
            $relatedGuideIII = Evaluation::where('organization_id', $organization->id)
                ->where('reference_guide', 'III')
                ->where('personal_id', $evaluation->personal_id)
                ->latest()
                ->first();

            if ($relatedGuideIII) {
                $questions = $relatedGuideIII->questions()->select('id', 'question', 'answer')->get();
                $guideIIIResults = [
                    'id' => $relatedGuideIII->id,
                    'folio' => $relatedGuideIII->folio,
                    'created_at' => $relatedGuideIII->created_at->format('Y-m-d H:i:s'),
                    'questions' => $questions,
                ];
                $evaluationForResults = $relatedGuideIII;
            }
        }

        $results = Category::with(['domains.dimensions.answers' => function($query) use ($evaluationForResults) {
            $query->where('evaluation_id', $evaluationForResults->id)
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

    /**
     * Actualiza la respuesta de una pregunta de la Guía de Referencia V.
     *
     * @param Request $request
     * @param Evaluation $evaluation
     * @param string $question
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateGuideVQuestion(Request $request, Evaluation $evaluation, Question $question)
    {
        // Validar que la evaluación sea de la guía V
        if ($evaluation->reference_guide !== 'V') {
            return response()->json(['error' => 'La evaluación no pertenece a la Guía de Referencia V'], 400);
        }

        // Validar que la pregunta pertenezca a esta evaluación
        if ($question->evaluation_id !== $evaluation->id) {
            return response()->json(['error' => 'La pregunta no pertenece a esta evaluación'], 400);
        }

        // Validar datos de entrada
        $validated = $request->validate([
            'answer' => 'required|string|max:1000',
        ]);

        // Actualizar la respuesta
        $question->update([
            'answer' => $validated['answer']
        ]);

        return redirect()->back()->with([
            'success' => true,
            'message' => 'Respuesta actualizada correctamente',
            'question' => $question->only('id', 'question', 'answer')
        ]);
    }
    
    /**
     * Actualiza la respuesta de una pregunta de la Guía de Referencia III.
     *
     * @param Request $request
     * @param Evaluation $evaluation
     * @param Question $question
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateGuideIIIQuestion(Request $request, Evaluation $evaluation, Question $question)
    {
        // Validar que la evaluación sea de la guía III
        if ($evaluation->reference_guide !== 'III') {
            return response()->json(['error' => 'La evaluación no pertenece a la Guía de Referencia III'], 400);
        }

        // Validar que la pregunta pertenezca a esta evaluación
        if ($question->evaluation_id !== $evaluation->id) {
            return response()->json(['error' => 'La pregunta no pertenece a esta evaluación'], 400);
        }

        // Validar datos de entrada
        $validated = $request->validate([
            'answer' => 'required|string|max:1000',
        ]);

        // Actualizar la respuesta
        $question->update([
            'answer' => $validated['answer']
        ]);

        return redirect()->back()->with([
            'success' => true,
            'message' => 'Respuesta actualizada correctamente',
            'question' => $question->only('id', 'question', 'answer')
        ]);
    }
}
