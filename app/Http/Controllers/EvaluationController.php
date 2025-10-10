<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessPaperEvaluation;
use App\Models\Evaluation;
use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class EvaluationController extends Controller
{
    public function index()
    {
        // Get organizations with their evaluations
        $organizations = \App\Models\Organization::with('evaluations')->get();

        // Get evaluations without organization
        $noOrgEvaluations = \App\Models\Evaluation::whereNull('organization_id')->get();

        return Inertia::render('Evaluations/Results', [
            'title' => 'Evaluaciones',
            'organizations' => $organizations,
            'noOrgEvaluations' => $noOrgEvaluations,
        ]);
    }

    public function loadEvaluation()
    {
        return Inertia::render('Evaluations/LoadEvaluation', [
            'title' => 'Evaluaciones',
        ]);
    }

    public function store(Request $request)
    {
        // 1. Validar que se envíe un archivo y que sea un PDF de hasta 10MB
        $validated = $request->validate([
            'file' => 'required|file|mimes:pdf', // 10240 KB = 10 MB
        ]);

        // 2. Guardar el archivo en el disco 'public' en la carpeta "evaluations"
        $path = $request->file('file')->store('evaluations', 'public');

        // Obtener el path completo del archivo almacenado
        $fullPath = storage_path('app/public/'.$path);

        // Nombre o ID del contenedor (según salida de `docker ps`)
        $containerName = 'training-and-ms';

        // 3. Despachar el nuevo job para procesamiento mejorado
        ProcessPaperEvaluation::dispatch($fullPath, $containerName);

        // Redirigir a la lista de evaluaciones con un mensaje de éxito
        return redirect()
            ->route('evaluations.index')
            ->with('flash', [
                'type' => 'success',
                'title' => 'Evaluación cargada exitosamente',
                'message' => 'El archivo PDF ha sido cargado y está siendo procesado. Los resultados estarán disponibles en breve.',
            ]);
    }

    public function show(Evaluation $evaluation)
    {
        $evaluation->load(['answers.dimension', 'organization']);

        return Inertia::render('Evaluations/EvaluationDetails', [
            'title' => 'Detalles de Evaluación',
            'evaluation' => $evaluation,
            'answers' => $evaluation->answers,
        ]);
    }

    public function organizationEvaluations($organization)
    {
        // Remove the reference_guide filter to get all evaluations

        if ($organization === 'no-org') {
            $evaluations = Evaluation::whereNull('organization_id')->get(); // Get all evaluations without org
            $organizationData = ['id' => 'no-org', 'name' => 'Sin Organización'];
        } else {
            $organizationData = Organization::findOrFail($organization);
            $evaluations = $organizationData->evaluations()->get(); // Get all evaluations for the org
        }

        return Inertia::render('Evaluations/EvaluationList', [
            'organization' => $organizationData, // Pass the correct organization data
            'evaluations' => $evaluations,
        ]);
    }

    // Nueva función para reasignar evaluaciones
    public function reassignEvaluations(Request $request, Organization $organization)
    {

        $validated = $request->validate([
            // Corregir validación: esperar string (UUID) y verificar existencia
            'new_organization_id' => 'required|string|exists:organizations,id',
        ]);

        $newOrganizationId = $validated['new_organization_id']; // Ahora $newOrganizationId será el UUID string

        if ($organization->id == $newOrganizationId) {

            return back()->with('error', 'No se puede reasignar las evaluaciones a la misma organización.');
        }

        try {
            // Determinar las evaluaciones a actualizar
            $evaluationsToReassign = $organization->evaluations()->get();

            // Contar cuántas se van a actualizar
            $count = $evaluationsToReassign->count();

            if ($count === 0) {
                // Devolver JSON también en este caso para consistencia
                session()->flash('info', 'No hay evaluaciones asociadas a esta organización para reasignar.');

                return response()->json(['message' => 'No hay evaluaciones para reasignar.'], 200); // Código 200 OK pero con info
            }

            $newOrganization = Organization::findOrFail($newOrganizationId);

            DB::transaction(function () use ($evaluationsToReassign, $newOrganizationId) {
                $updatedCount = 0;
                foreach ($evaluationsToReassign as $evaluation) {
                    // Usar el $newOrganizationId (UUID string) correcto
                    $evaluation->update(['organization_id' => $newOrganizationId]);
                    $updatedCount++;
                }
            });

            // Devolver una redirección con el mensaje flash (Estilo Inertia)
            return redirect()->route('organizations.evaluations', ['organization' => $newOrganizationId])
                ->with('success', "Se reasignaron {$count} evaluaciones exitosamente a {$newOrganization->name}.");

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::error('Error al encontrar la organización: '.$e->getMessage()); // Log error más específico

            return back()->with('error', 'La organización de origen no fue encontrada.');
        } catch (\Exception $e) {
            Log::error('Error durante la reasignación: '.$e->getMessage()); // Log excepción
            Log::error('Stack trace: '.$e->getTraceAsString()); // Log stack trace

            return back()->with('error', 'Ocurrió un error inesperado durante la reasignación: '.$e->getMessage());
        }
    }

    public function destroy(Evaluation $evaluation)
    {
        // Eliminar la evaluación
        $evaluation->delete();

        // Redirigir a la lista de evaluaciones con un mensaje de éxito
        return redirect()
            ->back()
            ->with('flash', [
                'type' => 'success',
                'title' => 'Evaluación eliminada exitosamente',
                'message' => 'La evaluación ha sido eliminada.',
            ]);
    }
}
