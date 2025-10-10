<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Evaluation;
use App\Models\Organization;
use App\Models\PaperEvaluation;
use App\Models\Question;
use App\Services\PaperEvaluationScoreService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ResultsController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        protected PaperEvaluationScoreService $scoreService
    ) {}

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

        if (! $evaluation) {
            return response()->json(['error' => 'No evaluation found for this organization'], 404);
        }

        $results = Category::with(['domains.dimensions' => function ($query) use ($evaluation) {
            $query->withSum(['answers' => function ($query) use ($evaluation) {
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
                        'score' => $score,
                    ];
                });

                $categoryScore += $domainScore;

                return [
                    'id' => $domain->id,
                    'name' => $domain->name,
                    'score' => $domainScore,
                    'dimensions' => $dimensions,
                ];
            });

            return [
                'id' => $category->id,
                'name' => $category->name,
                'score' => $categoryScore,
                'domains' => $domains,
            ];
        });

        return response()->json([
            'organization' => $organization->name,
            'evaluation_id' => $evaluation->id,
            'folio' => $evaluation->folio,
            'created_at' => $evaluation->created_at,
            'results' => $results,
        ]);
    }

    public function listResults(Organization $organization)
    {
        $this->authorize('view-organization-results', $organization);

        // Group paper evaluations by personal_folio
        $evaluationGroups = PaperEvaluation::where('organization_id', $organization->id)
            ->where('source', 'paper')
            ->where('processing_status', 'completed')
            ->orderBy('personal_folio')
            ->orderBy('created_at', 'desc')
            ->get()
            ->groupBy('personal_folio')
            ->map(function ($evaluations, $personalFolio) {
                $evaluationTypes = $evaluations->pluck('evaluation_type')->unique()->values();

                // Get the Referencia III evaluation for score
                $referenciaIII = $evaluations->firstWhere('evaluation_type', 'referencia_iii');
                $totalScore = 0;

                if ($referenciaIII) {
                    $scores = $this->scoreService->calculateReferenciaIIIScores($referenciaIII);
                    $totalScore = $scores['total_score'];
                }

                return [
                    'personal_folio' => $personalFolio,
                    'evaluation_types' => $evaluationTypes,
                    'total_score' => $totalScore,
                    'created_at' => $evaluations->first()->created_at->format('Y-m-d H:i:s'),
                    'evaluations' => $evaluations->map(function ($eval) {
                        return [
                            'id' => $eval->id,
                            'folio' => $eval->folio,
                            'evaluation_type' => $eval->evaluation_type,
                        ];
                    }),
                ];
            })
            ->values();

        return Inertia::render('Results/List', [
            'organization' => $organization->only('id', 'name'),
            'evaluationGroups' => $evaluationGroups,
        ]);
    }

    public function showDetailedResults(Organization $organization, string $personalFolio)
    {
        $this->authorize('view-organization-results', $organization);

        // Get all evaluations for this personal folio
        $evaluations = PaperEvaluation::where('organization_id', $organization->id)
            ->where('personal_folio', $personalFolio)
            ->where('source', 'paper')
            ->where('processing_status', 'completed')
            ->get();

        if ($evaluations->isEmpty()) {
            abort(404, 'No se encontraron evaluaciones para este folio personal');
        }

        // Get individual evaluations by type
        $referenciaI = $evaluations->firstWhere('evaluation_type', 'referencia_i');
        $referenciaIII = $evaluations->firstWhere('evaluation_type', 'referencia_iii');
        $referenciaV = $evaluations->firstWhere('evaluation_type', 'referencia_v');
        $cisneros = $evaluations->firstWhere('evaluation_type', 'cisneros');

        // Calculate scores for Referencia III
        $results = [];
        $totalScore = 0;

        if ($referenciaIII) {
            $detailedResults = $this->scoreService->getDetailedResults($referenciaIII);
            $scores = $this->scoreService->calculateReferenciaIIIScores($referenciaIII);
            $totalScore = $scores['total_score'];
            $results = $detailedResults;
        }

        // Format Guide I results
        $guideIResults = null;
        if ($referenciaI) {
            $guideIResults = [
                'id' => $referenciaI->id,
                'folio' => $referenciaI->folio,
                'created_at' => $referenciaI->created_at->format('Y-m-d H:i:s'),
                'answers' => $referenciaI->referencia_i_answers ?? [],
            ];
        }

        // Format Guide III results
        $guideIIIResults = null;
        if ($referenciaIII) {
            $guideIIIResults = [
                'id' => $referenciaIII->id,
                'folio' => $referenciaIII->folio,
                'created_at' => $referenciaIII->created_at->format('Y-m-d H:i:s'),
                'answers' => $referenciaIII->referencia_iii_answers ?? [],
                'conditional' => $referenciaIII->referencia_iii_conditional ?? [],
                'citsats_s1' => $referenciaIII->citsats_s1 ?? [],
            ];
        }

        // Format Guide V results
        $guideVResults = null;
        if ($referenciaV) {
            $guideVResults = [
                'id' => $referenciaV->id,
                'folio' => $referenciaV->folio,
                'created_at' => $referenciaV->created_at->format('Y-m-d H:i:s'),
                'demographic_data' => $referenciaV->demographic_data ?? [],
            ];
        }

        // Format Cisneros results
        $cisnerosResults = null;
        if ($cisneros) {
            $cisnerosResults = [
                'id' => $cisneros->id,
                'folio' => $cisneros->folio,
                'created_at' => $cisneros->created_at->format('Y-m-d H:i:s'),
                'answers' => $cisneros->cisneros_answers ?? [],
            ];
        }

        return Inertia::render('Results/Detail', [
            'organization' => $organization->only('id', 'name'),
            'personalFolio' => $personalFolio,
            'evaluation' => [
                'id' => $referenciaIII?->id ?? $evaluations->first()->id,
                'folio' => $referenciaIII?->folio ?? $evaluations->first()->folio,
                'created_at' => $referenciaIII?->created_at->format('Y-m-d H:i:s') ?? $evaluations->first()->created_at->format('Y-m-d H:i:s'),
                'personal_folio' => $personalFolio,
            ],
            'totalScore' => $totalScore,
            'results' => $results,
            'guideIResults' => $guideIResults,
            'guideVResults' => $guideVResults,
            'guideIIIResults' => $guideIIIResults,
            'cisnerosResults' => $cisnerosResults,
        ]);
    }

    /**
     * Actualiza la respuesta de una pregunta de la Guía de Referencia V.
     *
     * @param  string  $question
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
            'answer' => $validated['answer'],
        ]);

        return redirect()->back()->with([
            'success' => true,
            'message' => 'Respuesta actualizada correctamente',
            'question' => $question->only('id', 'question', 'answer'),
        ]);
    }

    /**
     * Actualiza la respuesta de una pregunta de la Guía de Referencia III.
     *
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
            'answer' => $validated['answer'],
        ]);

        return redirect()->back()->with([
            'success' => true,
            'message' => 'Respuesta actualizada correctamente',
            'question' => $question->only('id', 'question', 'answer'),
        ]);
    }
}
