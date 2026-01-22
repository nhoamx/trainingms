<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateHybridEvaluationRequest;
use App\Models\PaperEvaluation;
use Inertia\Inertia;
use Inertia\Response;

class HybridEvaluationController extends Controller
{
    /**
     * Display the hybrid evaluation form for completing online portion
     */
    public function show(string $folio): Response
    {
        // Find the paper evaluation by folio
        // QR scanners may strip leading zeros, so try multiple patterns:
        // 1. Exact match with the provided folio
        // 2. Pad to 9 digits (full extended folio format)
        // 3. Match any folio that ends with the provided folio
        $evaluation = PaperEvaluation::with('organization')
            ->where('folio', $folio)
            ->orWhere('folio', str_pad($folio, 9, '0', STR_PAD_LEFT))
            ->orWhere('folio', 'like', "%$folio")
            ->first();

        // Validate evaluation exists
        if (! $evaluation) {
            abort(404, "Evaluación no encontrada. Verifica tu folio: $folio");
        }

        // Validate it's a hybrid evaluation
        if ($evaluation->source !== 'hybrid') {
            abort(403, 'Este folio no corresponde a una evaluación híbrida.');
        }

        // Validate it hasn't been completed yet (referencia_iii_answers should be null)
        if ($evaluation->referencia_iii_answers !== null) {
            abort(410, 'Esta evaluación ya ha sido completada.');
        }

        // Get question configurations
        // Note: The component expects the referencia_iii config directly as props.questions
        $referenciaIIIQuestions = config('referencia_iii');
        $referenciaIQuestions = config('guide_i_questions');

        return Inertia::render('Hibrido/Take', [
            'evaluationId' => $evaluation->id,
            'folio' => $evaluation->folio,
            'organizationName' => $evaluation->organization?->name ?? 'Organización',
            'questions' => $referenciaIIIQuestions,
            'referencia_i_questions' => $referenciaIQuestions,
        ]);
    }

    /**
     * Update the hybrid evaluation with online responses
     */
    public function update(UpdateHybridEvaluationRequest $request, string $folio)
    {
        // Find the evaluation using the same logic as show()
        $evaluation = PaperEvaluation::where('folio', $folio)
            ->orWhere('folio', str_pad($folio, 9, '0', STR_PAD_LEFT))
            ->orWhere('folio', 'like', "%$folio")
            ->first();

        if (! $evaluation) {
            abort(404, 'Evaluación no encontrada.');
        }

        if ($evaluation->source !== 'hybrid') {
            abort(403, 'Este folio no corresponde a una evaluación híbrida.');
        }

        if ($evaluation->referencia_iii_answers !== null) {
            abort(410, 'Esta evaluación ya ha sido completada.');
        }

        $validated = $request->validated();

        // Update the evaluation with online responses
        $evaluation->update([
            'referencia_iii_answers' => $validated['referencia_iii'] ?? null,
            'referencia_i_answers' => $validated['referencia_i'] ?? null,
            'referencia_iii_conditional' => $validated['referencia_iii_conditional'] ?? null,
            'processing_status' => 'completed',
            'processed_at' => now(),
        ]);

        return redirect()->route('hybrid.show', $folio)
            ->with('success', 'Evaluación completada exitosamente.');
    }
}
