<?php

namespace App\Http\Controllers;

use App\Models\Quiz;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class QuizController extends Controller
{
    public function index()
    {
        $quizzes = Quiz::query()
            ->with(['organization', 'workCenter', 'customFields'])
            ->withCount('evaluations')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($quiz) {
                // Generar URL amigable si tiene work center asignado
                if ($quiz->work_center_id && $quiz->workCenter && $quiz->organization && $quiz->unique_identifier) {
                    $url = route('quiz.friendly', [
                        $quiz->organization->slug,
                        $quiz->workCenter->slug,
                        $quiz->unique_identifier,
                    ]);
                } else {
                    // Fallback a URL antigua
                    $url = route('quiz.temp', $quiz->temp_url);
                }

                return [
                    'id' => $quiz->id,
                    'name' => $quiz->name,
                    'organization' => $quiz->organization,
                    'work_center' => $quiz->workCenter,
                    'temp_url' => $url,
                    'friendly_url' => $quiz->work_center_id && $quiz->workCenter && $quiz->organization ?
                        "evaluacion/{$quiz->organization->slug}/{$quiz->workCenter->slug}/{$quiz->unique_identifier}" : null,
                    'qr_code' => 'data:image/svg+xml;base64,'.base64_encode(QrCode::format('svg')->size(500)->generate($url)),
                    'expires_at' => $quiz->expires_at->format('Y-m-d H:i'),
                    'is_active' => $quiz->is_active && ! $quiz->isExpired(),
                    'is_reduced' => $quiz->is_reduced,
                    'is_cisneros' => $quiz->is_cisneros,
                    'evaluations_count' => $quiz->evaluations_count,
                    'custom_fields' => $quiz->customFields,
                ];
            });

        // Obtener organizaciones para el formulario de creación
        $organizations = \App\Models\Organization::select('id', 'name')->orderBy('name')->get();

        // Obtener work centers para todos los centros de trabajo
        $workCenters = \App\Models\WorkCenter::with('organization:id,name')
            ->select('id', 'organization_id', 'code', 'name', 'is_primary')
            ->orderBy('organization_id')
            ->orderByDesc('is_primary')
            ->orderBy('name')
            ->get()
            ->map(fn ($wc) => [
                'id' => $wc->id,
                'organization_id' => $wc->organization_id,
                'name' => $wc->name,
                'full_name' => $wc->full_name,
                'is_primary' => $wc->is_primary,
            ]);

        return Inertia::render('Quiz/Index', [
            'quizzes' => $quizzes,
            'organizations' => $organizations,
            'workCenters' => $workCenters,
        ]);
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'organization_id' => 'required|exists:organizations,id',
                'work_center_id' => 'nullable|exists:work_centers,id',
                'expires_at' => 'required|date|after:now',
                'quiz_type' => 'required|in:normal,reducido,cisneros',
                'custom_fields' => 'sometimes|array',
                'custom_fields.*.name' => 'required_with:custom_fields|string|max:255',
                'custom_fields.*.type' => 'required_with:custom_fields|in:text,number,textarea',
            ]);

            $isReduced = $validated['quiz_type'] === 'reducido';
            $isCisneros = $validated['quiz_type'] === 'cisneros';

            $quiz = Quiz::create([
                'name' => $validated['name'],
                'organization_id' => $validated['organization_id'],
                'work_center_id' => $validated['work_center_id'] ?? null,
                'temp_url' => Str::random(32),
                'expires_at' => $validated['expires_at'],
                'is_active' => true,
                'is_reduced' => $isReduced,
                'is_cisneros' => $isCisneros,
            ]);

            // Crear campos personalizados si existen
            if (isset($validated['custom_fields']) && is_array($validated['custom_fields'])) {
                foreach ($validated['custom_fields'] as $customField) {
                    if (! empty($customField['name']) && ! empty($customField['type'])) {
                        $quiz->customFields()->create([
                            'name' => $customField['name'],
                            'type' => $customField['type'],
                        ]);
                    }
                }
            }

            return redirect()->route('quizzes.index')
                ->with('success', 'Examen creado exitosamente');
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Illuminate\Support\Facades\Log::warning('Validación fallida al crear quiz', [
                'errors' => $e->errors(),
                'user_ip' => $request->ip(),
            ]);

            return back()->withErrors($e->errors())->withInput()
                ->with('error', 'Los datos proporcionados no son válidos. Por favor, revise los campos.');
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error al crear quiz', [
                'error_message' => $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'user_ip' => $request->ip(),
            ]);

            return back()->withInput()
                ->with('error', 'Error al crear el examen. Por favor, inténtelo nuevamente.');
        }
    }

    public function toggle(Quiz $quiz)
    {
        $quiz->update(['is_active' => ! $quiz->is_active]);

        return back()->with('success', 'Estado del examen actualizado');
    }

    /**
     * Show quiz with custom fields for editing
     */
    public function show(Quiz $quiz)
    {
        $quiz->load(['organization', 'customFields']);

        return Inertia::render('Quiz/Show', [
            'quiz' => [
                'id' => $quiz->id,
                'name' => $quiz->name,
                'organization' => $quiz->organization,
                'expires_at' => $quiz->expires_at->format('Y-m-d\TH:i'),
                'is_active' => $quiz->is_active,
                'is_reduced' => $quiz->is_reduced,
                'is_cisneros' => $quiz->is_cisneros,
                'custom_fields' => $quiz->customFields->map(function ($field) {
                    return [
                        'id' => $field->id,
                        'name' => $field->name,
                        'type' => $field->type,
                    ];
                }),
            ],
            'organizations' => \App\Models\Organization::select('id', 'name')->orderBy('name')->get(),
        ]);
    }

    /**
     * Update quiz and custom fields
     */
    public function update(Request $request, Quiz $quiz)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'organization_id' => 'required|exists:organizations,id',
                'expires_at' => 'required|date|after:now',
                'quiz_type' => 'required|in:normal,reducido,cisneros',
                'custom_fields' => 'sometimes|array',
                'custom_fields.*.id' => 'sometimes|exists:custom_fields,id',
                'custom_fields.*.name' => 'required_with:custom_fields|string|max:255',
                'custom_fields.*.type' => 'required_with:custom_fields|in:text,number,textarea',
            ]);

            DB::transaction(function () use ($validated, $quiz) {
                // Update quiz
                $quiz->update([
                    'name' => $validated['name'],
                    'organization_id' => $validated['organization_id'],
                    'expires_at' => $validated['expires_at'],
                    'is_reduced' => $validated['quiz_type'] === 'reducido',
                    'is_cisneros' => $validated['quiz_type'] === 'cisneros',
                ]);

                // Handle custom fields
                if (isset($validated['custom_fields'])) {
                    $submittedFieldIds = collect($validated['custom_fields'])
                        ->pluck('id')
                        ->filter()
                        ->toArray();

                    // Delete fields that are not in the submitted list
                    $quiz->customFields()
                        ->whereNotIn('id', $submittedFieldIds)
                        ->delete();

                    foreach ($validated['custom_fields'] as $fieldData) {
                        if (! empty($fieldData['name']) && ! empty($fieldData['type'])) {
                            if (isset($fieldData['id'])) {
                                // Update existing field
                                $quiz->customFields()
                                    ->where('id', $fieldData['id'])
                                    ->update([
                                        'name' => $fieldData['name'],
                                        'type' => $fieldData['type'],
                                    ]);
                            } else {
                                // Create new field
                                $quiz->customFields()->create([
                                    'name' => $fieldData['name'],
                                    'type' => $fieldData['type'],
                                ]);
                            }
                        }
                    }
                } else {
                    // If no custom fields are sent, delete all existing ones
                    $quiz->customFields()->delete();
                }
            });

            return back()->with('success', 'Examen actualizado exitosamente');
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error updating quiz', [
                'quiz_id' => $quiz->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->withErrors(['error' => 'Error al actualizar el examen']);
        }
    }

    public function showTemp($tempUrl)
    {
        try {
            $quiz = Quiz::with(['organization.occupationPositions', 'organization.departmentAreas', 'customFields', 'workCenter'])
                ->where('temp_url', $tempUrl)
                ->where('is_active', true)
                ->where('expires_at', '>', now())
                ->first();

            if (! $quiz) {
                \Illuminate\Support\Facades\Log::warning('Quiz no encontrado o no disponible', [
                    'temp_url' => $tempUrl,
                    'user_ip' => request()->ip(),
                ]);

                abort(404, 'El examen no está disponible o ha expirado.');
            }

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error al cargar quiz temporal', [
                'temp_url' => $tempUrl,
                'error_message' => $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'user_ip' => request()->ip(),
            ]);

            abort(500, 'Error al cargar el examen. Por favor, inténtelo nuevamente.');
        }

        // Preparar datos de la organización
        $organizationData = [
            'id' => $quiz->organization->id,
            'name' => $quiz->organization->name,
            'occupation_positions' => $quiz->organization->occupationPositions->pluck('name', 'id')->toArray(),
            'department_areas' => $quiz->organization->departmentAreas->pluck('name', 'id')->toArray(),
            'custom_fields' => $quiz->customFields->map(function ($field) {
                return [
                    'id' => $field->id,
                    'name' => $field->name,
                    'type' => $field->type,
                ];
            })->toArray(),
        ];

        // Decidir qué vista usar basado en el tipo de quiz
        if ($quiz->is_cisneros) {
            // Quiz tipo Cisneros
            return Inertia::render('Quiz/TakeCisneros', [
                'quiz' => [
                    'id' => $quiz->id,
                    'name' => $quiz->name,
                    'organization' => $organizationData,
                    'questions' => [
                        'acontecimientos_traumaticos' => config('referencia_iii_reduced.acontecimientos_traumaticos'),
                    ],
                    'reference_i' => config('referencia_i'),
                    'reference_v' => config('referencia_v'),
                    'custom_fields' => $quiz->customFields->map(function ($field) {
                        return [
                            'id' => $field->id,
                            'name' => $field->name,
                            'type' => $field->type,
                        ];
                    })->toArray(),
                ],
                'workCenterName' => $quiz->workCenter?->name,
            ]);
        } elseif ($quiz->is_reduced) {
            // Quiz reducido - solo acontecimientos traumáticos
            return Inertia::render('Quiz/TakeReduced', [
                'quiz' => [
                    'id' => $quiz->id,
                    'name' => $quiz->name,
                    'organization' => $organizationData,
                    'questions' => [
                        'acontecimientos_traumaticos' => config('referencia_iii_reduced.acontecimientos_traumaticos'),
                    ],
                    'reference_i' => config('referencia_i'),
                    'reference_v' => config('referencia_v'),
                    'custom_fields' => $quiz->customFields->map(function ($field) {
                        return [
                            'id' => $field->id,
                            'name' => $field->name,
                            'type' => $field->type,
                        ];
                    })->toArray(),
                ],
                'workCenterName' => $quiz->workCenter?->name,
            ]);
        } else {
            // Quiz completo - layout original
            return Inertia::render('Quiz/Take', [
                'quiz' => [
                    'id' => $quiz->id,
                    'name' => $quiz->name,
                    'organization' => $organizationData,
                    'questions' => [
                        'general' => config('referencia_iii.general'),
                        'general_blocks' => config('referencia_iii.general_blocks'),
                        'conditional_sections' => config('referencia_iii.conditional_sections'),
                        'acontecimientos_traumaticos' => config('referencia_iii.acontecimientos_traumaticos'),
                    ],
                    'reference_i' => config('referencia_i'),
                    'reference_v' => config('referencia_v'),
                    'custom_fields' => $quiz->customFields->map(function ($field) {
                        return [
                            'id' => $field->id,
                            'name' => $field->name,
                            'type' => $field->type,
                        ];
                    })->toArray(),
                ],
                'workCenterName' => $quiz->workCenter?->name,
            ]);
        }
    }

    /**
     * Show quiz using friendly URL (organization slug + work center slug + quiz identifier)
     */
    public function showBySlug(string $organizationSlug, string $workCenterSlug, string $identifier)
    {
        try {
            // Find organization by slug
            $organization = \App\Models\Organization::where('slug', $organizationSlug)->firstOrFail();

            // Find work center by slug within the organization
            $workCenter = \App\Models\WorkCenter::where('organization_id', $organization->id)
                ->where('slug', $workCenterSlug)
                ->firstOrFail();

            // Find active quiz for this work center with the unique identifier
            $quiz = Quiz::with(['organization.occupationPositions', 'organization.departmentAreas', 'customFields', 'workCenter'])
                ->where('organization_id', $organization->id)
                ->where('work_center_id', $workCenter->id)
                ->where('unique_identifier', $identifier)
                ->where('is_active', true)
                ->where('expires_at', '>', now())
                ->firstOrFail();

            // Preparar datos de la organización (mismo código que showTemp)
            $organizationData = [
                'id' => $quiz->organization->id,
                'name' => $quiz->organization->name,
                'occupation_positions' => $quiz->organization->occupationPositions->pluck('name', 'id')->toArray(),
                'department_areas' => $quiz->organization->departmentAreas->pluck('name', 'id')->toArray(),
                'custom_fields' => $quiz->customFields->map(function ($field) {
                    return [
                        'id' => $field->id,
                        'name' => $field->name,
                        'type' => $field->type,
                    ];
                })->toArray(),
            ];

            // Decidir qué vista usar basado en el tipo de quiz
            if ($quiz->is_cisneros) {
                return Inertia::render('Quiz/TakeCisneros', [
                    'quiz' => [
                        'id' => $quiz->id,
                        'name' => $quiz->name,
                        'organization' => $organizationData,
                        'questions' => [
                            'acontecimientos_traumaticos' => config('referencia_iii_reduced.acontecimientos_traumaticos'),
                        ],
                        'reference_i' => config('referencia_i'),
                        'reference_v' => config('referencia_v'),
                        'custom_fields' => $quiz->customFields->map(function ($field) {
                            return [
                                'id' => $field->id,
                                'name' => $field->name,
                                'type' => $field->type,
                            ];
                        })->toArray(),
                    ],
                    'workCenterName' => $quiz->workCenter?->name,
                ]);
            } elseif ($quiz->is_reduced) {
                return Inertia::render('Quiz/TakeReduced', [
                    'quiz' => [
                        'id' => $quiz->id,
                        'name' => $quiz->name,
                        'organization' => $organizationData,
                        'questions' => [
                            'acontecimientos_traumaticos' => config('referencia_iii_reduced.acontecimientos_traumaticos'),
                        ],
                        'reference_i' => config('referencia_i'),
                        'reference_v' => config('referencia_v'),
                        'custom_fields' => $quiz->customFields->map(function ($field) {
                            return [
                                'id' => $field->id,
                                'name' => $field->name,
                                'type' => $field->type,
                            ];
                        })->toArray(),
                    ],
                    'workCenterName' => $quiz->workCenter?->name,
                ]);
            } else {
                return Inertia::render('Quiz/Take', [
                    'quiz' => [
                        'id' => $quiz->id,
                        'name' => $quiz->name,
                        'organization' => $organizationData,
                        'questions' => [
                            'general' => config('referencia_iii.general'),
                            'general_blocks' => config('referencia_iii.general_blocks'),
                            'conditional_sections' => config('referencia_iii.conditional_sections'),
                            'acontecimientos_traumaticos' => config('referencia_iii.acontecimientos_traumaticos'),
                        ],
                        'reference_i' => config('referencia_i'),
                        'reference_v' => config('referencia_v'),
                        'custom_fields' => $quiz->customFields->map(function ($field) {
                            return [
                                'id' => $field->id,
                                'name' => $field->name,
                                'type' => $field->type,
                            ];
                        })->toArray(),
                    ],
                    'workCenterName' => $quiz->workCenter?->name,
                ]);
            }
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            \Illuminate\Support\Facades\Log::warning('Quiz no encontrado con URL amigable', [
                'organization_slug' => $organizationSlug,
                'work_center_slug' => $workCenterSlug,
                'identifier' => $identifier,
                'user_ip' => request()->ip(),
            ]);

            abort(404, 'El examen no está disponible o ha expirado.');
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error al cargar quiz con URL amigable', [
                'organization_slug' => $organizationSlug,
                'work_center_slug' => $workCenterSlug,
                'identifier' => $identifier,
                'error_message' => $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'user_ip' => request()->ip(),
            ]);

            abort(500, 'Error al cargar el examen. Por favor, inténtelo nuevamente.');
        }
    }

    public function submit(Request $request, Quiz $quiz)
    {
        try {
            // Custom validation to handle both test scenarios (arrays) and production (JSON strings)
            $validated = $request->validate([
                'referencia_iii' => 'nullable',
                'referencia_i' => 'nullable',
                'referencia_v' => 'nullable',
                'escala_cisneros' => 'nullable',
                'custom_fields' => 'nullable',
                'organization_info' => 'nullable',
                'ine_frente' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
                'ine_reverso' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            ]);

            // Parse data - handle both JSON strings (production) and arrays (tests)
            $decodedData = [];
            foreach (['referencia_iii', 'referencia_i', 'referencia_v', 'escala_cisneros', 'custom_fields', 'organization_info'] as $key) {
                if (isset($validated[$key]) && ! empty($validated[$key])) {
                    if (is_array($validated[$key])) {
                        // Direct array from tests
                        $decodedData[$key] = $validated[$key];
                    } elseif (is_string($validated[$key])) {
                        // JSON string from production frontend
                        $decoded = json_decode($validated[$key], true);
                        if (json_last_error() === JSON_ERROR_NONE) {
                            $decodedData[$key] = $decoded;
                        } else {
                            \Illuminate\Support\Facades\Log::warning('Error al decodificar JSON', [
                                'key' => $key,
                                'json_error' => json_last_error_msg(),
                                'data' => $validated[$key],
                            ]);
                        }
                    }
                }
            }

            // Replace validated data with decoded data
            $validated = array_merge($validated, $decodedData);

        } catch (\Illuminate\Validation\ValidationException $e) {
            \Illuminate\Support\Facades\Log::warning('Validación fallida en quiz submit', [
                'quiz_id' => $quiz->id,
                'errors' => $e->errors(),
                'user_ip' => $request->ip(),
            ]);

            return back()->withErrors($e->errors())->withInput()
                ->with('error', 'Los datos enviados no son válidos. Por favor, complete todos los campos requeridos.');
        }

        // Verificar que el quiz esté activo y no haya expirado
        if (! $quiz->is_active || $quiz->isExpired()) {
            \Illuminate\Support\Facades\Log::warning('Intento de acceso a quiz inactivo o expirado', [
                'quiz_id' => $quiz->id,
                'is_active' => $quiz->is_active,
                'expires_at' => $quiz->expires_at,
                'current_time' => now(),
                'user_ip' => $request->ip(),
            ]);

            return back()->with('error', 'El examen no está disponible o ha expirado');
        }

        try {
            // Generate unique folio for this evaluation
            $personalFolioCounter = $this->getNextPersonalFolioNumber($quiz->organization_id);
            $folio = $this->generatePaperEvaluationFolio($quiz, $personalFolioCounter);

            // Determine evaluation type based on quiz type
            $evaluationType = $this->determineEvaluationType($quiz);

            // Store uploaded files immediately (before job dispatch)
            $filesData = [];
            if (isset($validated['ine_frente'])) {
                $path = $validated['ine_frente']->store(
                    "quiz_submissions/{$quiz->organization_id}/{$folio}",
                    'public'
                );
                $filesData['ine_frente'] = $path;
            }

            if (isset($validated['ine_reverso'])) {
                $path = $validated['ine_reverso']->store(
                    "quiz_submissions/{$quiz->organization_id}/{$folio}",
                    'public'
                );
                $filesData['ine_reverso'] = $path;
            }

            // Merge file paths into referencia_v data
            if (! empty($filesData) && isset($validated['referencia_v'])) {
                $validated['referencia_v'] = array_merge($validated['referencia_v'], $filesData);
            }

            // Create SubmissionStatus record for async processing
            $submissionStatus = \App\Models\SubmissionStatus::create([
                'folio' => $folio,
                'personal_id' => $personalFolioCounter,
                'organization_id' => $quiz->organization_id,
                'quiz_id' => $quiz->id,
                'status' => \App\Models\SubmissionStatus::STATUS_PENDING,
                'data_snapshot' => [
                    'evaluation_type' => $evaluationType,
                    'referencia_iii' => $validated['referencia_iii'] ?? null,
                    'referencia_i' => $validated['referencia_i'] ?? null,
                    'referencia_v' => $validated['referencia_v'] ?? null,
                    'escala_cisneros' => $validated['escala_cisneros'] ?? null,
                    'custom_fields' => $validated['custom_fields'] ?? null,
                    'organization_info' => $validated['organization_info'] ?? null,
                    'quiz_name' => $quiz->name,
                    'quiz_type' => match (true) {
                        $quiz->is_cisneros => 'cisneros',
                        $quiz->is_reduced => 'reducido',
                        default => 'normal',
                    },
                    'submitted_at' => now()->toIso8601String(),
                    'submission_ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ],
            ]);

            // Dispatch job to process evaluation asynchronously
            \App\Jobs\ProcessOnlineEvaluation::dispatch($submissionStatus->id)
                ->onQueue('quiz_processing');

            // Return immediate response to user (gracias page)
            return Inertia::render('Quiz/Completed', [
                'quiz' => [
                    'id' => $quiz->id,
                    'name' => $quiz->name,
                    'is_reduced' => $quiz->is_reduced,
                    'is_cisneros' => $quiz->is_cisneros,
                    'organization' => [
                        'id' => $quiz->organization->id,
                        'name' => $quiz->organization->name,
                    ],
                ],
                'folio' => $folio,
                'personalId' => $personalFolioCounter,
                'message' => 'Gracias por completar la evaluación. Sus respuestas han sido enviadas exitosamente.',
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error al procesar envío de examen', [
                'quiz_id' => $quiz->id,
                'organization_id' => $quiz->organization_id,
                'error_message' => $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'stack_trace' => $e->getTraceAsString(),
                'user_ip' => $request->ip(),
            ]);

            return back()->with('error', 'Error al procesar el examen. Por favor, inténtelo nuevamente.');
        }
    }

    /**
     * Obtiene el siguiente folio incremental para la organización
     */
    private function getNextFolioNumber($organizationId)
    {
        try {
            // Buscar el último folio usado en la organización
            $lastFolio = \App\Models\Folio::whereHas('folioBatch', function ($query) use ($organizationId) {
                $query->where('organization_id', $organizationId);
            })
                ->orderBy('numeric_value', 'desc')
                ->first();

            // Si no hay folios, empezar desde 0001
            if (! $lastFolio) {
                return '0001';
            }

            // Incrementar el número del último folio
            $nextNumber = $lastFolio->numeric_value + 1;
            $folioNumber = str_pad($nextNumber, 4, '0', STR_PAD_LEFT);

            return $folioNumber;
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error al generar folio', [
                'organization_id' => $organizationId,
                'error_message' => $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
            ]);

            throw new \Exception('Error al generar el número de folio: '.$e->getMessage());
        }
    }

    /**
     * Genera el siguiente personal_id disponible para la organización
     */
    private function generateNextPersonalId($organizationId)
    {
        try {

            // Buscar el último personal_id usado en evaluaciones de esta organización
            $lastEvaluation = \App\Models\Evaluation::where('organization_id', $organizationId)
                ->whereNotNull('personal_id')
                ->orderByRaw('CAST(personal_id AS UNSIGNED) DESC')
                ->first();

            // Buscar el último personal_id usado en respuestas online de esta organización
            $lastOnlineAnswer = \App\Models\OnlineAnswer::where('organization_id', $organizationId)
                ->whereNotNull('personal_id')
                ->orderByRaw('CAST(personal_id AS UNSIGNED) DESC')
                ->first();

            // Determinar el último personal_id usado entre evaluaciones y respuestas online
            $lastPersonalId = 0;

            if ($lastEvaluation) {
                $lastPersonalId = max($lastPersonalId, intval($lastEvaluation->personal_id));
            }

            if ($lastOnlineAnswer) {
                $lastPersonalId = max($lastPersonalId, intval($lastOnlineAnswer->personal_id));
            }

            // Si no hay registros previos, empezar desde 0001
            if ($lastPersonalId === 0) {

                return '0001';
            }

            // Incrementar el último personal_id
            $nextNumber = $lastPersonalId + 1;
            $personalId = str_pad($nextNumber, 4, '0', STR_PAD_LEFT);

            return $personalId;
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error al generar personal_id', [
                'organization_id' => $organizationId,
                'error_message' => $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
            ]);

            throw new \Exception('Error al generar el personal ID: '.$e->getMessage());
        }
    }

    /**
     * Formats answer values for consistent storage in the database
     * Handles different data types: arrays, booleans, and strings
     */
    private function formatAnswerValue($value)
    {
        try {
            // Handle arrays by JSON encoding them
            if (is_array($value)) {
                $jsonValue = json_encode($value, JSON_UNESCAPED_UNICODE);
                if ($jsonValue === false) {
                    \Illuminate\Support\Facades\Log::warning('Error al codificar array como JSON', [
                        'value' => $value,
                        'json_error' => json_last_error_msg(),
                    ]);

                    return json_encode($value); // Fallback without unicode flag
                }

                return $jsonValue;
            }

            // Handle booleans by converting to string representation
            if (is_bool($value)) {
                return $value ? '1' : '0';
            }

            // Handle null values
            if (is_null($value)) {
                return '';
            }

            // Handle all other types by casting to string
            return (string) $value;
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error al formatear valor de respuesta', [
                'value' => $value,
                'value_type' => gettype($value),
                'error_message' => $e->getMessage(),
            ]);

            // Return a safe fallback value
            return is_scalar($value) ? (string) $value : '';
        }
    }

    /**
     * Sanitize field name for use as question_key in database
     * Converts human-readable field names to database-safe identifiers
     */
    private function sanitizeFieldName($fieldName)
    {
        // Convert to lowercase
        $sanitized = strtolower(trim($fieldName));

        // Replace accented characters with their non-accented equivalents
        $accents = [
            'á' => 'a', 'à' => 'a', 'ä' => 'a', 'â' => 'a', 'ã' => 'a', 'å' => 'a',
            'Á' => 'a', 'À' => 'a', 'Ä' => 'a', 'Â' => 'a', 'Ã' => 'a', 'Å' => 'a',
            'é' => 'e', 'è' => 'e', 'ë' => 'e', 'ê' => 'e',
            'É' => 'e', 'È' => 'e', 'Ë' => 'e', 'Ê' => 'e',
            'í' => 'i', 'ì' => 'i', 'ï' => 'i', 'î' => 'i',
            'Í' => 'i', 'Ì' => 'i', 'Ï' => 'i', 'Î' => 'i',
            'ó' => 'o', 'ò' => 'o', 'ö' => 'o', 'ô' => 'o', 'õ' => 'o', 'ø' => 'o',
            'Ó' => 'o', 'Ò' => 'o', 'Ö' => 'o', 'Ô' => 'o', 'Õ' => 'o', 'Ø' => 'o',
            'ú' => 'u', 'ù' => 'u', 'ü' => 'u', 'û' => 'u',
            'Ú' => 'u', 'Ù' => 'u', 'Ü' => 'u', 'Û' => 'u',
            'ñ' => 'n', 'Ñ' => 'n', 'ç' => 'c', 'Ç' => 'c',
        ];

        // First replace accents, then convert to lowercase
        $sanitized = strtr($fieldName, $accents);
        $sanitized = strtolower(trim($sanitized));

        // Replace spaces and special chars with underscores
        $sanitized = preg_replace('/[^a-z0-9_]/', '_', $sanitized);
        $sanitized = preg_replace('/_+/', '_', $sanitized); // Remove multiple underscores
        $sanitized = trim($sanitized, '_'); // Remove leading/trailing underscores

        // Ensure it's not empty
        if (empty($sanitized)) {
            $sanitized = 'custom_field';
        }

        return $sanitized;
    }

    /**
     * Procesa y almacena las imágenes del INE
     */
    private function processIneImages($request, $folio, $personalId)
    {
        $ineImages = [];

        try {
            // Procesar INE frente
            if ($request->hasFile('ine_frente')) {
                $ineFrente = $request->file('ine_frente');
                $frenteFileName = "ine_frente_{$folio}_{$personalId}.".$ineFrente->getClientOriginalExtension();
                $frentePath = $ineFrente->storeAs('ine_images', $frenteFileName, 'public');
                $ineImages['ine_frente'] = $frentePath;

            }

            // Procesar INE reverso
            if ($request->hasFile('ine_reverso')) {
                $ineReverso = $request->file('ine_reverso');
                $reversoFileName = "ine_reverso_{$folio}_{$personalId}.".$ineReverso->getClientOriginalExtension();
                $reversoPath = $ineReverso->storeAs('ine_images', $reversoFileName, 'public');
                $ineImages['ine_reverso'] = $reversoPath;
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error al procesar imágenes del INE', [
                'folio' => $folio,
                'personal_id' => $personalId,
                'error_message' => $e->getMessage(),
            ]);

            throw new \Exception('Error al procesar las imágenes del INE: '.$e->getMessage());
        }

        return $ineImages;
    }

    /**
     * Store online quiz answers in the online_answers table
     * Processes and stores individual question-answer pairs for different reference guide types
     */
    private function storeOnlineAnswers($folio, $personalId, $organizationId, $quizId, $answers, $ineImages = [])
    {
        try {

            $records = [];
            $recordCounts = [
                'referencia_iii' => 0,
                'referencia_i' => 0,
                'referencia_v' => 0,
                'escala_cisneros' => 0,
                'custom_fields' => 0,
            ];

            // Process referencia_iii answers
            if (isset($answers['referencia_iii']) && is_array($answers['referencia_iii'])) {
                foreach ($answers['referencia_iii'] as $key => $value) {
                    // Handle nested structures like acontecimientos_traumaticos
                    if (is_array($value)) {
                        foreach ($value as $subKey => $subValue) {
                            $records[] = [
                                'folio' => $folio,
                                'personal_id' => $personalId,
                                'organization_id' => $organizationId,
                                'quiz_id' => $quizId,
                                'question_key' => $key.'_'.$subKey,
                                'answer_value' => $this->formatAnswerValue($subValue),
                                'reference_guide' => 'III',
                                'created_at' => now(),
                                'updated_at' => now(),
                            ];
                            $recordCounts['referencia_iii']++;
                        }
                    } else {
                        $records[] = [
                            'folio' => $folio,
                            'personal_id' => $personalId,
                            'organization_id' => $organizationId,
                            'quiz_id' => $quizId,
                            'question_key' => $key,
                            'answer_value' => $this->formatAnswerValue($value),
                            'reference_guide' => 'III',
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                        $recordCounts['referencia_iii']++;
                    }
                }
            }

            // Process referencia_i answers
            if (isset($answers['referencia_i']) && is_array($answers['referencia_i'])) {
                foreach ($answers['referencia_i'] as $key => $value) {
                    $records[] = [
                        'folio' => $folio,
                        'personal_id' => $personalId,
                        'organization_id' => $organizationId,
                        'quiz_id' => $quizId,
                        'question_key' => $key,
                        'answer_value' => $this->formatAnswerValue($value),
                        'reference_guide' => 'I',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                    $recordCounts['referencia_i']++;
                }
            }

            // Process referencia_v answers
            if (isset($answers['referencia_v']) && is_array($answers['referencia_v'])) {
                foreach ($answers['referencia_v'] as $key => $value) {
                    // Handle nested structures like datos_laborales
                    if (is_array($value)) {
                        foreach ($value as $subKey => $subValue) {
                            $records[] = [
                                'folio' => $folio,
                                'personal_id' => $personalId,
                                'organization_id' => $organizationId,
                                'quiz_id' => $quizId,
                                'question_key' => $key.'_'.$subKey,
                                'answer_value' => $this->formatAnswerValue($subValue),
                                'reference_guide' => 'V',
                                'created_at' => now(),
                                'updated_at' => now(),
                            ];
                            $recordCounts['referencia_v']++;
                        }
                    } else {
                        $records[] = [
                            'folio' => $folio,
                            'personal_id' => $personalId,
                            'organization_id' => $organizationId,
                            'quiz_id' => $quizId,
                            'question_key' => $key,
                            'answer_value' => $this->formatAnswerValue($value),
                            'reference_guide' => 'V',
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                        $recordCounts['referencia_v']++;
                    }
                }
            }

            // Process INE images if present
            if (! empty($ineImages)) {
                foreach ($ineImages as $imageType => $imagePath) {
                    $records[] = [
                        'folio' => $folio,
                        'personal_id' => $personalId,
                        'organization_id' => $organizationId,
                        'quiz_id' => $quizId,
                        'question_key' => $imageType,
                        'answer_value' => $imagePath,
                        'reference_guide' => 'V',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                    $recordCounts['referencia_v']++;
                }
            }

            // Process escala_cisneros answers (for Cisneros quizzes)
            if (isset($answers['escala_cisneros']) && is_array($answers['escala_cisneros'])) {
                foreach ($answers['escala_cisneros'] as $key => $value) {
                    $records[] = [
                        'folio' => $folio,
                        'personal_id' => $personalId,
                        'organization_id' => $organizationId,
                        'quiz_id' => $quizId,
                        'question_key' => $key,
                        'answer_value' => $this->formatAnswerValue($value),
                        'reference_guide' => 'Cisneros',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                    $recordCounts['escala_cisneros']++;
                }
            }

            // Process custom fields answers
            if (isset($answers['custom_fields']) && is_array($answers['custom_fields'])) {
                // Load custom fields for this quiz to get field names
                $quiz = Quiz::with('customFields')->find($quizId);
                $customFields = $quiz ? $quiz->customFields->keyBy('id') : collect();

                foreach ($answers['custom_fields'] as $key => $value) {
                    if (! empty($value)) {
                        // Try to get the real field name from the database
                        $fieldName = 'custom_field_'.$key; // Default fallback

                        // If we have the custom field data, use the actual field name
                        if ($customFields->has($key)) {
                            $customField = $customFields->get($key);
                            $fieldName = $this->sanitizeFieldName($customField->name);
                        }

                        $records[] = [
                            'folio' => $folio,
                            'personal_id' => $personalId,
                            'organization_id' => $organizationId,
                            'quiz_id' => $quizId,
                            'question_key' => $fieldName,
                            'answer_value' => $this->formatAnswerValue($value),
                            'reference_guide' => 'V', // Store custom fields under reference V
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                        $recordCounts['custom_fields']++;
                    }
                }
            }

            // Batch insert for performance optimization
            if (! empty($records)) {
                \App\Models\OnlineAnswer::insert($records);

            } else {
                \Illuminate\Support\Facades\Log::warning('No se encontraron respuestas para almacenar', [
                    'folio' => $folio,
                    'personal_id' => $personalId,
                    'quiz_id' => $quizId,
                ]);
            }
        } catch (\Illuminate\Database\QueryException $e) {
            \Illuminate\Support\Facades\Log::error('Error de base de datos al almacenar respuestas online', [
                'folio' => $folio,
                'personal_id' => $personalId,
                'quiz_id' => $quizId,
                'error_code' => $e->getCode(),
                'error_message' => $e->getMessage(),
                'sql_state' => $e->errorInfo[0] ?? null,
            ]);

            throw new \Exception('Error al guardar las respuestas en la base de datos: '.$e->getMessage());
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error general al almacenar respuestas online', [
                'folio' => $folio,
                'personal_id' => $personalId,
                'quiz_id' => $quizId,
                'error_message' => $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
            ]);

            throw new \Exception('Error al procesar las respuestas del examen: '.$e->getMessage());
        }
    }

    /**
     * Crea un folio virtual para mantener la trazabilidad del quiz
     * Modificado para trabajar sin evaluation_id, manteniendo compatibilidad con el sistema de lotes existente
     */
    private function createVirtualFolio($organizationId, $folioNumber)
    {
        try {

            // Buscar o crear un lote virtual para quiz de esta organización
            $virtualBatch = \App\Models\FolioBatch::firstOrCreate([
                'organization_id' => $organizationId,
                'name' => 'Quiz Virtual Batch',
                'type' => 'en_linea',
            ], [
                'description' => 'Lote virtual para folios generados por quiz',
                'start_number' => 1,
                'end_number' => 9999,
                'quantity' => 9999,
            ]);

            // Crear el folio virtual
            $folio = \App\Models\Folio::create([
                'folio_batch_id' => $virtualBatch->id,
                'folio_number' => $folioNumber,
                'numeric_value' => intval($folioNumber),
                'used' => true,
                'used_at' => now(),
            ]);

        } catch (\Illuminate\Database\QueryException $e) {
            \Illuminate\Support\Facades\Log::error('Error de base de datos al crear folio virtual', [
                'organization_id' => $organizationId,
                'folio_number' => $folioNumber,
                'error_code' => $e->getCode(),
                'error_message' => $e->getMessage(),
                'sql_state' => $e->errorInfo[0] ?? null,
            ]);

            throw new \Exception('Error al crear el folio virtual en la base de datos: '.$e->getMessage());
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error general al crear folio virtual', [
                'organization_id' => $organizationId,
                'folio_number' => $folioNumber,
                'error_message' => $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
            ]);

            throw new \Exception('Error al crear el folio virtual: '.$e->getMessage());
        }
    }

    /**
     * Get the next personal folio number for the organization
     */
    private function getNextPersonalFolioNumber($organizationId): string
    {
        try {
            // Get the last personal folio from paper evaluations for this organization
            $lastEvaluation = \App\Models\PaperEvaluation::where('organization_id', $organizationId)
                ->whereNotNull('personal_folio')
                ->orderByRaw('CAST(personal_folio AS UNSIGNED) DESC')
                ->first();

            if (! $lastEvaluation) {
                return '0001';
            }

            $nextNumber = intval($lastEvaluation->personal_folio) + 1;

            return str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error al generar personal folio', [
                'organization_id' => $organizationId,
                'error_message' => $e->getMessage(),
            ]);

            throw new \Exception('Error al generar el número de folio personal: '.$e->getMessage());
        }
    }

    /**
     * Generate a complete folio for PaperEvaluation
     * Format: 2 digits (evaluation_type_code) + 3 digits (organization_code) + 4 digits (personal_folio)
     */
    private function generatePaperEvaluationFolio(Quiz $quiz, string $personalFolio): string
    {
        // Determine evaluation type code based on quiz type
        $evaluationTypeCode = match (true) {
            $quiz->is_cisneros => '04',  // Cisneros scale
            $quiz->is_reduced => '02',   // Reduced evaluation (Referencia III)
            default => '03',             // Full evaluation (Referencia V)
        };

        // Get organization code (using folio_organization or generating from ID)
        $organizationCode = $this->getOrganizationCode($quiz->organization);

        return $evaluationTypeCode.$organizationCode.$personalFolio;
    }

    /**
     * Get organization code from organization model
     */
    private function getOrganizationCode($organization): string
    {
        if ($organization->folio_organization) {
            // Use existing code, ensure it's 3 digits
            return str_pad(substr($organization->folio_organization, 0, 3), 3, '0', STR_PAD_LEFT);
        }

        // Generate from ID as fallback
        $orgId = is_numeric($organization->id) ? $organization->id : crc32($organization->id);

        return str_pad(substr((string) $orgId, -3), 3, '0', STR_PAD_LEFT);
    }

    /**
     * Determine evaluation type from quiz configuration
     */
    private function determineEvaluationType(Quiz $quiz): string
    {
        return match (true) {
            $quiz->is_cisneros => 'cisneros',
            $quiz->is_reduced => 'referencia_iii',
            default => 'referencia_v',
        };
    }

    /**
     * Extract demographic data from validated submission
     */
    private function extractDemographicData(array $validated, array $filesData): array
    {
        $demographicData = $validated['referencia_v'] ?? [];

        // Add file paths to demographic data if they exist
        if (! empty($filesData)) {
            $demographicData = array_merge($demographicData, $filesData);
        }

        // Add custom fields if they exist
        if (isset($validated['custom_fields']) && ! empty($validated['custom_fields'])) {
            $demographicData['custom_fields'] = $validated['custom_fields'];
        }

        return $demographicData;
    }

    /**
     * Extract Referencia III answers (excluding conditional sections)
     */
    private function extractReferenciaIIIAnswers(array $validated): ?array
    {
        if (! isset($validated['referencia_iii'])) {
            return null;
        }

        $answers = $validated['referencia_iii'];

        // Remove conditional sections to store them separately
        unset($answers['conditional_sections']);
        unset($answers['conditional_customer_service']);
        unset($answers['conditional_management']);

        return $answers;
    }

    /**
     * Extract conditional answers from Referencia III
     */
    private function extractConditionalAnswers(array $validated): ?array
    {
        if (! isset($validated['referencia_iii'])) {
            return null;
        }

        $conditionalAnswers = [];

        // Extract conditional sections
        if (isset($validated['referencia_iii']['conditional_sections'])) {
            $conditionalAnswers['conditional_sections'] = $validated['referencia_iii']['conditional_sections'];
        }

        if (isset($validated['referencia_iii']['conditional_customer_service'])) {
            $conditionalAnswers['conditional_customer_service'] = $validated['referencia_iii']['conditional_customer_service'];
        }

        if (isset($validated['referencia_iii']['conditional_management'])) {
            $conditionalAnswers['conditional_management'] = $validated['referencia_iii']['conditional_management'];
        }

        return ! empty($conditionalAnswers) ? $conditionalAnswers : null;
    }
}
