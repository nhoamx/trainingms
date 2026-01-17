<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateCompanyDataRequest;
use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class CompanyDataController extends Controller
{
    /**
     * Show the company data edit form.
     */
    public function edit(Organization $organization)
    {
        // Authorization check
        $user = auth()->user();
        if (! $user->hasRole(['admin', 'super-admin']) && $user->organization_id !== $organization->id) {
            abort(403, 'No autorizado para acceder a esta organización.');
        }

        return Inertia::render('Organizations/CompanyData', [
            'organization' => $organization->only([
                'id',
                'name',
                'razon_social',
                'rfc',
                'registro_patronal',
                'actividad_principal',
                'fecha_aplicacion',
                'calle_numero',
                'colonia',
                'codigo_postal',
                'municipio',
                'estado',
                'total_trabajadores',
                'total_hombres',
                'total_mujeres',
                'muestra_aplicada',
                'muestra_hombres',
                'muestra_mujeres',
                'justificacion_muestra',
                'contacto_nombre',
                'contacto_puesto',
                'contacto_email',
                'contacto_movil',
                'responsable_nombre',
                'responsable_puesto',
                'responsable_email',
                'responsable_movil',
                'comite_integrantes',
                'comite_hombres',
                'comite_mujeres',
                'policy_draft_path',
                'policy_approved_path',
                'policy_approved_at',
            ]),
        ]);
    }

    /**
     * Update the company data.
     */
    public function update(UpdateCompanyDataRequest $request, Organization $organization)
    {
        $organization->update($request->validated());

        return redirect()
            ->route('company-data.edit', $organization)
            ->with('flash', [
                'type' => 'success',
                'title' => 'Éxito',
                'message' => 'Los datos de la empresa se actualizaron correctamente.',
            ]);
    }

    /**
     * Upload draft policy document.
     */
    public function uploadPolicyDraft(Request $request, Organization $organization)
    {
        // Authorization check
        $user = auth()->user();
        if (! $user->hasRole(['admin', 'super-admin']) && $user->organization_id !== $organization->id) {
            abort(403, 'No autorizado para acceder a esta organización.');
        }

        $request->validate([
            'policy_draft' => ['required', 'file', 'mimes:pdf,doc,docx', 'max:10240'], // 10MB max
        ]);

        // Delete old draft if exists
        if ($organization->policy_draft_path) {
            Storage::disk('public')->delete($organization->policy_draft_path);
        }

        // Store new draft
        $path = $request->file('policy_draft')->store("policies/{$organization->id}/drafts", 'public');

        $organization->update([
            'policy_draft_path' => $path,
        ]);

        return back()->with('flash', [
            'type' => 'success',
            'title' => 'Éxito',
            'message' => 'El borrador de la política se subió correctamente.',
        ]);
    }

    /**
     * Upload approved policy document (admin only).
     */
    public function uploadPolicyApproved(Request $request, Organization $organization)
    {
        // Only admins can approve policies
        if (! auth()->user()->hasRole(['admin', 'super-admin'])) {
            abort(403, 'Solo los administradores pueden aprobar políticas.');
        }

        $request->validate([
            'policy_approved' => ['required', 'file', 'mimes:pdf,doc,docx', 'max:10240'], // 10MB max
        ]);

        // Delete old approved if exists
        if ($organization->policy_approved_path) {
            Storage::disk('public')->delete($organization->policy_approved_path);
        }

        // Store new approved policy
        $path = $request->file('policy_approved')->store("policies/{$organization->id}/approved", 'public');

        $organization->update([
            'policy_approved_path' => $path,
            'policy_approved_at' => now(),
        ]);

        return back()->with('flash', [
            'type' => 'success',
            'title' => 'Éxito',
            'message' => 'La política aprobada se subió correctamente.',
        ]);
    }

    /**
     * Download draft policy.
     */
    public function downloadPolicyDraft(Organization $organization)
    {
        // Authorization check
        $user = auth()->user();
        if (! $user->hasRole(['admin', 'super-admin']) && $user->organization_id !== $organization->id) {
            abort(403, 'No autorizado para acceder a esta organización.');
        }

        if (! $organization->policy_draft_path) {
            abort(404, 'No hay borrador de política disponible.');
        }

        return Storage::disk('public')->download($organization->policy_draft_path);
    }

    /**
     * Download approved policy.
     */
    public function downloadPolicyApproved(Organization $organization)
    {
        // Authorization check
        $user = auth()->user();
        if (! $user->hasRole(['admin', 'super-admin']) && $user->organization_id !== $organization->id) {
            abort(403, 'No autorizado para acceder a esta organización.');
        }

        if (! $organization->policy_approved_path) {
            abort(404, 'No hay política aprobada disponible.');
        }

        return Storage::disk('public')->download($organization->policy_approved_path);
    }
}
