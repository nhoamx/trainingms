<?php

namespace App\Http\Controllers;

use App\Models\Evaluation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class OnlineEvaluationController extends Controller
{
    /**
     * Muestra el formulario de evaluación en línea
     */
    public function show($folio)
    {
        // Buscar el folio en los lotes de folios
        $folio_record = \App\Models\Folio::where('folio_number', $folio)
            ->whereHas('folioBatch', function ($query) {
                $query->where('type', 'en_linea');
            })
            ->with('folioBatch.organization')
            ->first();

        if (! $folio_record) {
            return redirect()->route('online-evaluation.access')
                ->with('error', 'Folio no válido o no corresponde a una evaluación en línea');
        }

        // Verificar si ya fue usado
        if ($folio_record->used) {
            return redirect()->route('online-evaluation.access')
                ->with('error', 'Este folio ya ha sido utilizado');
        }

        return Inertia::render('OnlineEvaluation/Form', [
            'folio' => $folio,
            'organization' => $folio_record->folioBatch->organization,
            'title' => 'Evaluación en Línea',
            'questionConfig' => [
                'guide_I' => config('referencia_i'),
                'guide_III' => config('referencia_iii'),
                'guide_V' => config('referencia_v'),
                'online_config' => config('online_evaluation_questions'),
            ],
        ]);
    }

    /**
     * Guarda la evaluación en línea
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'folio' => 'required|string',
            'personal_id' => 'required|string|size:4',
            'reference_guide' => 'required|in:I,III,V',
            'answers' => 'required|array',
        ]);

        // Verificar que el folio sea válido y no esté usado
        $folio_record = \App\Models\Folio::where('folio_number', $validated['folio'])
            ->whereHas('folioBatch', function ($query) {
                $query->where('type', 'en_linea');
            })
            ->with('folioBatch.organization')
            ->first();

        if (! $folio_record || $folio_record->used) {
            return response()->json([
                'message' => 'Folio no válido o ya utilizado',
            ], 422);
        }

        DB::beginTransaction();
        try {
            // Crear la evaluación
            $evaluation = Evaluation::create([
                'document_id' => null, // Para evaluaciones en línea no hay document_id
                'folio' => $validated['folio'],
                'personal_id' => $validated['personal_id'],
                'organization_id' => $folio_record->folioBatch->organization->id,
                'quiz_id' => null, // Las evaluaciones con folio no vienen de quiz
                'data' => $validated['answers'], // Mantener compatibilidad
                'reference_guide' => $validated['reference_guide'],
            ]);

            // Guardar respuestas directamente en Questions
            $evaluation->saveOnlineAnswers($validated['answers'], $validated['reference_guide']);

            // Marcar el folio como usado
            $folio_record->update([
                'used' => true,
                'used_at' => now(),
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Evaluación guardada exitosamente',
                'evaluation_id' => $evaluation->id,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al guardar evaluación en línea: '.$e->getMessage());

            return response()->json([
                'message' => 'Error al guardar la evaluación',
            ], 500);
        }
    }

    /**
     * Muestra el resultado de la evaluación
     */
    public function result($evaluationId)
    {
        $evaluation = Evaluation::with(['questions', 'organization'])
            ->findOrFail($evaluationId);

        return Inertia::render('OnlineEvaluation/Result', [
            'evaluation' => $evaluation,
            'title' => 'Resultado de Evaluación',
        ]);
    }
}
