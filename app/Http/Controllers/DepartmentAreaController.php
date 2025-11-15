<?php

namespace App\Http\Controllers;

use App\Exports\DepartmentAreasExport;
use App\Imports\DepartmentAreasImport;
use App\Models\DepartmentArea;
use App\Models\Organization;
use App\Services\DepartmentAreaService;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

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

    /**
     * Descargar plantilla Excel con los departamentos actuales
     */
    public function downloadTemplate(Organization $organization)
    {
        $filename = 'departamentos_'.$organization->folio_organization.'_'.now()->format('Y-m-d').'.xlsx';

        return Excel::download(new DepartmentAreasExport($organization), $filename);
    }

    /**
     * Importar departamentos desde archivo Excel
     */
    public function import(Request $request, Organization $organization)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls|max:10240', // Max 10MB
        ]);

        try {
            $import = new DepartmentAreasImport($organization, $this->departmentService);

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
                    'title' => 'Importación de Departamentos',
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
}
