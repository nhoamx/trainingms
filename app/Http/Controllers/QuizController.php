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
            'name' => $ validated['name'],
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

        return Inertia::render('Quiz/Take', [
            'quiz' => [
                'id' => $quiz->id,
                'name' => $quiz->name,
                'questions' => [
                    'general' => config('quiz_questions.general'),
                    'conditional_sections' => config('quiz_questions.conditional_sections'),
                    'acontecimientos_traumaticos' => config('quiz_questions.acontecimientos_traumaticos')
                ],
                'reference_i' => config('referencia_i'),
                'reference_v' => config('referencia_v')
            ]
        ]);
    }

    public function submit(Request $request, Quiz $quiz)
    {
        $validated = $request->validate([
            'reference_iii' => 'required|array',
            'reference_i' => 'array',
            'reference_v' => 'required|array'
        ]);

        // Aquí guardarías las respuestas en tu base de datos
        // Por ejemplo:
        // $quiz->answers()->create([
        //     'user_id' => auth()->id(),
        //     'answers' => $validated
        // ]);

        return redirect()->back()->with('success', 'Examen completado exitosamente');
    }
}
