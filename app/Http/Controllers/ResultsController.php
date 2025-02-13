<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use App\Models\Category;
use App\Models\Evaluation;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ResultsController extends Controller
{
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
        if ($evaluation->organization_id !== $organization->id) {
            abort(403, 'La evaluación no pertenece a esta organización');
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
            ],
            'results' => $results
        ]);
    }
}
