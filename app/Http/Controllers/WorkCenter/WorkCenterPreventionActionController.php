<?php

namespace App\Http\Controllers\WorkCenter;

use App\Http\Controllers\Controller;
use App\Http\Requests\WorkCenter\StoreWorkCenterPreventionActionRequest;
use App\Models\WorkCenter;
use App\Models\WorkCenterPreventionAction;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;

class WorkCenterPreventionActionController extends Controller
{
    use AuthorizesRequests;

    public function store(StoreWorkCenterPreventionActionRequest $request, WorkCenter $workCenter): RedirectResponse
    {
        $this->authorize('viewWorkCenterDashboard', $workCenter);

        $workCenter->preventionActions()->create([
            ...$request->validated(),
            'sort_order' => $request->validated()['sort_order'] ?? 0,
            'updated_by' => $request->user()?->id,
        ]);

        return back()->with('flash', [
            'type' => 'success',
            'title' => 'Éxito',
            'message' => 'Acción preventiva registrada correctamente.',
        ]);
    }

    public function update(StoreWorkCenterPreventionActionRequest $request, WorkCenter $workCenter, WorkCenterPreventionAction $preventionAction): RedirectResponse
    {
        $this->authorize('viewWorkCenterDashboard', $workCenter);

        if ($preventionAction->work_center_id !== $workCenter->id) {
            abort(403, 'No autorizado para editar esta acción.');
        }

        $preventionAction->update([
            ...$request->validated(),
            'sort_order' => $request->validated()['sort_order'] ?? 0,
            'updated_by' => $request->user()?->id,
        ]);

        return back()->with('flash', [
            'type' => 'success',
            'title' => 'Éxito',
            'message' => 'Acción preventiva actualizada correctamente.',
        ]);
    }

    public function destroy(WorkCenter $workCenter, WorkCenterPreventionAction $preventionAction): RedirectResponse
    {
        $this->authorize('viewWorkCenterDashboard', $workCenter);

        if (! request()->user()?->hasRole(['admin', 'super-admin'])) {
            abort(403, 'No autorizado.');
        }

        if ($preventionAction->work_center_id !== $workCenter->id) {
            abort(403, 'No autorizado para eliminar esta acción.');
        }

        $preventionAction->delete();

        return back()->with('flash', [
            'type' => 'success',
            'title' => 'Éxito',
            'message' => 'Acción preventiva eliminada correctamente.',
        ]);
    }
}
