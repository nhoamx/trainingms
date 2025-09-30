<?php

namespace App\Http\Controllers;

use App\Models\DepartmentArea;
use App\Models\Organization;
use App\Services\DepartmentAreaService;
use Illuminate\Http\Request;

class DepartmentAreaController extends Controller
{
    protected $departmentService;

    public function __construct(DepartmentAreaService $departmentService)
    {
        $this->departmentService = $departmentService;
    }

    /**
     * Almacena un nuevo departamento en la base de datos.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        // Validar la solicitud
        $validated = $request->validate([
            'organization_id' => 'required|uuid|exists:organizations,id',
            'name' => 'required|string|max:255',
        ]);

        // Buscar la organización
        $organization = Organization::findOrFail($validated['organization_id']);

        // Crear el departamento usando nuestro servicio
        $area = $this->departmentService->createArea(
            $organization,
            $validated['name']
        );

        // Redireccionar con un mensaje flash de éxito y devolver el área creada
        return back()->with([
            'flash' => [
                'type' => 'success',
                'title' => 'Departamento creado',
                'message' => 'El departamento ha sido creado exitosamente.',
            ],
            'area' => $area, // Devolver el área creada para actualización reactiva
        ]);
    }

    /**
     * Elimina un departamento específico.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(DepartmentArea $departmentArea)
    {
        // Verificar si existe y eliminarlo
        $departmentArea->delete();

        // Redireccionar con un mensaje flash de éxito
        return back()->with([
            'flash' => [
                'type' => 'success',
                'title' => 'Departamento eliminado',
                'message' => 'El departamento ha sido eliminado exitosamente.',
            ],
        ]);
    }
}
