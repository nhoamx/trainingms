<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOrganizationRequest;
use App\Http\Requests\UpdateOrganizationRequest;
use App\Models\Organization;
use App\Services\OrganizationAddressService;
use App\Services\OrganizationService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class OrganizationController extends Controller
{
    public function __construct(
        protected OrganizationService $organizationService
    ) {}

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
        $orgData = $request->validated();
        $logoFile = $request->hasFile('logo') ? $request->file('logo') : null;

        // Extract work center data from wizard (Paso 2) with wc_ prefix
        $workCenterData = $request->only([
            'wc_name', 'wc_type', 'wc_legal_name', 'wc_tax_id', 'wc_employer_registration',
            'wc_street_address', 'wc_neighborhood', 'wc_postal_code', 'wc_municipality', 'wc_state',
        ]);

        // Remove wc_ prefix from keys
        $workCenterData = collect($workCenterData)
            ->mapWithKeys(fn ($value, $key) => [str_replace('wc_', '', $key) => $value])
            ->filter(fn ($value) => $value !== null)
            ->toArray();

        // Create organization with work center from wizard
        $organization = $this->organizationService->createWithWorkCenter($orgData, $workCenterData, $logoFile);

        return redirect()->route('organizations.edit', $organization)
            ->with('flash', [
                'type' => 'success',
                'title' => 'Organización creada exitosamente',
                'message' => "Se creó el corporativo '{$organization->name}' y su centro principal.",
            ]);
    }

    public function edit(Organization $organization)
    {
        // Cargar relaciones de puestos, departamentos, work centers
        $organization->load([
            'occupationPositions',
            'departmentAreas',
            'workCenters' => function ($query) {
                $query->orderBy('is_primary', 'desc')->orderBy('code');
            },
            'addresses' => function ($query) {
                $query->orderBy('is_primary', 'desc')->orderBy('type');
            },
            'folioBatches' => function ($query) {
                $query->with('workCenter:id,code,name')
                    ->withCount([
                        'folios as used_count' => function ($q) {
                            $q->where('used', true);
                        },
                    ]);
            },
        ]);

        return Inertia::render('Organizations/Edit', [
            'title' => 'Editar organización',
            'organization' => $organization,
            'workCenterTypes' => array_map(
                fn ($type) => [
                    'value' => $type->value,
                    'label' => $type->label(),
                ],
                \App\Enums\WorkCenterType::cases()
            ),
        ]);
    }

    public function update(UpdateOrganizationRequest $request, Organization $organization)
    {
        $data = $request->validated();
        $logoFile = $data['logo'] ?? null;

        // Update only organization corporate data
        $organization = $this->organizationService->updateOrganization($organization, $data, $logoFile);

        return redirect()->route('organizations.edit', $organization)
            ->with('flash', [
                'type' => 'success',
                'title' => 'Organización actualizada exitosamente.',
                'message' => 'Los datos corporativos de la organización han sido actualizados.',
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

    /**
     * Export Likert answers report to Excel
     */
    public function exportLikertAnswers(Organization $organization): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $filename = 'respuestas_likert_'.str_replace(' ', '_', $organization->name).'_'.now()->format('Y-m-d').'.xlsx';

        return Excel::download(
            new \App\Exports\LikertAnswersExport($organization->id, $organization->name),
            $filename
        );
    }

    /**
     * Descargar template de Excel para actualización masiva de organización y direcciones
     */
    public function downloadBulkTemplate(Organization $organization): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $filename = 'organizacion_direcciones_'.$organization->folio_organization.'_'.now()->format('Y-m-d').'.xlsx';

        return Excel::download(
            new \App\Exports\OrganizationDataExport($organization),
            $filename
        );
    }

    /**
     * Importar datos masivos de organización y direcciones desde Excel
     */
    public function importBulkData(Request $request, Organization $organization)
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls', 'max:10240'],
        ]);

        try {
            \Log::info('Iniciando importación masiva', [
                'organization_id' => $organization->id,
                'organization_name' => $organization->name,
                'file_name' => $request->file('file')->getClientOriginalName(),
                'file_size' => $request->file('file')->getSize(),
            ]);

            $addressService = new OrganizationAddressService;
            $import = new \App\Imports\OrganizationBulkUpdateImport($organization, $addressService);

            Excel::import($import, $request->file('file'));

            $summary = $import->getSummary();

            \Log::info('Importación completada', [
                'organization_id' => $organization->id,
                'summary' => $summary,
            ]);

            $message = sprintf(
                'Organizaciones actualizadas: %d, Direcciones procesadas: %d, Omitidos: %d',
                $summary['updated'],
                $summary['addresses'],
                $summary['skipped']
            );

            $response = back()->with('flash', [
                'type' => empty($summary['errors']) ? 'success' : 'warning',
                'title' => 'Importación de Datos',
                'message' => $message,
            ]);

            // Agregar errores si existen
            if (! empty($summary['errors'])) {
                $response->with('bulk_errors', $summary['errors']);
            }

            return $response;

        } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
            // Errores de validación de Excel
            $failures = $e->failures();
            $errors = [];

            foreach ($failures as $failure) {
                $error = sprintf(
                    'Fila %d, Columna %s: %s',
                    $failure->row(),
                    $failure->attribute(),
                    implode(', ', $failure->errors())
                );
                $errors[] = $error;
            }

            \Log::error('Error de validación en importación', [
                'organization_id' => $organization->id,
                'errors' => $errors,
            ]);

            return back()
                ->with('flash', [
                    'type' => 'error',
                    'title' => 'Error de Validación',
                    'message' => 'Se encontraron errores en el archivo de Excel. Por favor revisa los detalles.',
                ])
                ->with('bulk_errors', $errors);

        } catch (\Exception $e) {
            // Otros errores generales
            \Log::error('Error general en importación', [
                'organization_id' => $organization->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()
                ->with('flash', [
                    'type' => 'error',
                    'title' => 'Error al Procesar el Archivo',
                    'message' => 'Ocurrió un error al procesar el archivo.',
                ])
                ->with('bulk_errors', [$e->getMessage()]);
        }
    }
}
