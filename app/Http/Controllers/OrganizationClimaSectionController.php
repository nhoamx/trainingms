<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpsertOrganizationClimaSectionRequest;
use App\Models\Organization;
use App\Models\OrganizationClimaSection;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;

class OrganizationClimaSectionController extends Controller
{
    use AuthorizesRequests;

    public function upsert(UpsertOrganizationClimaSectionRequest $request, Organization $organization): RedirectResponse
    {
        $this->authorize('viewOrganizationDashboard', $organization);

        $validated = $request->validated();

        OrganizationClimaSection::query()->updateOrCreate(
            [
                'organization_id' => $organization->id,
                'section_key' => $validated['section_key'],
            ],
            [
                'content' => $validated['content'] ?? null,
                'status' => $validated['status'],
                'updated_by' => $request->user()?->id,
                'published_at' => $validated['status'] === OrganizationClimaSection::STATUS_PUBLISHED ? now() : null,
            ]
        );

        return back()->with('flash', [
            'type' => 'success',
            'title' => 'Éxito',
            'message' => 'La sección fue actualizada correctamente.',
        ]);
    }
}
