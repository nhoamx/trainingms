<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessEvaluation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class EvaluationController extends Controller
{

    public function index()
    {
        return Inertia::render('Evaluations/Results', [
            'title' => 'Evaluaciones',
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

        return redirect()->back()->with('success', 'El proceso se ha iniciado en segundo plano.');
    }

}
