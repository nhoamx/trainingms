<?php

namespace App\Http\Controllers;

use App\Enums\WorkCenterType;
use App\Models\Organization;
use App\Models\WorkCenter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

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
}
