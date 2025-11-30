<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessPaperEvaluation;
use App\Models\Evaluation;
use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
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
        // 1. Validar que se envíen archivos PDF (máximo 20 para evitar sobrecarga)
        $validated = $request->validate([
            'files' => 'required|array|min:1|max:20',
            'files.*' => 'file|mimes:pdf|max:10240', // 10MB max por archivo
        ]);

        $userId = optional($request->user())->id;
        $containerName = 'training-and-ms';
        $batchId = Str::uuid()->toString();
        $uploadedFiles = $request->file('files');
        $totalFiles = count($uploadedFiles);

        // 2. Guardar todos los archivos y crear array de jobs
        $jobs = [];
        $fileNames = [];

        foreach ($uploadedFiles as $index => $file) {
            $originalName = $file->getClientOriginalName();

            // Ignorar duplicados por nombre
            if (in_array($originalName, $fileNames)) {
                continue;
            }
            $fileNames[] = $originalName;

            // Guardar con nombre único (UUID)
            $uniqueName = Str::uuid().'_'.$originalName;
            $path = $file->storeAs('evaluations', $uniqueName, 'public');
            $fullPath = storage_path('app/public/'.$path);

            // Crear job con metadata del lote
            $jobs[] = new ProcessPaperEvaluation(
                $fullPath,
                $containerName,
                $userId,
                $batchId,
                $index + 1,
                $totalFiles,
                $originalName
            );
        }

        // 3. Despachar cada job independientemente (si uno falla, los demás continúan)
        foreach ($jobs as $job) {
            dispatch($job);
        }

        // 4. Retornar a la misma página con datos del lote para tracking
        return back()->with('batch', [
            'batchId' => $batchId,
            'totalFiles' => count($jobs),
            'fileNames' => $fileNames,
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
