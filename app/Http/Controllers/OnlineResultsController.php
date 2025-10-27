<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use App\Models\PaperEvaluation;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class OnlineResultsController extends Controller
{
    /**
     * Mostrar la lista de evaluaciones online de una organización
     */
    public function index($organizationId)
    {
        $organization = Organization::findOrFail($organizationId);

        // Obtener evaluaciones online completadas
        $evaluations = PaperEvaluation::query()
            ->online()
            ->completed()
            ->where('organization_id', $organizationId)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($evaluation) {
                // Extraer datos demográficos básicos
                $demographicData = $evaluation->demographic_data ?? [];

                return [
                    'id' => $evaluation->id,
                    'folio' => $evaluation->folio,
                    'personal_folio' => $evaluation->personal_folio,
                    'quiz_name' => $evaluation->quiz_name ?? 'N/A',
                    'quiz_type' => $this->formatQuizType($evaluation->quiz_type),
                    'evaluation_type' => $this->formatEvaluationType($evaluation->evaluation_type),
                    'completed_at' => $evaluation->processed_at?->format('d/m/Y H:i') ?? $evaluation->created_at->format('d/m/Y H:i'),
                    'sexo' => $demographicData['sexo'] ?? 'N/A',
                    'edad' => $demographicData['edad'] ?? 'N/A',
                    'puesto' => $demographicData['datos_laborales']['ocupacion_puesto'] ?? $demographicData['ocupacion_puesto'] ?? 'N/A',
                ];
            });

        return Inertia::render('OnlineResults/List', [
            'organization' => [
                'id' => $organization->id,
                'name' => $organization->name,
            ],
            'evaluations' => $evaluations,
        ]);
    }

    /**
     * Mostrar los detalles de una evaluación online específica
     */
    public function show($organizationId, $id)
    {
        $organization = Organization::findOrFail($organizationId);
        $evaluation = PaperEvaluation::query()
            ->online()
            ->where('organization_id', $organizationId)
            ->findOrFail($id);

        // Preparar datos demográficos (Referencia V)
        $demographicData = $evaluation->demographic_data ?? [];

        // Preparar respuestas de Referencia I
        $referenciaIAnswers = $evaluation->referencia_i_answers ?? [];

        // Preparar respuestas de Referencia III
        $referenciaIIIAnswers = $evaluation->referencia_iii_answers ?? [];

        // Preparar respuestas condicionales (CITSAT)
        $citsatAnswers = $evaluation->referencia_iii_conditional ?? [];

        // Preparar respuestas de Cisneros
        $cisnerosAnswers = $evaluation->cisneros_answers ?? [];

        // Preparar custom fields
        $customFields = $evaluation->custom_fields ?? [];

        // Preparar imágenes del INE
        $ineImages = [];
        if (isset($demographicData['ine_frente'])) {
            $ineImages['ine_frente'] = [
                'path' => $demographicData['ine_frente'],
                'url' => Storage::url($demographicData['ine_frente']),
                'exists' => Storage::disk('public')->exists($demographicData['ine_frente']),
            ];
        }
        if (isset($demographicData['ine_reverso'])) {
            $ineImages['ine_reverso'] = [
                'path' => $demographicData['ine_reverso'],
                'url' => Storage::url($demographicData['ine_reverso']),
                'exists' => Storage::disk('public')->exists($demographicData['ine_reverso']),
            ];
        }

        // Obtener configuraciones de preguntas
        $traumaticQuestions = config('referencia_iii_reduced.acontecimientos_traumaticos.questions', []);
        $referenciaIQuestions = config('referencia_i', []);
        $referenciaIIIQuestions = config('referencia_iii', []);
        $escalaCisnerosQuestions = config('escala_cisneros', []);
        $referenciaVConfig = config('referencia_v', []);

        return Inertia::render('OnlineResults/Detail', [
            'organization' => [
                'id' => $organization->id,
                'name' => $organization->name,
            ],
            'evaluation' => [
                'id' => $evaluation->id,
                'folio' => $evaluation->folio,
                'personal_folio' => $evaluation->personal_folio,
                'quiz_name' => $evaluation->quiz_name ?? 'N/A',
                'quiz_type' => $evaluation->quiz_type,
                'evaluation_type' => $evaluation->evaluation_type,
                'completed_at' => $evaluation->processed_at?->format('d/m/Y H:i') ?? $evaluation->created_at->format('d/m/Y H:i'),
                'has_referencia_i' => $evaluation->hasReferenciaI(),
                'has_referencia_iii' => $evaluation->hasReferenciaIII(),
                'has_referencia_v' => $evaluation->hasReferenciaV(),
                'has_cisneros' => $evaluation->hasCisneros(),
            ],
            'answers' => [
                'demographic_data' => $demographicData,
                'referencia_i' => $referenciaIAnswers,
                'referencia_iii' => $referenciaIIIAnswers,
                'citsat' => $citsatAnswers,
                'cisneros' => $cisnerosAnswers,
                'custom_fields' => $customFields,
            ],
            'ine_images' => $ineImages,
            'questions_config' => [
                'traumatic_questions' => $traumaticQuestions,
                'referencia_i_questions' => $referenciaIQuestions,
                'referencia_iii_questions' => $referenciaIIIQuestions,
                'escala_cisneros_questions' => $escalaCisnerosQuestions,
                'referencia_v_config' => $referenciaVConfig,
            ],
        ]);
    }

    /**
     * Format quiz type for display
     */
    private function formatQuizType(string $type): string
    {
        return match ($type) {
            'completo' => 'Completo',
            'reducido' => 'Reducido',
            'cisneros' => 'Cisneros',
            default => ucfirst($type),
        };
    }

    /**
     * Format evaluation type for display
     */
    private function formatEvaluationType(string $type): string
    {
        return match ($type) {
            'referencia_i' => 'Guía I',
            'referencia_iii' => 'Guía III',
            'referencia_v' => 'Guía V',
            'cisneros' => 'Escala Cisneros',
            default => ucfirst(str_replace('_', ' ', $type)),
        };
    }
}
