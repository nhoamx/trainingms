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
            ->withCount('evaluations')
            ->select('id', 'name', 'temp_url', 'expires_at', 'is_active')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($quiz) {
                $url = route('quiz.temp', $quiz->temp_url);
                return [
                    'id' => $quiz->id,
                    'name' => $quiz->name,
                    'temp_url' => $url,
                    'qr_code' => 'data:image/svg+xml;base64,' . base64_encode(QrCode::format('svg')->size(500)->generate($url)),
                    'expires_at' => $quiz->expires_at->format('Y-m-d H:i'),
                    'is_active' => $quiz->is_active && !$quiz->isExpired(),
                    'evaluations_count' => $quiz->evaluations_count,
                ];
            });

        return Inertia::render('Quiz/Index', [
            'quizzes' => $quizzes,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'expires_at' => 'required|date|after:now',
        ]);

        $quiz = Quiz::create([
            'name' => $validated['name'],
            'temp_url' => Str::random(32),
            'expires_at' => $validated['expires_at'],
            'is_active' => true,
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
        $quiz = Quiz::where('temp_url', $tempUrl)
            ->where('is_active', true)
            ->where('expires_at', '>', now())
            ->firstOrFail();

        // Usar la misma vista que OnlineEvaluation pero con datos del Quiz
        return Inertia::render('OnlineEvaluation/Form', [
            'quiz' => $quiz,
            'title' => 'Examen: ' . $quiz->name,
            'questionConfig' => [
                'guide_I' => config('referencia_i'),
                'guide_III' => config('referencia_iii'),
                'guide_V' => config('referencia_v')
            ],
            'isQuizMode' => true // Flag para distinguir del modo folio
        ]);
    }

    public function submit(Request $request, Quiz $quiz)
    {
        $validated = $request->validate([
            'personal_id' => 'required|string|size:4',
            'reference_guide' => 'required|in:I,III,V',
            'answers' => 'required|array',
        ]);

        // Verificar que el quiz esté activo y no haya expirado
        if (!$quiz->is_active || $quiz->isExpired()) {
            return response()->json([
                'message' => 'El examen no está disponible o ha expirado'
            ], 422);
        }

        try {
            // Crear la evaluación usando el sistema OnlineEvaluation
            $evaluation = \App\Models\Evaluation::create([
                'document_id' => null,
                'folio' => $quiz->temp_url . '_' . $validated['personal_id'], // Folio único basado en quiz
                'personal_id' => $validated['personal_id'],
                'organization_id' => null, // Los quiz no están asociados a organizaciones específicas
                'quiz_id' => $quiz->id,
                'data' => $validated['answers'],
                'reference_guide' => $validated['reference_guide'],
            ]);

            // Guardar respuestas directamente en Questions
            $evaluation->saveOnlineAnswers($validated['answers'], $validated['reference_guide']);

            return response()->json([
                'message' => 'Examen completado exitosamente',
                'evaluation_id' => $evaluation->id
            ]);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error al guardar examen desde Quiz: ' . $e->getMessage());
            
            return response()->json([
                'message' => 'Error al guardar el examen'
            ], 500);
        }
    }
}
