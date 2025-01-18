<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        return Inertia::render('Dashboard');
    }

    public function evaluations()
    {
        return Inertia::render('Evaluations/Index', [
            'title' => 'Evaluaciones',
        ]);
    }

    public function uploadFiles(Request $request)
    {
        // Validar los archivos
        $request->validate([
            'files.*' => 'required|file|mimes:jpg,jpeg,png,pdf|max:2048', // Máximo 2MB por archivo
        ]);

        $uploadedFiles = [];

        // Almacenar los archivos
        if ($request->hasFile('files')) {
            foreach ($request->file('files') as $file) {
                $path = $file->store('uploads', 'public'); // Guarda en storage/app/public/uploads
                $uploadedFiles[] = $path;
            }
        }

        return response()->json([
            'message' => 'Archivos subidos con éxito.',
            'files' => $uploadedFiles, // Devuelve las rutas de los archivos subidos
        ]);
    }

    public function evaluationResults()
    {
        return Inertia::render('Evaluations/Results', [
            'title' => 'Resultados de evaluaciones',
        ]);
    }
}
