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
            'ocupacion' => 'nullable|string|max:100',
            'departamento' => 'nullable|string|max:100',
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

        // Update ocupacion
        if ($request->has('ocupacion')) {
            $demographicData['ocupacion'] = $splitText($request->input('ocupacion'));
        }

        // Update departamento
        if ($request->has('departamento')) {
            $demographicData['departamento'] = $splitText($request->input('departamento'));
        }

        // Get raw_data and update it as well (for Referencia V evaluations)
        $rawData = $paperEvaluation->raw_data ?? [];

        // Update both demographic_data and raw_data to keep them in sync
        if ($request->has('ocupacion')) {
            $rawData['ocupacion'] = $demographicData['ocupacion'];
        }

        if ($request->has('departamento')) {
            $rawData['departamento'] = $demographicData['departamento'];
        }

        $paperEvaluation->update([
            'demographic_data' => $demographicData,
            'raw_data' => $rawData,
        ]);

        return back()->with('success', 'Datos laborales actualizados exitosamente');
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
}
