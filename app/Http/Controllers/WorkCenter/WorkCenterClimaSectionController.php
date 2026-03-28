<?php

namespace App\Http\Controllers\WorkCenter;

use App\Http\Controllers\Controller;
use App\Http\Requests\WorkCenter\UpsertWorkCenterClimaSectionRequest;
use App\Models\WorkCenter;
use App\Models\WorkCenterClimaSection;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;

class WorkCenterClimaSectionController extends Controller
{
    use AuthorizesRequests;

    public function upsert(UpsertWorkCenterClimaSectionRequest $request, WorkCenter $workCenter): RedirectResponse
    {
        $this->authorize('viewWorkCenterDashboard', $workCenter);

        $validated = $request->validated();

        WorkCenterClimaSection::query()->updateOrCreate(
            [
                'work_center_id' => $workCenter->id,
                'section_key' => $validated['section_key'],
            ],
            [
                'content' => $validated['content'] ?? null,
                'status' => $validated['status'],
                'updated_by' => $request->user()?->id,
                'published_at' => $validated['status'] === WorkCenterClimaSection::STATUS_PUBLISHED ? now() : null,
            ]
        );

        return back()->with('flash', [
            'type' => 'success',
            'title' => 'Éxito',
            'message' => 'La sección fue actualizada correctamente.',
        ]);
    }
}
