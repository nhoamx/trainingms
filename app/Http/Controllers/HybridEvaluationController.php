<?php

namespace App\Http\Controllers;

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

        // If already completed, show a nice completion page instead of error
        if ($evaluation->referencia_iii_answers !== null) {
            return Inertia::render('Hibrido/Completed', [
                'folio' => $evaluation->folio,
                'organizationName' => $evaluation->organization?->name ?? 'Organización',
                'completedAt' => $evaluation->processed_at?->format('d/m/Y H:i'),
            ]);
        }

        // Get question configurations
        // Note: The component expects the referencia_iii config directly as props.questions
        $referenciaIIIQuestions = config('referencia_iii');
        $referenciaIQuestions = config('referencia_i');

        return Inertia::render('Hibrido/Take', [
            'evaluationId' => $evaluation->id,
            'folio' => $evaluation->folio,
            'organizationName' => $evaluation->organization?->name ?? 'Organización',
            'questions' => $referenciaIIIQuestions,
            'referencia_i_questions' => $referenciaIQuestions,
        ]);
    }
}
