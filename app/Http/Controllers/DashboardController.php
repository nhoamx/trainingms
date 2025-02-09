<?php

namespace App\Http\Controllers;

use App\Models\Domain;
use App\Models\Organization;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        return Inertia::render('Dashboard');
    }

    public function uploadFiles(Request $request)
    {
        try {
            $fileName = $request->file->getClientOriginalName();
            $folioId = $request->folio_id;
            $organizationId = $request->organization_id;

            $request->file->storeAs('public/evaluations', $fileName);

            return response()->json(['message' => 'Archivo subido correctamente']);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function evaluationResults()
    {
        return Inertia::render('Evaluations/Results', [
            'title' => 'Resultados',
            'organizations' => Organization::all()
        ]);
    }
}
