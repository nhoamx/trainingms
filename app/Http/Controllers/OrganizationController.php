<?php

namespace App\Http\Controllers;

use App\Events\EvaluationProcessingStatusChanged;
use App\Models\Organization;
use Illuminate\Http\Request;
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

    public function store(Request $request)
    {
        // Validaciones del request
        $validatedData = $request->validate([
            'name'  => 'required|string|max:255',
            'logo'  => 'nullable|image|mimes:jpeg,png,gif|max:10240', // 10MB
            'folio_organization' => 'nullable|numeric',
        ]);

        // Creamos la nueva organización
        $organization = new Organization();
        $organization->name = $validatedData['name'];
        $organization->folio_organization = $validatedData['folio_organization'] ?? rand(100, 999);

        // Si se envía un archivo para el logo, lo almacenamos
        // En este ejemplo, se da prioridad a "logo" y, en caso de que no se envíe, se usa "image"
        if ($request->hasFile('logo')) {
            $logoPath = $request->file('logo')->store('organizations', 'public');
            $organization->logo = $logoPath;
        }

        $organization->save();

        // Redirigimos a organizations.index y enviamos un mensaje flash de sesión
        return redirect()->route('organizations.index')
            ->with('success', 'Organización creada exitosamente.');
    }

    public function edit(Organization $organization)
    {
        return Inertia::render('Organizations/Edit', [
            'title' => 'Editar organización',
            'organization' => $organization,
        ]);
    }

    public function update(Request $request, Organization $organization)
    {
        // Validaciones del request
        $request->validate([
            'name'  => 'required|string|max:255',
            'logo'  => 'nullable|image|mimes:jpeg,png,gif|max:10240', // 10MB
            'folio_organization' => 'nullable|numeric',
        ]);

        $organization->name = $request->name;
        $organization->folio_organization = $request->folio_organization;

        if ($request->hasFile('logo')) {
            // Si existe un logo anterior, lo eliminamos del disco
            if ($organization->logo && Storage::disk('public')->exists($organization->logo)) {
                Storage::disk('public')->delete($organization->logo);
            }

            // Almacenamos el nuevo logo
            $logoPath = $request->file('logo')->store('organizations', 'public');
            $organization->logo = $logoPath;
        }

        $organization->save();

        return redirect()->route('organizations.edit', $organization)
            ->with('flash', [
                'type' => 'success',
                'title' => 'Organización actualizada exitosamente.',
                'message' => 'Los datos de la organización han sido actualizados correctamente.'
            ]);

    }

    public function destroy(Organization $organization)
    {
        if (!$organization->trashed()) {
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
                'message' => $message
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
                'message' => $message
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
}
