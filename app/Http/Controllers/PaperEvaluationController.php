<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdatePaperEvaluationRequest;
use App\Models\PaperEvaluation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PaperEvaluationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified paper evaluation.
     */
    public function update(UpdatePaperEvaluationRequest $request, PaperEvaluation $paperEvaluation): RedirectResponse
    {
        $validated = $request->validated();
        $folioChanged = false;
        $newPersonalFolio = $paperEvaluation->personal_folio;

        if (isset($validated['evaluee_name'])) {
            $paperEvaluation->updateName($validated['evaluee_name']);
        }

        if (isset($validated['personal_folio'])) {
            $newPersonalFolio = $validated['personal_folio'];
            $paperEvaluation->updatePersonalFolio($newPersonalFolio);
            $folioChanged = true;
        }

        // If folio changed, redirect to new URL
        if ($folioChanged) {
            return to_route('organization.results.detail', [
                'organization' => $paperEvaluation->organization_id,
                'personalFolio' => $newPersonalFolio,
            ])->with('success', 'Evaluación actualizada exitosamente');
        }

        return back()->with('success', 'Evaluación actualizada exitosamente');
    }

    /**
     * Update only the evaluee name.
     */
    public function updateName(Request $request, PaperEvaluation $paperEvaluation): RedirectResponse
    {
        $request->validate([
            'evaluee_name' => 'required|string|max:255',
        ]);

        $paperEvaluation->updateName($request->input('evaluee_name'));

        return back()->with('success', 'Nombre actualizado exitosamente');
    }

    /**
     * Update demographic data (ocupacion and departamento).
     */
    public function updateDemographicData(Request $request, PaperEvaluation $paperEvaluation): RedirectResponse
    {
        $request->validate([
            'sexo' => 'nullable|string|in:Masculino,Femenino',
            'edad' => 'nullable|integer|min:15|max:99',
            'estado_civil' => 'nullable|string',
            'nivel_estudios' => 'nullable|string',
            'ocupacion' => 'nullable|string|max:100',
            'departamento' => 'nullable|string|max:100',
            'tipo_puesto' => 'nullable|string',
            'tipo_contratacion' => 'nullable|string',
            'tipo_personal' => 'nullable|string',
            'tipo_jornada' => 'nullable|string',
            'rotacion_turnos' => 'nullable|string',
            'tiempo_puesto_actual' => 'nullable|string',
            'tiempo_experiencia_laboral' => 'nullable|string',
        ]);

        $demographicData = $paperEvaluation->demographic_data ?? [];

        // Helper function to split text into two lines (max 50 chars each)
        $splitText = function (?string $text): array {
            if (empty($text)) {
                return ['fila1' => null, 'fila2' => null];
            }

            $text = trim($text);

            // Si el texto cabe en una línea (50 caracteres)
            if (strlen($text) <= 50) {
                return ['fila1' => $text, 'fila2' => null];
            }

            // Si es más largo, dividir en dos líneas
            $words = explode(' ', $text);
            $fila1 = '';
            $fila2 = '';
            $currentLine = 1;

            foreach ($words as $word) {
                if ($currentLine === 1) {
                    if (strlen($fila1.' '.$word) <= 50) {
                        $fila1 .= ($fila1 ? ' ' : '').$word;
                    } else {
                        $currentLine = 2;
                        $fila2 = $word;
                    }
                } else {
                    if (strlen($fila2.' '.$word) <= 50) {
                        $fila2 .= ($fila2 ? ' ' : '').$word;
                    } else {
                        // Si no cabe, truncar
                        break;
                    }
                }
            }

            return [
                'fila1' => $fila1 ?: null,
                'fila2' => $fila2 ?: null,
            ];
        };

        // Update sexo - convertir a minúsculas
        if ($request->has('sexo')) {
            $demographicData['sexo'] = strtolower($request->input('sexo'));
        }

        // Update edad - convertir a formato { decenas, unidades }
        if ($request->has('edad') && $request->input('edad') !== null) {
            $edad = (int) $request->input('edad');
            $demographicData['edad'] = [
                'decenas' => floor($edad / 10),
                'unidades' => $edad % 10,
            ];
        }

        // Update estado_civil
        if ($request->has('estado_civil')) {
            $estadoCivil = $request->input('estado_civil');
            // Convertir a formato snake_case para consistencia
            $estadoCivilMap = [
                'Unión libre' => 'union_libre',
                'Casado' => 'casado',
                'Soltero' => 'soltero',
                'Divorciado' => 'divorciado',
                'Viudo' => 'viudo',
            ];
            $demographicData['estado_civil'] = $estadoCivilMap[$estadoCivil] ?? strtolower($estadoCivil);
        }

        // Update nivel_estudios - convertir a formato complejo
        if ($request->has('nivel_estudios')) {
            $nivelEstudios = $request->input('nivel_estudios');

            if ($nivelEstudios === 'Sin formación') {
                $demographicData['nivel_estudios'] = ['sin_formacion' => ['seleccionado' => true]];
            } else {
                // Parsear "Primaria Terminada" -> nivel: primaria, completado: completo
                $parts = explode(' ', $nivelEstudios);
                $completado = array_pop($parts); // "Terminada" o "Incompleta"
                $nivel = implode(' ', $parts); // "Primaria", "Preparatoria o Bachillerato", etc.

                $nivelKey = strtolower(str_replace(' ', '_', $nivel));
                $completadoValue = $completado === 'Terminada' ? 'completo' : 'incompleto';

                $demographicData['nivel_estudios'] = [
                    $nivelKey => [
                        'seleccionado' => true,
                        'completado' => $completadoValue,
                    ],
                ];
            }
        }

        // Update ocupacion (with split text)
        if ($request->has('ocupacion')) {
            $demographicData['ocupacion_puesto'] = $splitText($request->input('ocupacion'));
            $demographicData['ocupacion'] = $splitText($request->input('ocupacion')); // Backward compatibility
        }

        // Update departamento (with split text)
        if ($request->has('departamento')) {
            $demographicData['departamento_seccion_area'] = $splitText($request->input('departamento'));
            $demographicData['departamento'] = $splitText($request->input('departamento')); // Backward compatibility
        }

        // Update tipo_puesto - convertir a snake_case
        if ($request->has('tipo_puesto')) {
            $tipoPuestoMap = [
                'Operativo' => 'operativo',
                'Profesional o técnico' => 'profesional_o_tecnico',
                'Supervisor' => 'supervisor',
                'Gerente' => 'gerente',
            ];
            $tipoPuesto = $request->input('tipo_puesto');
            $demographicData['tipo_puesto'] = $tipoPuestoMap[$tipoPuesto] ?? strtolower(str_replace(' ', '_', $tipoPuesto));
        }

        // Update tipo_contratacion - convertir a snake_case
        if ($request->has('tipo_contratacion')) {
            $tipoContratacionMap = [
                'Por obra o proyecto' => 'por_obra_o_proyecto',
                'Por tiempo determinado (temporal)' => 'por_tiempo_determinado_(temporal)',
                'Tiempo indeterminado' => 'tiempo_indeterminado',
                'Honorarios' => 'honorarios',
            ];
            $tipoContratacion = $request->input('tipo_contratacion');
            $demographicData['tipo_contratacion'] = $tipoContratacionMap[$tipoContratacion] ?? strtolower(str_replace(' ', '_', $tipoContratacion));
        }

        // Update tipo_personal - convertir a snake_case
        if ($request->has('tipo_personal')) {
            $tipoPersonalMap = [
                'Sindicalizado' => 'sindicalizado',
                'Confianza' => 'confianza',
                'Ninguno' => 'ninguno',
            ];
            $tipoPersonal = $request->input('tipo_personal');
            $demographicData['tipo_personal'] = $tipoPersonalMap[$tipoPersonal] ?? strtolower($tipoPersonal);
        }

        // Update tipo_jornada - convertir a snake_case
        if ($request->has('tipo_jornada')) {
            $tipoJornadaMap = [
                'Fijo nocturno (entre las 20:00 y 6:00 hrs)' => 'fijo_nocturno_(entre_las_20:00_y_6:00_hrs)',
                'Fijo diurno (entre las 6:00 y 20:00 hrs)' => 'fijo_diurno_(entre_las_6:00_y_20:00_hrs)',
                'Fijo mixto (combinación de nocturno y diurno)' => 'fijo_mixto_(combinacion_de_nocturno_y_diurno)',
            ];
            $tipoJornada = $request->input('tipo_jornada');
            $demographicData['tipo_jornada'] = $tipoJornadaMap[$tipoJornada] ?? strtolower(str_replace([' ', 'ó', 'á', 'é', 'í', 'ú'], ['_', 'o', 'a', 'e', 'i', 'u'], $tipoJornada));
        }

        // Update rotacion_turnos - convertir a snake_case
        if ($request->has('rotacion_turnos')) {
            $rotacionTurnosMap = [
                'Sí' => 'si',
                'No' => 'no',
            ];
            $rotacionTurnos = $request->input('rotacion_turnos');
            $demographicData['rotacion_turnos'] = $rotacionTurnosMap[$rotacionTurnos] ?? strtolower($rotacionTurnos);
        }

        // Update tiempo_puesto_actual - convertir a snake_case sin acentos
        if ($request->has('tiempo_puesto_actual')) {
            $tiempoPuestoMap = [
                'Menos de 6 meses' => 'menos_de_6_meses',
                'Entre 6 meses y 1 año' => 'entre_6_meses_y_1_ano',
                'Entre 1 a 4 años' => 'entre_1_a_4_anos',
                'Entre 5 a 9 años' => 'entre_5_a_9_anos',
                'Entre 10 a 14 años' => 'entre_10_a_14_anos',
                'Entre 15 a 19 años' => 'entre_15_a_19_anos',
                'Entre 20 a 24 años' => 'entre_20_a_24_anos',
                '25 años o más' => '25_anos_o_mas',
            ];
            $tiempoPuesto = $request->input('tiempo_puesto_actual');
            $demographicData['tiempo_puesto_actual'] = $tiempoPuestoMap[$tiempoPuesto] ?? strtolower(str_replace([' ', 'á', 'é', 'í', 'ó', 'ú'], ['_', 'a', 'e', 'i', 'o', 'u'], $tiempoPuesto));
        }

        // Update tiempo_experiencia_laboral - convertir a snake_case sin acentos
        if ($request->has('tiempo_experiencia_laboral')) {
            $tiempoExperienciaMap = [
                'Menos de 6 meses' => 'menos_de_6_meses',
                'Entre 6 meses y 1 año' => 'entre_6_meses_y_1_ano',
                'Entre 1 a 4 años' => 'entre_1_a_4_anos',
                'Entre 5 a 9 años' => 'entre_5_a_9_anos',
                'Entre 10 a 14 años' => 'entre_10_a_14_anos',
                'Entre 15 a 19 años' => 'entre_15_a_19_anos',
            ];
            $tiempoExperiencia = $request->input('tiempo_experiencia_laboral');
            $valorSnakeCase = $tiempoExperienciaMap[$tiempoExperiencia] ?? strtolower(str_replace([' ', 'á', 'é', 'í', 'ó', 'ú'], ['_', 'a', 'e', 'i', 'o', 'u'], $tiempoExperiencia));
            $demographicData['tiempo_experiencia_laboral'] = $valorSnakeCase;
            $demographicData['experiencia_laboral'] = $valorSnakeCase; // Backward compatibility
        }

        // Get raw_data and update it as well (for Referencia V evaluations)
        $rawData = $paperEvaluation->raw_data ?? [];

        // Sync all fields to raw_data to keep them consistent
        foreach ($demographicData as $key => $value) {
            $rawData[$key] = $value;
        }

        $paperEvaluation->update([
            'demographic_data' => $demographicData,
            'raw_data' => $rawData,
        ]);

        return back()->with('success', 'Datos demográficos actualizados exitosamente');
    }

    /**
     * Update only the personal folio.
     */
    public function updateFolio(UpdatePaperEvaluationRequest $request, PaperEvaluation $paperEvaluation): RedirectResponse
    {
        $validated = $request->validated();

        if (! isset($validated['personal_folio'])) {
            return back()->withErrors(['personal_folio' => 'El folio personal es requerido']);
        }

        $newPersonalFolio = $validated['personal_folio'];
        $organizationId = $paperEvaluation->organization_id;

        $paperEvaluation->updatePersonalFolio($newPersonalFolio);

        // Redirect to the new URL with the updated personal folio
        return to_route('organization.results.detail', [
            'organization' => $organizationId,
            'personalFolio' => $newPersonalFolio,
        ])->with('success', 'Folio actualizado exitosamente');
    }

    /**
     * Check if a folio is available (returns JSON for AJAX validation).
     */
    public function checkFolioAvailability(Request $request, PaperEvaluation $paperEvaluation): JsonResponse
    {
        $validated = $request->validate([
            'personal_folio' => ['required', 'string', 'regex:/^\d{4}$/'],
        ]);

        $newFolio = PaperEvaluation::generateFolio(
            $paperEvaluation->evaluation_type_code,
            $paperEvaluation->organization_code,
            $validated['personal_folio']
        );

        $isAvailable = PaperEvaluation::isFolioAvailable($newFolio, $paperEvaluation->id);

        return response()->json([
            'available' => $isAvailable,
            'new_folio' => $newFolio,
            'message' => $isAvailable
                ? 'Folio disponible'
                : "El folio {$newFolio} ya está en uso",
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    /**
     * Update hybrid evaluation with online answers (API endpoint)
     * Used when user completes Referencia III/I via QR code
     */
    public function updateHybrid(Request $request, PaperEvaluation $paperEvaluation): JsonResponse
    {
        // Validate it's a hybrid evaluation
        if ($paperEvaluation->source !== 'hybrid') {
            return response()->json([
                'success' => false,
                'message' => 'Esta evaluación no es de tipo híbrida',
            ], 403);
        }

        // Validate it's pending (not already completed)
        if ($paperEvaluation->processing_status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Esta evaluación ya ha sido procesada',
            ], 409);
        }

        // Validate and parse data
        $validated = $request->validate([
            'referencia_iii' => 'nullable|json',
            'referencia_iii_conditional' => 'nullable|json',
            'referencia_i' => 'nullable|json',
        ]);

        // Decode JSON strings to arrays
        $referenciaIII = isset($validated['referencia_iii']) ? json_decode($validated['referencia_iii'], true) : null;
        $referenciaIIIConditional = isset($validated['referencia_iii_conditional']) ? json_decode($validated['referencia_iii_conditional'], true) : null;
        $referenciaI = isset($validated['referencia_i']) ? json_decode($validated['referencia_i'], true) : null;

        // Update evaluation with online answers
        $paperEvaluation->update([
            'referencia_iii_answers' => $referenciaIII,
            'referencia_iii_conditional' => $referenciaIIIConditional,
            'referencia_i_answers' => $referenciaI,
            'processing_status' => 'completed',
            'processed_at' => now(),
            'raw_data' => array_merge(
                $paperEvaluation->raw_data ?? [],
                [
                    'online_completed_at' => now()->toIso8601String(),
                    'submission_ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ]
            ),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Evaluación completada exitosamente',
            'is_complete' => $paperEvaluation->fresh()->isComplete(),
        ]);
    }
}
