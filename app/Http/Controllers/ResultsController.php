<?php

namespace App\Http\Controllers;

use App\Exports\EvaluationTemplateExport;
use App\Imports\EvaluationBulkUpdateImport;
use App\Models\Category;
use App\Models\Evaluation;
use App\Models\Organization;
use App\Models\PaperEvaluation;
use App\Models\Question;
use App\Services\PaperEvaluationScoreService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

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

        // Group paper evaluations by personal_folio (include both paper and online sources)
        $evaluationGroups = PaperEvaluation::where('organization_id', $organization->id)
            ->whereIn('source', ['paper', 'online'])
            ->where('processing_status', 'completed')
            ->orderBy('personal_folio')
            ->orderBy('created_at', 'desc')
            ->get()
            ->groupBy('personal_folio')
            ->map(function ($evaluations, $personalFolio) {
                $evaluationTypes = $evaluations->pluck('evaluation_type')->unique()->values();
                $source = $evaluations->first()->source; // Get source (paper or online)

                // Get the Referencia III evaluation for score and evaluee_name
                $referenciaIII = $evaluations->firstWhere('evaluation_type', 'referencia_iii');
                $totalScore = 0;
                $evalueeNameFromRef3 = null;

                if ($referenciaIII) {
                    $scores = $this->scoreService->calculateReferenciaIIIScores($referenciaIII);
                    $totalScore = $scores['total_score'];
                    $evalueeNameFromRef3 = $referenciaIII->evaluee_name;
                }

                // Check for missing evaluations (only III and V)
                $hasReferenciaIII = $evaluations->contains('evaluation_type', 'referencia_iii');
                $hasReferenciaV = $evaluations->contains('evaluation_type', 'referencia_v');

                // Check for missing or null demographic data
                $missingData = [];
                $referenciaV = $evaluations->firstWhere('evaluation_type', 'referencia_v');
                if ($referenciaV) {
                    // If demographic_data is null or empty, all fields are missing
                    if (! $referenciaV->demographic_data || empty($referenciaV->demographic_data)) {
                        $missingData = ['Todos los datos demográficos'];
                    } else {
                        $data = $referenciaV->demographic_data;

                        // Detect if it's paper format (direct fields) or online format (nested datos_laborales)
                        $isPaperFormat = ! isset($data['datos_laborales']);

                        if ($isPaperFormat) {
                            // PAPER FORMAT: Check direct fields
                            $paperFields = [
                                'edad' => 'Edad',
                                'sexo' => 'Género',
                                'estado_civil' => 'Estado Civil',
                                'ocupacion' => 'Puesto/Ocupación',
                                'departamento' => 'Departamento',
                                'tipo_puesto' => 'Tipo de Puesto',
                                'tipo_contratacion' => 'Tipo de Contratación',
                                'tipo_jornada' => 'Tipo de Jornada',
                                'tiempo_puesto_actual' => 'Experiencia en Puesto Actual',
                            ];

                            foreach ($paperFields as $field => $label) {
                                $value = $data[$field] ?? null;

                                // Check if empty, null, or array with all null/empty values
                                if ($value === null || $value === '') {
                                    $missingData[] = $label;
                                } elseif (is_array($value)) {
                                    // For nested arrays like edad: {decenas, unidades} or ocupacion: {fila1, fila2}
                                    $allEmpty = true;
                                    foreach ($value as $subValue) {
                                        if ($subValue !== null && $subValue !== '') {
                                            $allEmpty = false;
                                            break;
                                        }
                                    }
                                    if ($allEmpty) {
                                        $missingData[] = $label;
                                    }
                                }
                            }
                        } else {
                            // ONLINE FORMAT: Check basic fields + nested datos_laborales
                            $basicFields = [
                                'edad' => 'Edad',
                                'sexo' => 'Género',
                                'estado_civil' => 'Estado Civil',
                                'nivel_estudios' => 'Nivel de Estudios',
                            ];

                            foreach ($basicFields as $field => $label) {
                                if (! isset($data[$field]) ||
                                    $data[$field] === null ||
                                    $data[$field] === '') {
                                    $missingData[] = $label;
                                }
                            }

                            // Check labor data (nested structure)
                            if (! isset($data['datos_laborales']) ||
                                empty($data['datos_laborales'])) {
                                $missingData[] = 'Todos los Datos Laborales';
                            } else {
                                $laborData = $data['datos_laborales'];
                                $laborFields = [
                                    'ocupacion_puesto' => 'Puesto',
                                    'tipo_puesto' => 'Tipo de Puesto',
                                    'tipo_contratacion' => 'Tipo de Contratación',
                                    'tipo_jornada' => 'Tipo de Jornada',
                                    'departamento_seccion_area' => 'Área/Departamento',
                                ];

                                foreach ($laborFields as $field => $label) {
                                    if (! isset($laborData[$field]) ||
                                        $laborData[$field] === null ||
                                        $laborData[$field] === '') {
                                        $missingData[] = $label;
                                    }
                                }

                                // Check experiencia (nested in experiencia)
                                if (isset($laborData['experiencia'])) {
                                    if (empty($laborData['experiencia']['tiempo_puesto_actual'])) {
                                        $missingData[] = 'Experiencia en Puesto Actual';
                                    }
                                } else {
                                    $missingData[] = 'Experiencia en Puesto Actual';
                                }
                            }
                        }
                    }
                }

                return [
                    'personal_folio' => $personalFolio,
                    'evaluation_types' => $evaluationTypes,
                    // Use evaluee_name from Referencia III (main evaluation) with fallback to first evaluation
                    'evaluee_name' => $evalueeNameFromRef3 ?? $evaluations->first()->evaluee_name,
                    'source' => $source,
                    'total_score' => $totalScore,
                    'created_at' => $evaluations->first()->created_at->format('Y-m-d H:i:s'),
                    'has_referencia_iii' => $hasReferenciaIII,
                    'has_referencia_v' => $hasReferenciaV,
                    'missing_data' => $missingData,
                    // Include demographic_data for filtering (gender, age, etc.)
                    'demographic_data' => $referenciaV?->demographic_data,
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

        // Calculate summary statistics
        $totalEvaluations = $evaluationGroups->count();
        $missingReferenciaIII = $evaluationGroups->where('has_referencia_iii', false)->count();
        $missingReferenciaV = $evaluationGroups->where('has_referencia_v', false)->count();
        $withMissingData = $evaluationGroups->filter(fn ($group) => ! empty($group['missing_data']))->count();

        return Inertia::render('Results/List', [
            'organization' => $organization->only('id', 'name'),
            'evaluationGroups' => $evaluationGroups,
            'summary' => [
                'total_evaluations' => $totalEvaluations,
                'missing_referencia_iii' => $missingReferenciaIII,
                'missing_referencia_v' => $missingReferenciaV,
                'with_missing_data' => $withMissingData,
            ],
        ]);
    }

    public function showDetailedResults(Organization $organization, string $personalFolio)
    {
        $this->authorize('view-organization-results', $organization);

        // Get all evaluations for this personal folio (include both paper and online sources)
        $evaluations = PaperEvaluation::where('organization_id', $organization->id)
            ->where('personal_folio', $personalFolio)
            ->whereIn('source', ['paper', 'online'])
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
            $questions = config('guide_i_questions');
            $answers = $referenciaI->referencia_i_answers ?? [];
            $mappedAnswers = [];
            foreach ($answers as $key => $value) {
                $label = $questions[$key] ?? $key;
                $mappedAnswers[$label] = $value;
            }
            $guideIResults = [
                'id' => $referenciaI->id,
                'folio' => $referenciaI->folio,
                'created_at' => $referenciaI->created_at->format('Y-m-d H:i:s'),
                'answers' => $mappedAnswers,
            ];
        }

        // Format Guide III results
        $guideIIIResults = null;
        if ($referenciaIII) {
            $questions = config('referencia_iii.general');
            $conditionalSections = config('referencia_iii.conditional_sections');
            $acontecimientos = config('referencia_iii.acontecimientos_traumaticos');
            $answers = $referenciaIII->referencia_iii_answers ?? [];
            $mappedAnswers = [];
            foreach ($answers as $key => $value) {
                $num = (int) ltrim($key, '0');
                $label = $questions[$num] ?? $key;
                $mappedAnswers[$label] = $value;
            }
            // Condicionales: incluir la condición y mapear cada pregunta
            $conditional = $referenciaIII->referencia_iii_conditional ?? [];
            $mappedConditional = [];
            foreach ($conditionalSections as $sectionKey => $section) {
                if (isset($conditional[$sectionKey])) {
                    $sectionData = $conditional[$sectionKey];
                    $conditionValue = $sectionData['condition'] ?? null;
                    $questionsData = $sectionData['questions'] ?? [];
                    $sectionLabel = $section['condition'];
                    $mappedQuestions = [];
                    foreach ($questionsData as $qKey => $qValue) {
                        $qNum = (int) ltrim($qKey, '0');
                        $qLabel = $section['questions'][$qNum] ?? $qKey;
                        $mappedQuestions[$qLabel] = $qValue;
                    }
                    $mappedConditional[] = [
                        'section' => $sectionLabel,
                        'condition' => $conditionValue,
                        'questions' => $mappedQuestions,
                    ];
                }
            }
            // CITSATS: usar el bloque de acontecimientos_traumaticos
            $citsats = $referenciaIII->citsats_s1 ?? [];
            $mappedCitsats = [];
            if (! empty($citsats)) {
                $citsatsQuestions = $acontecimientos['questions'] ?? [];
                foreach ($citsats as $key => $value) {
                    $num = (int) ltrim($key, '0');
                    $label = $citsatsQuestions[$num] ?? $key;
                    $mappedCitsats[$label] = $value;
                }
            }
            $guideIIIResults = [
                'id' => $referenciaIII->id,
                'folio' => $referenciaIII->folio,
                'created_at' => $referenciaIII->created_at->format('Y-m-d H:i:s'),
                'answers' => $mappedAnswers,
                'conditional' => $mappedConditional,
                'citsats_s1' => $mappedCitsats,
            ];
        }

        // Format Guide V results
        $guideVResults = null;
        if ($referenciaV) {
            $demographic = $referenciaV->demographic_data ?? [];
            $labels = [
                'sexo' => 'Sexo',
                'edad' => 'Edad',
                'estado_civil' => 'Estado Civil',
                'nivel_estudios' => 'Nivel de Estudios',
                'ocupacion_puesto' => 'Ocupación/Puesto',
                'departamento_seccion_area' => 'Departamento/Sección/Área',
                'tipo_puesto' => 'Tipo de Puesto',
                'tipo_contratacion' => 'Tipo de Contratación',
                'tipo_personal' => 'Tipo de Personal',
                'tipo_jornada' => 'Tipo de Jornada',
                'rotacion_turnos' => 'Rotación de Turnos',
                'tiempo_puesto_actual' => 'Tiempo en el Puesto Actual',
                'tiempo_experiencia_laboral' => 'Tiempo de Experiencia Laboral',
            ];
            $configV = config('referencia_v');
            $mappedDemographic = [];
            foreach ($demographic as $key => $value) {
                $label = $labels[$key] ?? $key;
                $displayValue = '';
                // Edad: puede venir como array { decenas, unidades }
                if ($key === 'edad' && is_array($value) && isset($value['decenas'], $value['unidades'])) {
                    $displayValue = $value['decenas'].$value['unidades'];
                }
                // Sexo
                elseif ($key === 'sexo' && is_string($value)) {
                    $displayValue = strtolower($value) === 'femenino' ? 'Femenino' : (strtolower($value) === 'masculino' ? 'Masculino' : ucfirst($value));
                }
                // Estado civil
                elseif ($key === 'estado_civil' && is_string($value)) {
                    $map = ['union_libre' => 'Unión libre', 'casado' => 'Casado', 'soltero' => 'Soltero', 'divorciado' => 'Divorciado', 'viudo' => 'Viudo'];
                    $displayValue = $map[$value] ?? ucfirst($value);
                }
                // Nivel de estudios
                elseif ($key === 'nivel_estudios' && is_array($value)) {
                    foreach ($value as $nivel => $datos) {
                        if (is_array($datos) && ! empty($datos['seleccionado'])) {
                            $labelNivel = ucfirst(str_replace('_', ' ', $nivel));
                            if (! empty($datos['completado'])) {
                                $labelNivel .= $datos['completado'] === 'completo' ? ' (Terminada)' : ' (Incompleta)';
                            }
                            $displayValue = $labelNivel;
                            break;
                        }
                    }
                }
                // Ocupación/Puesto y Departamento
                elseif (($key === 'ocupacion_puesto' || $key === 'departamento_seccion_area' || $key === 'ocupacion' || $key === 'departamento') && is_array($value)) {
                    $vals = array_filter(array_values($value), fn ($v) => ! is_null($v) && $v !== '');
                    $displayValue = $vals ? implode(' ', $vals) : 'Sin respuesta';
                }
                // Tipo de puesto, contratación, personal, jornada, rotación, etc.
                elseif (in_array($key, ['tipo_puesto', 'tipo_contratacion', 'tipo_personal', 'tipo_jornada', 'rotacion_turnos'])) {
                    $displayValue = is_string($value) ? ucwords(str_replace(['_', '-'], [' ', ' '], $value)) : '';
                }
                // Experiencia laboral y tiempo en el puesto actual - usar mapeo correcto
                elseif (in_array($key, ['tiempo_puesto_actual', 'experiencia_laboral', 'tiempo_experiencia_laboral'])) {
                    if (is_string($value)) {
                        // Reemplazar guiones bajos y "anos" por "años"
                        $displayValue = str_replace('_', ' ', $value);
                        $displayValue = str_replace('anos', 'años', $displayValue);
                        // Capitalizar correctamente
                        $displayValue = ucfirst($displayValue);
                        // Reemplazar "a" por "a" en rangos (Entre 5 a 9 años)
                        $displayValue = preg_replace('/\s+a\s+(\d)/', ' a $1', $displayValue);
                    } else {
                        $displayValue = '';
                    }
                }
                // Si no, mostrar como string
                else {
                    $displayValue = is_array($value) ? json_encode($value) : (string) $value;
                }
                $mappedDemographic[$label] = $displayValue;
            }
            $guideVResults = [
                'id' => $referenciaV->id,
                'folio' => $referenciaV->folio,
                'created_at' => $referenciaV->created_at->format('Y-m-d H:i:s'),
                'demographic_data' => $mappedDemographic,
                'raw_demographic_data' => $demographic, // Para edición
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
                'evaluee_name' => $referenciaIII?->evaluee_name ?? $evaluations->first()->evaluee_name,
                'created_at' => $referenciaIII?->created_at->format('Y-m-d H:i:s') ?? $evaluations->first()->created_at->format('Y-m-d H:i:s'),
                'personal_folio' => $personalFolio,
                'has_guide_i' => (bool) $referenciaI,
                'has_guide_iii' => (bool) $referenciaIII,
                'has_guide_v' => (bool) $referenciaV,
                'has_cisneros' => false,
            ],
            'totalScore' => $totalScore,
            'results' => $results,
            'guideIResults' => $guideIResults,
            'guideVResults' => $guideVResults,
            'guideIIIResults' => $guideIIIResults,
            'cisnerosResults' => $cisnerosResults,
            'isAdmin' => auth()->user()->hasRole(['admin', 'super-admin']),
            'occupationPositions' => $organization->occupationPositions()->get(['id', 'name'])->toArray(),
            'departmentAreas' => $organization->departmentAreas()->get(['id', 'name'])->toArray(),
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

    /**
     * Descargar plantilla de Excel para actualización masiva
     */
    public function downloadTemplate(Organization $organization)
    {
        $this->authorize('view-organization-results', $organization);

        $filename = 'plantilla_actualizacion_'.$organization->name.'_'.now()->format('Y-m-d').'.xlsx';

        return Excel::download(
            new EvaluationTemplateExport($organization),
            $filename
        );
    }

    /**
     * Procesar archivo de actualización masiva
     */
    public function bulkUpdate(Request $request, Organization $organization)
    {
        $this->authorize('view-organization-results', $organization);

        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls|max:10240', // Max 10MB
        ]);

        try {
            $import = new EvaluationBulkUpdateImport;
            Excel::import($import, $request->file('file'));

            $updatedCount = $import->getUpdatedCount();
            $skippedCount = $import->getSkippedCount();
            $errors = $import->getErrors();

            // Preparar mensaje de respuesta
            $message = "Proceso completado: {$updatedCount} folios actualizados";

            if ($skippedCount > 0) {
                $message .= ", {$skippedCount} folios omitidos";
            }

            // Si hay errores, incluirlos en la respuesta
            if (! empty($errors)) {
                return back()->with([
                    'success' => $updatedCount > 0,
                    'message' => $message,
                    'errors' => $errors,
                ]);
            }

            return back()->with([
                'success' => true,
                'message' => $message,
            ]);
        } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
            $failures = $e->failures();
            $errors = [];

            foreach ($failures as $failure) {
                $errors[] = "Fila {$failure->row()}: ".implode(', ', $failure->errors());
            }

            return back()->with([
                'success' => false,
                'message' => 'Error de validación en el archivo',
                'errors' => $errors,
            ]);
        } catch (\Exception $e) {
            return back()->with([
                'success' => false,
                'message' => 'Error al procesar el archivo: '.$e->getMessage(),
            ]);
        }
    }
}
