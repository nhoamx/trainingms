<?php

namespace App\Http\Controllers;

use App\Models\Quiz;
use App\Models\CustomField;
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
            ->with(['organization', 'customFields'])
            ->withCount('evaluations')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($quiz) {
                $url = route('quiz.temp', $quiz->temp_url);
                return [
                    'id' => $quiz->id,
                    'name' => $quiz->name,
                    'organization' => $quiz->organization,
                    'temp_url' => $url,
                    'qr_code' => 'data:image/svg+xml;base64,' . base64_encode(QrCode::format('svg')->size(500)->generate($url)),
                    'expires_at' => $quiz->expires_at->format('Y-m-d H:i'),
                    'is_active' => $quiz->is_active && !$quiz->isExpired(),
                    'is_reduced' => $quiz->is_reduced,
                    'is_cisneros' => $quiz->is_cisneros,
                    'evaluations_count' => $quiz->evaluations_count,
                    'custom_fields' => $quiz->customFields,
                ];
            });

        // Obtener organizaciones para el formulario de creación
        $organizations = \App\Models\Organization::select('id', 'name')->orderBy('name')->get();

        return Inertia::render('Quiz/Index', [
            'quizzes' => $quizzes,
            'organizations' => $organizations,
        ]);
    }

    public function store(Request $request)
    {
        try {
            \Illuminate\Support\Facades\Log::info('Iniciando creación de quiz', [
                'user_ip' => $request->ip(),
                'request_data' => $request->only(['name', 'organization_id', 'expires_at', 'quiz_type'])
            ]);

            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'organization_id' => 'required|exists:organizations,id',
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
                'temp_url' => Str::random(32),
                'expires_at' => $validated['expires_at'],
                'is_active' => true,
                'is_reduced' => $isReduced,
                'is_cisneros' => $isCisneros,
            ]);

            // Crear campos personalizados si existen
            if (isset($validated['custom_fields']) && is_array($validated['custom_fields'])) {
                foreach ($validated['custom_fields'] as $customField) {
                    if (!empty($customField['name']) && !empty($customField['type'])) {
                        $quiz->customFields()->create([
                            'name' => $customField['name'],
                            'type' => $customField['type'],
                        ]);
                    }
                }
            }

            \Illuminate\Support\Facades\Log::info('Quiz creado exitosamente', [
                'quiz_id' => $quiz->id,
                'quiz_name' => $quiz->name,
                'organization_id' => $quiz->organization_id,
                'quiz_type' => $validated['quiz_type']
            ]);

            return redirect()->route('quizzes.index')
                ->with('success', 'Examen creado exitosamente');
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Illuminate\Support\Facades\Log::warning('Validación fallida al crear quiz', [
                'errors' => $e->errors(),
                'user_ip' => $request->ip()
            ]);
            
            return back()->withErrors($e->errors())->withInput()
                ->with('error', 'Los datos proporcionados no son válidos. Por favor, revise los campos.');
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error al crear quiz', [
                'error_message' => $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'user_ip' => $request->ip()
            ]);

            return back()->withInput()
                ->with('error', 'Error al crear el examen. Por favor, inténtelo nuevamente.');
        }
    }

    public function toggle(Quiz $quiz)
    {
        $quiz->update(['is_active' => !$quiz->is_active]);

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
                        if (!empty($fieldData['name']) && !empty($fieldData['type'])) {
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
                'trace' => $e->getTraceAsString()
            ]);

            return back()->withErrors(['error' => 'Error al actualizar el examen']);
        }
    }

    public function showTemp($tempUrl)
    {
        try {
            \Illuminate\Support\Facades\Log::info('Acceso a quiz temporal', [
                'temp_url' => $tempUrl,
                'user_ip' => request()->ip(),
                'user_agent' => request()->userAgent()
            ]);

            $quiz = Quiz::with(['organization.occupationPositions', 'organization.departmentAreas', 'customFields'])
                ->where('temp_url', $tempUrl)
                ->where('is_active', true)
                ->where('expires_at', '>', now())
                ->first();

            if (!$quiz) {
                \Illuminate\Support\Facades\Log::warning('Quiz no encontrado o no disponible', [
                    'temp_url' => $tempUrl,
                    'user_ip' => request()->ip()
                ]);
                
                abort(404, 'El examen no está disponible o ha expirado.');
            }

            \Illuminate\Support\Facades\Log::info('Quiz cargado exitosamente', [
                'quiz_id' => $quiz->id,
                'quiz_name' => $quiz->name,
                'organization_id' => $quiz->organization_id,
                'is_reduced' => $quiz->is_reduced,
                'is_cisneros' => $quiz->is_cisneros
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error al cargar quiz temporal', [
                'temp_url' => $tempUrl,
                'error_message' => $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'user_ip' => request()->ip()
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
                        'acontecimientos_traumaticos' => config('referencia_iii_reduced.acontecimientos_traumaticos')
                    ],
                    'reference_i' => config('referencia_i'),
                    'reference_v' => config('referencia_v'),
                    'custom_fields' => $quiz->customFields->map(function ($field) {
                        return [
                            'id' => $field->id,
                            'name' => $field->name,
                            'type' => $field->type,
                        ];
                    })->toArray()
                ]
            ]);
        } elseif ($quiz->is_reduced) {
            // Quiz reducido - solo acontecimientos traumáticos
            return Inertia::render('Quiz/TakeReduced', [
                'quiz' => [
                    'id' => $quiz->id,
                    'name' => $quiz->name,
                    'organization' => $organizationData,
                    'questions' => [
                        'acontecimientos_traumaticos' => config('referencia_iii_reduced.acontecimientos_traumaticos')
                    ],
                    'reference_i' => config('referencia_i'),
                    'reference_v' => config('referencia_v'),
                    'custom_fields' => $quiz->customFields->map(function ($field) {
                        return [
                            'id' => $field->id,
                            'name' => $field->name,
                            'type' => $field->type,
                        ];
                    })->toArray()
                ]
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
                        'conditional_sections' => config('referencia_iii.conditional_sections'),
                        'acontecimientos_traumaticos' => config('referencia_iii.acontecimientos_traumaticos')
                    ],
                    'reference_i' => config('referencia_i'),
                    'reference_v' => config('referencia_v'),
                    'custom_fields' => $quiz->customFields->map(function ($field) {
                        return [
                            'id' => $field->id,
                            'name' => $field->name,
                            'type' => $field->type,
                        ];
                    })->toArray()
                ]
            ]);
        }
    }

    public function submit(Request $request, Quiz $quiz)
    {
        \Illuminate\Support\Facades\Log::info('Quiz submit iniciado', [
            'quiz_id' => $quiz->id,
            'organization_id' => $quiz->organization_id,
            'user_ip' => $request->ip(),
            'user_agent' => $request->userAgent()
        ]);

        try {
            $validated = $request->validate([
                'referencia_iii' => 'nullable|string',
                'referencia_i' => 'nullable|string',
                'referencia_v' => 'nullable|string',
                'escala_cisneros' => 'nullable|string',
                'custom_fields' => 'nullable|string',
                'ine_frente' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
                'ine_reverso' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
            ]);

            // Decodificar datos JSON
            $decodedData = [];
            foreach (['referencia_iii', 'referencia_i', 'referencia_v', 'escala_cisneros', 'custom_fields'] as $key) {
                if (isset($validated[$key]) && !empty($validated[$key])) {
                    $decoded = json_decode($validated[$key], true);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        $decodedData[$key] = $decoded;
                    } else {
                        \Illuminate\Support\Facades\Log::warning('Error al decodificar JSON', [
                            'key' => $key,
                            'json_error' => json_last_error_msg(),
                            'data' => $validated[$key]
                        ]);
                    }
                }
            }
            
            // Reemplazar datos validados con datos decodificados
            $validated = array_merge($validated, $decodedData);

            \Illuminate\Support\Facades\Log::info('Datos validados correctamente', [
                'quiz_id' => $quiz->id,
                'data_keys' => array_keys($validated),
                'answer_counts' => [
                    'referencia_iii' => isset($validated['referencia_iii']) ? count($validated['referencia_iii']) : 0,
                    'referencia_i' => count($validated['referencia_i']),
                    'referencia_v' => count($validated['referencia_v']),
                    'escala_cisneros' => isset($validated['escala_cisneros']) ? count($validated['escala_cisneros']) : 0,
                ]
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Illuminate\Support\Facades\Log::warning('Validación fallida en quiz submit', [
                'quiz_id' => $quiz->id,
                'errors' => $e->errors(),
                'user_ip' => $request->ip()
            ]);
            
            return back()->withErrors($e->errors())->withInput()
                ->with('error', 'Los datos enviados no son válidos. Por favor, complete todos los campos requeridos.');
        }

        // Verificar que el quiz esté activo y no haya expirado
        if (!$quiz->is_active || $quiz->isExpired()) {
            \Illuminate\Support\Facades\Log::warning('Intento de acceso a quiz inactivo o expirado', [
                'quiz_id' => $quiz->id,
                'is_active' => $quiz->is_active,
                'expires_at' => $quiz->expires_at,
                'current_time' => now(),
                'user_ip' => $request->ip()
            ]);
            
            return back()->with('error', 'El examen no está disponible o ha expirado');
        }

        try {
            // Generar personal_id automáticamente basado en los folios de la organización
            $personalId = $this->generateNextPersonalId($quiz->organization_id);
            
            \Illuminate\Support\Facades\Log::info('Personal ID generado', [
                'quiz_id' => $quiz->id,
                'personal_id' => $personalId,
                'organization_id' => $quiz->organization_id
            ]);

            // Generar el siguiente folio incremental para la organización
            $folioNumber = $this->getNextFolioNumber($quiz->organization_id);
            
            \Illuminate\Support\Facades\Log::info('Folio generado', [
                'quiz_id' => $quiz->id,
                'folio' => $folioNumber,
                'organization_id' => $quiz->organization_id
            ]);

            // Procesar imágenes del INE si están presentes
            $ineImages = $this->processIneImages($request, $folioNumber, $personalId);

            // Wrap operations in database transaction for data integrity
            DB::transaction(function () use ($folioNumber, $personalId, $quiz, $validated, $ineImages) {
                \Illuminate\Support\Facades\Log::info('Iniciando transacción de base de datos', [
                    'quiz_id' => $quiz->id,
                    'folio' => $folioNumber,
                    'personal_id' => $personalId
                ]);

                // Store online answers using the new method
                $this->storeOnlineAnswers($folioNumber, $personalId, $quiz->organization_id, $quiz->id, $validated, $ineImages);

                // Crear un registro de folio virtual para mantener la trazabilidad
                $this->createVirtualFolio($quiz->organization_id, $folioNumber);
                
                \Illuminate\Support\Facades\Log::info('Transacción completada exitosamente', [
                    'quiz_id' => $quiz->id,
                    'folio' => $folioNumber,
                    'personal_id' => $personalId
                ]);
            });

            \Illuminate\Support\Facades\Log::info('Respuestas guardadas exitosamente', [
                'quiz_id' => $quiz->id,
                'folio' => $folioNumber,
                'personal_id' => $personalId,
                'organization_id' => $quiz->organization_id
            ]);

            // Redirigir a la página de confirmación
            return Inertia::render('Quiz/Completed', [
                'quiz' => [
                    'id' => $quiz->id,
                    'name' => $quiz->name,
                    'is_reduced' => $quiz->is_reduced,
                    'is_cisneros' => $quiz->is_cisneros,
                    'organization' => [
                        'id' => $quiz->organization->id,
                        'name' => $quiz->organization->name,
                    ]
                ],
                'folio' => $folioNumber,
                'personalId' => $personalId,
                'message' => 'Examen completado exitosamente'
            ]);
        } catch (\Illuminate\Database\QueryException $e) {
            \Illuminate\Support\Facades\Log::error('Error de base de datos al guardar examen', [
                'quiz_id' => $quiz->id,
                'organization_id' => $quiz->organization_id,
                'error_code' => $e->getCode(),
                'error_message' => $e->getMessage(),
                'sql_state' => $e->errorInfo[0] ?? null,
                'user_ip' => $request->ip()
            ]);

            return back()->with('error', 'Error al guardar el examen en la base de datos. Por favor, inténtelo nuevamente.');
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error general al guardar examen', [
                'quiz_id' => $quiz->id,
                'organization_id' => $quiz->organization_id,
                'error_message' => $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'stack_trace' => $e->getTraceAsString(),
                'user_ip' => $request->ip()
            ]);

            return back()->with('error', 'Error al guardar el examen. Por favor, inténtelo nuevamente.');
        }
    }

    /**
     * Obtiene el siguiente folio incremental para la organización
     */
    private function getNextFolioNumber($organizationId)
    {
        try {
            \Illuminate\Support\Facades\Log::info('Generando siguiente folio', [
                'organization_id' => $organizationId
            ]);

            // Buscar el último folio usado en la organización
            $lastFolio = \App\Models\Folio::whereHas('folioBatch', function ($query) use ($organizationId) {
                $query->where('organization_id', $organizationId);
            })
                ->orderBy('numeric_value', 'desc')
                ->first();

            // Si no hay folios, empezar desde 0001
            if (!$lastFolio) {
                \Illuminate\Support\Facades\Log::info('No se encontraron folios previos, iniciando desde 0001', [
                    'organization_id' => $organizationId
                ]);
                return '0001';
            }

            // Incrementar el número del último folio
            $nextNumber = $lastFolio->numeric_value + 1;
            $folioNumber = str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
            
            \Illuminate\Support\Facades\Log::info('Folio generado exitosamente', [
                'organization_id' => $organizationId,
                'last_folio_number' => $lastFolio->numeric_value,
                'new_folio_number' => $folioNumber
            ]);

            return $folioNumber;
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error al generar folio', [
                'organization_id' => $organizationId,
                'error_message' => $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine()
            ]);
            
            throw new \Exception('Error al generar el número de folio: ' . $e->getMessage());
        }
    }

    /**
     * Genera el siguiente personal_id disponible para la organización
     */
    private function generateNextPersonalId($organizationId)
    {
        try {
            \Illuminate\Support\Facades\Log::info('Generando siguiente personal_id', [
                'organization_id' => $organizationId
            ]);

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
                \Illuminate\Support\Facades\Log::info('No se encontraron registros previos, iniciando personal_id desde 0001', [
                    'organization_id' => $organizationId
                ]);
                return '0001';
            }

            // Incrementar el último personal_id
            $nextNumber = $lastPersonalId + 1;
            $personalId = str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
            
            \Illuminate\Support\Facades\Log::info('Personal ID generado exitosamente', [
                'organization_id' => $organizationId,
                'last_personal_id' => $lastPersonalId,
                'new_personal_id' => $personalId
            ]);

            return $personalId;
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error al generar personal_id', [
                'organization_id' => $organizationId,
                'error_message' => $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine()
            ]);
            
            throw new \Exception('Error al generar el personal ID: ' . $e->getMessage());
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
                        'json_error' => json_last_error_msg()
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
                'error_message' => $e->getMessage()
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
            'ñ' => 'n', 'Ñ' => 'n', 'ç' => 'c', 'Ç' => 'c'
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
                $frenteFileName = "ine_frente_{$folio}_{$personalId}." . $ineFrente->getClientOriginalExtension();
                $frentePath = $ineFrente->storeAs('ine_images', $frenteFileName, 'public');
                $ineImages['ine_frente'] = $frentePath;
                
                \Illuminate\Support\Facades\Log::info('INE frente procesado', [
                    'folio' => $folio,
                    'personal_id' => $personalId,
                    'file_path' => $frentePath
                ]);
            }

            // Procesar INE reverso
            if ($request->hasFile('ine_reverso')) {
                $ineReverso = $request->file('ine_reverso');
                $reversoFileName = "ine_reverso_{$folio}_{$personalId}." . $ineReverso->getClientOriginalExtension();
                $reversoPath = $ineReverso->storeAs('ine_images', $reversoFileName, 'public');
                $ineImages['ine_reverso'] = $reversoPath;
                
                \Illuminate\Support\Facades\Log::info('INE reverso procesado', [
                    'folio' => $folio,
                    'personal_id' => $personalId,
                    'file_path' => $reversoPath
                ]);
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error al procesar imágenes del INE', [
                'folio' => $folio,
                'personal_id' => $personalId,
                'error_message' => $e->getMessage()
            ]);
            
            throw new \Exception('Error al procesar las imágenes del INE: ' . $e->getMessage());
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
            \Illuminate\Support\Facades\Log::info('Iniciando almacenamiento de respuestas online', [
                'folio' => $folio,
                'personal_id' => $personalId,
                'organization_id' => $organizationId,
                'quiz_id' => $quizId,
                'answer_sections' => array_keys($answers)
            ]);

            $records = [];
            $recordCounts = [
                'referencia_iii' => 0,
                'referencia_i' => 0,
                'referencia_v' => 0,
                'escala_cisneros' => 0,
                'custom_fields' => 0
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
                                'question_key' => $key . '_' . $subKey,
                                'answer_value' => $this->formatAnswerValue($subValue),
                                'reference_guide' => 'III',
                                'created_at' => now(),
                                'updated_at' => now()
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
                            'updated_at' => now()
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
                        'updated_at' => now()
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
                                'question_key' => $key . '_' . $subKey,
                                'answer_value' => $this->formatAnswerValue($subValue),
                                'reference_guide' => 'V',
                                'created_at' => now(),
                                'updated_at' => now()
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
                            'updated_at' => now()
                        ];
                        $recordCounts['referencia_v']++;
                    }
                }
            }

            // Process INE images if present
            if (!empty($ineImages)) {
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
                        'updated_at' => now()
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
                        'updated_at' => now()
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
                    if (!empty($value)) {
                        // Try to get the real field name from the database
                        $fieldName = 'custom_field_' . $key; // Default fallback
                        
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
                            'updated_at' => now()
                        ];
                        $recordCounts['custom_fields']++;
                    }
                }
            }
            
            // Batch insert for performance optimization
            if (!empty($records)) {
                \App\Models\OnlineAnswer::insert($records);
                
                \Illuminate\Support\Facades\Log::info('Respuestas almacenadas exitosamente', [
                    'folio' => $folio,
                    'personal_id' => $personalId,
                    'total_records' => count($records),
                    'record_counts' => $recordCounts
                ]);
            } else {
                \Illuminate\Support\Facades\Log::warning('No se encontraron respuestas para almacenar', [
                    'folio' => $folio,
                    'personal_id' => $personalId,
                    'quiz_id' => $quizId
                ]);
            }
        } catch (\Illuminate\Database\QueryException $e) {
            \Illuminate\Support\Facades\Log::error('Error de base de datos al almacenar respuestas online', [
                'folio' => $folio,
                'personal_id' => $personalId,
                'quiz_id' => $quizId,
                'error_code' => $e->getCode(),
                'error_message' => $e->getMessage(),
                'sql_state' => $e->errorInfo[0] ?? null
            ]);
            
            throw new \Exception('Error al guardar las respuestas en la base de datos: ' . $e->getMessage());
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error general al almacenar respuestas online', [
                'folio' => $folio,
                'personal_id' => $personalId,
                'quiz_id' => $quizId,
                'error_message' => $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine()
            ]);
            
            throw new \Exception('Error al procesar las respuestas del examen: ' . $e->getMessage());
        }
    }

    /**
     * Crea un folio virtual para mantener la trazabilidad del quiz
     * Modificado para trabajar sin evaluation_id, manteniendo compatibilidad con el sistema de lotes existente
     */
    private function createVirtualFolio($organizationId, $folioNumber)
    {
        try {
            \Illuminate\Support\Facades\Log::info('Creando folio virtual', [
                'organization_id' => $organizationId,
                'folio_number' => $folioNumber
            ]);

            // Buscar o crear un lote virtual para quiz de esta organización
            $virtualBatch = \App\Models\FolioBatch::firstOrCreate([
                'organization_id' => $organizationId,
                'name' => 'Quiz Virtual Batch',
                'type' => 'en_linea'
            ], [
                'description' => 'Lote virtual para folios generados por quiz',
                'start_number' => 1,
                'end_number' => 9999,
                'quantity' => 9999
            ]);

            \Illuminate\Support\Facades\Log::info('Lote virtual obtenido/creado', [
                'batch_id' => $virtualBatch->id,
                'organization_id' => $organizationId
            ]);

            // Crear el folio virtual
            $folio = \App\Models\Folio::create([
                'folio_batch_id' => $virtualBatch->id,
                'folio_number' => $folioNumber,
                'numeric_value' => intval($folioNumber),
                'used' => true,
                'used_at' => now()
            ]);

            \Illuminate\Support\Facades\Log::info('Folio virtual creado exitosamente', [
                'folio_id' => $folio->id,
                'folio_number' => $folioNumber,
                'batch_id' => $virtualBatch->id
            ]);
        } catch (\Illuminate\Database\QueryException $e) {
            \Illuminate\Support\Facades\Log::error('Error de base de datos al crear folio virtual', [
                'organization_id' => $organizationId,
                'folio_number' => $folioNumber,
                'error_code' => $e->getCode(),
                'error_message' => $e->getMessage(),
                'sql_state' => $e->errorInfo[0] ?? null
            ]);
            
            throw new \Exception('Error al crear el folio virtual en la base de datos: ' . $e->getMessage());
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error general al crear folio virtual', [
                'organization_id' => $organizationId,
                'folio_number' => $folioNumber,
                'error_message' => $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine()
            ]);
            
            throw new \Exception('Error al crear el folio virtual: ' . $e->getMessage());
        }
    }
}
