<?php

namespace App\Http\Controllers;

use App\Enums\WorkCenterType;
use App\Exports\WorkCentersExport;
use App\Imports\WorkCentersImport;
use App\Models\Organization;
use App\Models\WorkCenter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;

class WorkCenterController extends Controller
{
    /**
     * Display a listing of work centers for an organization
     */
    public function index(Organization $organization): Response
    {
        $workCenters = $organization->workCenters()
            ->withCount(['quizzes', 'paperEvaluations'])
            ->orderByDesc('is_primary')
            ->orderBy('name')
            ->get();

        return Inertia::render('WorkCenters/Index', [
            'title' => 'Centros de Trabajo - '.$organization->name,
            'organization' => $organization,
            'workCenters' => $workCenters,
        ]);
    }

    /**
     * Show the form for creating a new work center
     */
    public function create(Organization $organization): Response
    {
        return Inertia::render('WorkCenters/Create', [
            'title' => 'Nuevo Centro de Trabajo',
            'organization' => $organization->only(['id', 'name']),
        ]);
    }

    /**
     * Store a newly created work center
     */
    public function store(Request $request, Organization $organization): RedirectResponse
    {
        $validated = $request->validate([
            'code' => 'required|string|size:4',
            'name' => 'required|string|max:255',
            'type' => ['required', Rule::in(WorkCenterType::values())],
            'is_primary' => 'boolean',
            'legal_name' => 'nullable|string|max:255',
            'tax_id' => 'nullable|string|max:13',
            'employer_registration' => 'nullable|string|max:255',
            'street_address' => 'nullable|string|max:255',
            'neighborhood' => 'nullable|string|max:255',
            'postal_code' => 'nullable|string|max:10',
            'municipality' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
        ]);

        // Verify code uniqueness within organization
        if ($organization->workCenters()->where('code', $validated['code'])->exists()) {
            return back()->withErrors(['code' => 'El código ya existe para esta organización.']);
        }

        $workCenter = $organization->workCenters()->create($validated);

        return redirect()
            ->route('organizations.work-centers.index', $organization)
            ->with('flash', [
                'type' => 'success',
                'title' => 'Centro de trabajo creado',
                'message' => "Centro '{$workCenter->name}' creado exitosamente.",
            ]);
    }

    /**
     * Show the form for editing a work center
     */
    public function edit(Organization $organization, WorkCenter $workCenter): Response
    {
        // Ensure work center belongs to organization
        if ($workCenter->organization_id !== $organization->id) {
            abort(403, 'No autorizado.');
        }

        $workCenter->loadCount(['quizzes', 'paperEvaluations']);

        return Inertia::render('WorkCenters/Edit', [
            'title' => 'Editar Centro de Trabajo',
            'organization' => $organization->only(['id', 'name']),
            'workCenter' => $workCenter,
        ]);
    }

    /**
     * Update the specified work center
     */
    public function update(Request $request, Organization $organization, WorkCenter $workCenter): RedirectResponse
    {
        // Ensure work center belongs to organization
        if ($workCenter->organization_id !== $organization->id) {
            abort(403, 'No autorizado.');
        }

        $validated = $request->validate([
            'code' => 'required|string|size:4',
            'name' => 'required|string|max:255',
            'type' => ['required', Rule::in(WorkCenterType::values())],
            'is_primary' => 'boolean',
            'legal_name' => 'nullable|string|max:255',
            'tax_id' => 'nullable|string|max:13',
            'employer_registration' => 'nullable|string|max:255',
            'street_address' => 'nullable|string|max:255',
            'neighborhood' => 'nullable|string|max:255',
            'postal_code' => 'nullable|string|max:10',
            'municipality' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
        ]);

        // Verify code uniqueness (excluding current work center)
        if ($organization->workCenters()
            ->where('code', $validated['code'])
            ->where('id', '!=', $workCenter->id)
            ->exists()) {
            return back()->withErrors(['code' => 'El código ya existe para esta organización.']);
        }

        $workCenter->update($validated);

        return redirect()
            ->route('organizations.work-centers.index', $organization)
            ->with('flash', [
                'type' => 'success',
                'title' => 'Centro actualizado',
                'message' => "Centro '{$workCenter->name}' actualizado exitosamente.",
            ]);
    }

    /**
     * Remove (soft delete) the specified work center
     */
    public function destroy(Organization $organization, WorkCenter $workCenter): RedirectResponse
    {
        // Ensure work center belongs to organization
        if ($workCenter->organization_id !== $organization->id) {
            abort(403, 'No autorizado.');
        }

        // Prevent deletion of primary work center
        if ($workCenter->is_primary) {
            return back()->with('flash', [
                'type' => 'error',
                'title' => 'No se puede eliminar',
                'message' => 'No se puede eliminar el centro de trabajo primario.',
            ]);
        }

        $workCenter->delete();

        return redirect()
            ->route('organizations.work-centers.index', $organization)
            ->with('flash', [
                'type' => 'info',
                'title' => 'Centro eliminado',
                'message' => "Centro '{$workCenter->name}' eliminado exitosamente.",
            ]);
    }

    /**
     * Descargar plantilla Excel con los centros de trabajo actuales
     */
    public function downloadTemplate(Organization $organization)
    {
        $filename = 'centros_trabajo_'.$organization->folio_organization.'_'.now()->format('Y-m-d').'.xlsx';

        return Excel::download(new WorkCentersExport($organization), $filename);
    }

    /**
     * Importar centros de trabajo desde archivo Excel
     */
    public function import(Request $request, Organization $organization)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls|max:10240', // Max 10MB
        ]);

        try {
            $import = new WorkCentersImport($organization);

            Excel::import($import, $request->file('file'));

            $summary = $import->getSummary();

            $message = sprintf(
                'Importación completada: %d creados, %d actualizados, %d omitidos.',
                $summary['created'],
                $summary['updated'],
                $summary['skipped']
            );

            if (! empty($summary['errors'])) {
                $message .= ' Errores: '.implode(', ', array_slice($summary['errors'], 0, 3));
                if (count($summary['errors']) > 3) {
                    $message .= '...';
                }
            }

            return back()->with([
                'flash' => [
                    'type' => empty($summary['errors']) ? 'success' : 'warning',
                    'title' => 'Importación de Centros de Trabajo',
                    'message' => $message,
                ],
            ]);
        } catch (\Exception $e) {
            return back()->with([
                'flash' => [
                    'type' => 'error',
                    'title' => 'Error en la importación',
                    'message' => 'No se pudo procesar el archivo: '.$e->getMessage(),
                ],
            ]);
        }
    }

    /**
     * Display work centers assigned to the authenticated user
     */
    public function myWorkCenters(): Response
    {
        $user = auth()->user();

        $workCenters = $user->workCenters()
            ->with('organization:id,name')
            ->select('work_centers.*')
            ->get()
            ->map(function ($workCenter) {
                return [
                    'id' => $workCenter->id,
                    'code' => $workCenter->code,
                    'name' => $workCenter->name,
                    'work_center_type' => $workCenter->work_center_type,
                    'is_primary' => $workCenter->is_primary,
                    'organization_id' => $workCenter->organization_id,
                    'organization_name' => $workCenter->organization->name ?? 'N/A',
                ];
            });

        return Inertia::render('WorkCenters/MyWorkCenters', [
            'workCenters' => $workCenters,
        ]);
    }
}
