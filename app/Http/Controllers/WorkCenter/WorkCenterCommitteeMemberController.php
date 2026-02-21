<?php

namespace App\Http\Controllers\WorkCenter;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreWorkCenterCommitteeMemberRequest;
use App\Models\WorkCenter;
use App\Models\WorkCenterCommitteeMember;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;

class WorkCenterCommitteeMemberController extends Controller
{
    use AuthorizesRequests;

    /**
     * Store a newly created committee member for a work center.
     */
    public function store(StoreWorkCenterCommitteeMemberRequest $request, WorkCenter $workCenter): RedirectResponse
    {
        $workCenter->committeeMembers()->create($request->validated());

        return back()->with('flash', [
            'type' => 'success',
            'title' => 'Éxito',
            'message' => 'Miembro del comité agregado correctamente.',
        ]);
    }

    /**
     * Remove the specified committee member from a work center.
     */
    public function destroy(WorkCenter $workCenter, WorkCenterCommitteeMember $committeeMember): RedirectResponse
    {
        if (! request()->user()?->hasRole(['admin', 'super-admin'])) {
            abort(403, 'No autorizado.');
        }

        $this->authorize('viewWorkCenterDashboard', $workCenter);

        if ($committeeMember->work_center_id !== $workCenter->id) {
            abort(403, 'No autorizado.');
        }

        $committeeMember->delete();

        return back()->with('flash', [
            'type' => 'success',
            'title' => 'Éxito',
            'message' => 'Miembro del comité eliminado correctamente.',
        ]);
    }
}
