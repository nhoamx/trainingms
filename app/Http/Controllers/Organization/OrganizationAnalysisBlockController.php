<?php

namespace App\Http\Controllers\Organization;

use App\Http\Controllers\Controller;
use App\Http\Requests\Organization\StoreOrganizationAnalysisBlockRequest;
use App\Models\Organization;
use App\Models\OrganizationAnalysisBlock;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;

class OrganizationAnalysisBlockController extends Controller
{
    use AuthorizesRequests;

    public function store(StoreOrganizationAnalysisBlockRequest $request, Organization $organization): RedirectResponse
    {
        $this->authorize('viewOrganizationDashboard', $organization);

        $validated = $request->validated();

        $organization->analysisBlocks()->create([
            ...$validated,
            'content_html' => $this->sanitizeHtml($validated['content_html']),
            'sort_order' => $validated['sort_order'] ?? 0,
            'updated_by' => $request->user()?->id,
        ]);

        return back()->with('flash', [
            'type' => 'success',
            'title' => 'Éxito',
            'message' => 'Bloque de análisis guardado correctamente.',
        ]);
    }

    public function update(
        StoreOrganizationAnalysisBlockRequest $request,
        Organization $organization,
        OrganizationAnalysisBlock $analysisBlock
    ): RedirectResponse {
        $this->authorize('viewOrganizationDashboard', $organization);

        if ($analysisBlock->organization_id !== $organization->id) {
            abort(403, 'No autorizado para editar este bloque.');
        }

        $validated = $request->validated();

        $analysisBlock->update([
            ...$validated,
            'content_html' => $this->sanitizeHtml($validated['content_html']),
            'sort_order' => $validated['sort_order'] ?? 0,
            'updated_by' => $request->user()?->id,
        ]);

        return back()->with('flash', [
            'type' => 'success',
            'title' => 'Éxito',
            'message' => 'Bloque de análisis actualizado correctamente.',
        ]);
    }

    public function destroy(Organization $organization, OrganizationAnalysisBlock $analysisBlock): RedirectResponse
    {
        $this->authorize('viewOrganizationDashboard', $organization);

        if (! request()->user()?->hasRole(['admin', 'super-admin'])) {
            abort(403, 'No autorizado.');
        }

        if ($analysisBlock->organization_id !== $organization->id) {
            abort(403, 'No autorizado para eliminar este bloque.');
        }

        $analysisBlock->delete();

        return back()->with('flash', [
            'type' => 'success',
            'title' => 'Éxito',
            'message' => 'Bloque de análisis eliminado correctamente.',
        ]);
    }

    protected function sanitizeHtml(string $html): string
    {
        $withoutScripts = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $html);

        return trim((string) $withoutScripts);
    }
}
