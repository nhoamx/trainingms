<?php

namespace App\Http\Controllers;

use App\Models\Quiz;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class QuizController extends Controller
{
    public function index()
    {
        $quizzes = Quiz::query()
            ->with('organization')
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
                    'evaluations_count' => $quiz->evaluations_count,
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
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'organization_id' => 'required|exists:organizations,id',
            'expires_at' => 'required|date|after:now',
            'is_reduced' => 'boolean',
        ]);

        $quiz = Quiz::create([
            'name' => $validated['name'],
            'organization_id' => $validated['organization_id'],
            'temp_url' => Str::random(32),
            'expires_at' => $validated['expires_at'],
            'is_active' => true,
            'is_reduced' => $validated['is_reduced'] ?? false,
        ]);

        return redirect()->route('quizzes.index')
            ->with('success', 'Examen creado exitosamente');
    }

    public function toggle(Quiz $quiz)
    {
        $quiz->update(['is_active' => !$quiz->is_active]);
        
        return back()->with('success', 'Estado del examen actualizado');
    }

    public function showTemp($tempUrl)
    {
        $quiz = Quiz::with(['organization.occupationPositions', 'organization.departmentAreas'])
            ->where('temp_url', $tempUrl)
            ->where('is_active', true)
            ->where('expires_at', '>', now())
            ->firstOrFail();

        // Preparar datos de la organización
        $organizationData = [
            'id' => $quiz->organization->id,
            'name' => $quiz->organization->name,
            'occupation_positions' => $quiz->organization->occupationPositions->pluck('name', 'id')->toArray(),
            'department_areas' => $quiz->organization->departmentAreas->pluck('name', 'id')->toArray(),
        ];

        // Decidir qué vista usar basado en is_reduced
        if ($quiz->is_reduced) {
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
                    'reference_v' => config('referencia_v')
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
                    'reference_v' => config('referencia_v')
                ]
            ]);
        }
    }

    public function submit(Request $request, Quiz $quiz)
    {
        $validated = $request->validate([
            'referencia_iii' => 'required|array',
            'referencia_i' => 'array',
            'referencia_v' => 'required|array'
        ]);

        // Verificar que el quiz esté activo y no haya expirado
        if (!$quiz->is_active || $quiz->isExpired()) {
            return back()->with('error', 'El examen no está disponible o ha expirado');
        }

        // Generar personal_id automáticamente basado en los folios de la organización
        $personalId = $this->generateNextPersonalId($quiz->organization_id);

        // Combinar todas las respuestas en un solo array
        $allAnswers = array_merge(
            $validated['referencia_iii'] ?? [],
            $validated['referencia_i'] ?? [],
            $validated['referencia_v'] ?? []
        );

        // Determinar la guía de referencia principal (por defecto III)
        $referenceGuide = 'III';

        try {
            // Generar el siguiente folio incremental para la organización
            $folioNumber = $this->getNextFolioNumber($quiz->organization_id);

            // Crear la evaluación con el folio generado
            $evaluation = \App\Models\Evaluation::create([
                'document_id' => null,
                'folio' => $folioNumber,
                'personal_id' => $personalId,
                'organization_id' => $quiz->organization_id,
                'quiz_id' => $quiz->id,
                'data' => $allAnswers,
                'reference_guide' => $referenceGuide,
            ]);

            // Crear un registro de folio virtual para mantener la trazabilidad
            $this->createVirtualFolio($quiz->organization_id, $folioNumber, $evaluation->id);

            // Guardar respuestas directamente en Questions
            $evaluation->saveOnlineAnswers($allAnswers, $referenceGuide);

            // Redirigir a la página de confirmación
            return Inertia::render('Quiz/Completed', [
                'quiz' => [
                    'id' => $quiz->id,
                    'name' => $quiz->name,
                    'is_reduced' => $quiz->is_reduced,
                    'organization' => [
                        'id' => $quiz->organization->id,
                        'name' => $quiz->organization->name,
                    ]
                ],
                'folio' => $folioNumber,
                'personalId' => $personalId,
                'message' => 'Examen completado exitosamente'
            ]);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error al guardar examen desde Quiz: ' . $e->getMessage());
            
            return back()->with('error', 'Error al guardar el examen. Por favor, inténtelo nuevamente.');
        }
    }

    /**
     * Obtiene el siguiente folio incremental para la organización
     */
    private function getNextFolioNumber($organizationId)
    {
        // Buscar el último folio usado en la organización
        $lastFolio = \App\Models\Folio::whereHas('folioBatch', function($query) use ($organizationId) {
                $query->where('organization_id', $organizationId);
            })
            ->orderBy('numeric_value', 'desc')
            ->first();

        // Si no hay folios, empezar desde 0001
        if (!$lastFolio) {
            return '0001';
        }

        // Incrementar el número del último folio
        $nextNumber = $lastFolio->numeric_value + 1;
        return str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Genera el siguiente personal_id disponible para la organización
     */
    private function generateNextPersonalId($organizationId)
    {
        // Buscar el último personal_id usado en evaluaciones de esta organización
        $lastEvaluation = \App\Models\Evaluation::where('organization_id', $organizationId)
            ->whereNotNull('personal_id')
            ->orderByRaw('CAST(personal_id AS UNSIGNED) DESC')
            ->first();

        // Si no hay evaluaciones previas, empezar desde 0001
        if (!$lastEvaluation) {
            return '0001';
        }

        // Incrementar el último personal_id
        $nextNumber = intval($lastEvaluation->personal_id) + 1;
        return str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Crea un folio virtual para mantener la trazabilidad del quiz
     */
    private function createVirtualFolio($organizationId, $folioNumber, $evaluationId)
    {
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

        // Crear el folio virtual
        \App\Models\Folio::create([
            'folio_batch_id' => $virtualBatch->id,
            'folio_number' => $folioNumber,
            'numeric_value' => intval($folioNumber),
            'used' => true,
            'used_at' => now()
        ]);
    }
}
