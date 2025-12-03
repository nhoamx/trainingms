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

        // Preparar custom fields (from quiz, stored in raw_data)
        $customFields = $evaluation->quiz_custom_fields ?? [];

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
        $traumaticQuestionsReduced = config('referencia_iii_reduced.acontecimientos_traumaticos', []);
        $traumaticQuestionsComplete = config('referencia_iii.acontecimientos_traumaticos', []);
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
                'traumatic_questions_reduced' => $traumaticQuestionsReduced,
                'traumatic_questions_complete' => $traumaticQuestionsComplete,
                'referencia_i_questions' => $referenciaIQuestions,
                'referencia_iii_questions' => $referenciaIIIQuestions,
                'escala_cisneros_questions' => $escalaCisnerosQuestions,
                'referencia_v_config' => $referenciaVConfig,
            ],
        ]);
    }

    /**
     * Mostrar reporte agregado de evaluaciones online por organización
     */
    public function report($organizationId)
    {
        $organization = Organization::findOrFail($organizationId);

        // Obtener todas las evaluaciones online completadas
        $evaluations = PaperEvaluation::query()
            ->online()
            ->completed()
            ->where('organization_id', $organizationId)
            ->get();

        // Estadísticas generales
        $stats = [
            'total' => $evaluations->count(),
            'por_tipo_quiz' => [
                'completo' => $evaluations->where('quiz_type', 'completo')->count(),
                'reducido' => $evaluations->where('quiz_type', 'reducido')->count(),
                'cisneros' => $evaluations->where('quiz_type', 'cisneros')->count(),
            ],
            'por_genero' => [],
            'por_edad' => [],
            'por_puesto' => [],
            'acontecimientos_traumaticos' => [
                'total_con_eventos' => 0,
                'porcentaje' => 0,
                'tipos_eventos' => [],
            ],
        ];

        // Análisis de datos demográficos y traumáticos
        $eventosTraumaticos = [];

        foreach ($evaluations as $evaluation) {
            $demographic = $evaluation->demographic_data ?? [];

            // Conteo por género
            $sexo = $demographic['sexo'] ?? 'No especificado';
            $stats['por_genero'][$sexo] = ($stats['por_genero'][$sexo] ?? 0) + 1;

            // Conteo por edad
            $edad = $demographic['edad'] ?? 'No especificado';
            $stats['por_edad'][$edad] = ($stats['por_edad'][$edad] ?? 0) + 1;

            // Conteo por puesto
            $puesto = $demographic['datos_laborales']['tipo_puesto'] ?? 'No especificado';
            $stats['por_puesto'][$puesto] = ($stats['por_puesto'][$puesto] ?? 0) + 1;

            // Análisis de acontecimientos traumáticos
            $traumaticos = null;
            if (isset($evaluation->referencia_iii_answers['acontecimientos_traumaticos'])) {
                $traumaticos = $evaluation->referencia_iii_answers['acontecimientos_traumaticos'];
            } elseif ($evaluation->referencia_iii_conditional) {
                $traumaticos = $evaluation->referencia_iii_conditional['acontecimientos_traumaticos'] ?? null;
            }

            if ($traumaticos && is_array($traumaticos)) {
                $tieneEventos = false;
                foreach ($traumaticos as $key => $value) {
                    if ($value === true || $value === 'true' || $value === 1) {
                        $tieneEventos = true;
                        $eventosTraumaticos[$key] = ($eventosTraumaticos[$key] ?? 0) + 1;
                    }
                }
                if ($tieneEventos) {
                    $stats['acontecimientos_traumaticos']['total_con_eventos']++;
                }
            }
        }

        // Calcular porcentaje de personas con acontecimientos traumáticos
        if ($stats['total'] > 0) {
            $stats['acontecimientos_traumaticos']['porcentaje'] = round(
                ($stats['acontecimientos_traumaticos']['total_con_eventos'] / $stats['total']) * 100,
                1
            );
        }

        // Obtener nombres de los eventos traumáticos
        $traumaticQuestionsComplete = config('referencia_iii.acontecimientos_traumaticos.questions', []);
        $traumaticQuestionsReduced = config('referencia_iii_reduced.acontecimientos_traumaticos.questions', []);

        foreach ($eventosTraumaticos as $key => $count) {
            $questionNum = (int) $key;

            // Intentar mapear de 1-6 a 73-78
            $mappedKey = $questionNum + 72;
            $questionText = $traumaticQuestionsComplete[$mappedKey] ??
                           $traumaticQuestionsReduced[$questionNum] ??
                           "Evento $questionNum";

            $stats['acontecimientos_traumaticos']['tipos_eventos'][] = [
                'evento' => $questionText,
                'cantidad' => $count,
                'porcentaje' => $stats['total'] > 0 ? round(($count / $stats['total']) * 100, 1) : 0,
            ];
        }

        // Ordenar eventos por cantidad (descendente)
        usort($stats['acontecimientos_traumaticos']['tipos_eventos'], function ($a, $b) {
            return $b['cantidad'] - $a['cantidad'];
        });

        return Inertia::render('OnlineResults/Report', [
            'organization' => [
                'id' => $organization->id,
                'name' => $organization->name,
            ],
            'stats' => $stats,
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
