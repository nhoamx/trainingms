<?php

namespace App\Http\Controllers;

use App\Events\EvaluationProcessingStatusChanged;
use App\Jobs\ProcessEvaluation;
use App\Models\Evaluation;
use App\Models\Organization;
use Illuminate\Http\Request;
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
            'noOrgEvaluations' => $noOrgEvaluations
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
            'file' => 'required|file|mimes:pdf|max:10240', // 10240 KB = 10 MB
        ]);

        // 2. Guardar el archivo en el disco 'public' en la carpeta "evaluations"
        $path = $request->file('file')->store('evaluations', 'public');

        // Obtener el path completo del archivo almacenado
        $fullPath = storage_path('app/public/' . $path);
        Log::info('Archivo almacenado en: ' . $fullPath);

        // Nombre o ID del contenedor (según salida de `docker ps`)
        $containerName = "training-and-ms";

        // 3. Despachar el job que se encargará de copiar el archivo y ejecutar el comando
        ProcessEvaluation::dispatch($fullPath, $containerName);

        // Redirigir a la lista de evaluaciones con un mensaje de éxito
        return redirect()
            ->route('evaluations.index')
            ->with('flash', [
                'type' => 'success',
                'title' => 'Evaluación cargada exitosamente',
                'message' => 'El archivo PDF ha sido cargado y está siendo procesado. Los resultados estarán disponibles en breve.'
            ]);
    }

    public function show(Evaluation $evaluation)
    {
        $evaluation->load(['answers.dimension', 'organization']);

        return Inertia::render('Evaluations/EvaluationDetails', [
            'title' => 'Detalles de Evaluación',
            'evaluation' => $evaluation,
            'answers' => $evaluation->answers
        ]);
    }

    public function organizationEvaluations($organization)
    {
        if ($organization === 'no-org') {
            $evaluations = Evaluation::whereNull('organization_id')->get();
            $organization = ['id' => 'no-org', 'name' => 'Sin Organización'];
        } else {
            $organization = Organization::findOrFail($organization);
            $evaluations = $organization->evaluations()->get();
        }

        return Inertia::render('Evaluations/EvaluationList', [
            'organization' => $organization,
            'evaluations' => $evaluations,
        ]);
    }

}
