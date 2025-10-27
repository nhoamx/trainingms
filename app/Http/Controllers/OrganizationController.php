<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOrganizationRequest;
use App\Http\Requests\UpdateOrganizationRequest;
use App\Models\Organization;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class OrganizationController extends Controller
{
    public function index()
    {
        $organizations = Organization::all();

        return Inertia::render('Organizations/Index', [
            'title' => 'Organizaciones',
            'organizations' => Organization::withTrashed()->get(),
        ]);
    }

    public function create()
    {
        return Inertia::render('Organizations/Create', [
            'title' => 'Crear organización',
        ]);
    }

    public function store(StoreOrganizationRequest $request)
    {
        $data = $request->validated();

        // Generar folio si no se proporcionó
        if (empty($data['folio_organization'])) {
            $data['folio_organization'] = rand(100, 999);
        }

        // Manejar logo aparte del fill
        $logoFile = $data['logo'] ?? null;
        unset($data['logo']);

        $organization = new Organization;
        $organization->fill($data);

        if ($logoFile) {
            $logoPath = $logoFile->store('organizations', 'public');
            $organization->logo = $logoPath;
        }

        $organization->save();

        // Redirigimos a organizations.index y enviamos un mensaje flash de sesión
        return redirect()->route('organizations.index')
            ->with('success', 'Organización creada exitosamente.');
    }

    public function edit(Organization $organization)
    {
        // Cargar relaciones de puestos y departamentos
        $organization->load([
            'occupationPositions',
            'departmentAreas',
            'folioBatches' => function ($query) {
                $query->withCount([
                    'folios as used_count' => function ($q) {
                        $q->where('used', true);
                    },
                ]);
            },
        ]);

        return Inertia::render('Organizations/Edit', [
            'title' => 'Editar organización',
            'organization' => $organization,
        ]);
    }

    public function update(UpdateOrganizationRequest $request, Organization $organization)
    {
        $data = $request->validated();

        $logoFile = $data['logo'] ?? null;
        unset($data['logo']);

        $organization->fill($data);

        if ($logoFile) {
            if ($organization->logo && Storage::disk('public')->exists($organization->logo)) {
                Storage::disk('public')->delete($organization->logo);
            }
            $logoPath = $logoFile->store('organizations', 'public');
            $organization->logo = $logoPath;
        }

        $organization->save();

        return redirect()->route('organizations.edit', $organization)
            ->with('flash', [
                'type' => 'success',
                'title' => 'Organización actualizada exitosamente.',
                'message' => 'Los datos de la organización han sido actualizados correctamente.',
            ]);

    }

    public function destroy(Organization $organization)
    {
        if (! $organization->trashed()) {
            $organization->delete();
            $title = 'Organización Deshabilitada';
            $message = 'Organización deshabilitada exitosamente.';
        } else {
            $organization->restore();
            $title = 'Organización activa nuevamente';
            $message = 'Organización activada exitosamente.';
        }

        return redirect()->route('organizations.index')
            ->with('flash', [
                'type' => 'info',
                'title' => $title,
                'message' => $message,
            ]);
    }

    public function restore(Organization $organization)
    {
        $organization->restore();
        $title = 'Organización activa nuevamente';
        $message = 'Organización activada exitosamente.';

        return redirect()->route('organizations.index')
            ->with('flash', [
                'type' => 'info',
                'title' => $title,
                'message' => $message,
            ]);
    }

    // Method to provide a list of organizations for dropdowns
    public function listForDropdown()
    {
        $organizations = Organization::select('id', 'name')
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get();

        return response()->json($organizations);
    }

    // metodo para eliminar completamente una organización
    public function forceDelete(Organization $organization)
    {
        // Eliminar la organización de forma permanente
        $organization->forceDelete();

        // Redirigir a la lista de organizaciones con un mensaje de éxito
        return redirect()->route('organizations.index')
            ->with('flash', [
                'type' => 'success',
                'title' => 'Organización eliminada permanentemente',
                'message' => 'La organización ha sido eliminada permanentemente.',
            ]);
    }
}
