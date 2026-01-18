<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCommitteeMemberRequest;
use App\Models\CommitteeMember;
use App\Models\Organization;

class CommitteeMemberController extends Controller
{
    /**
     * Store a newly created committee member.
     */
    public function store(StoreCommitteeMemberRequest $request, Organization $organization)
    {
        $member = $organization->committeeMembers()->create($request->validated());

        return back()->with('flash', [
            'type' => 'success',
            'title' => 'Éxito',
            'message' => 'Miembro del comité agregado correctamente.',
        ]);
    }

    /**
     * Remove the specified committee member.
     */
    public function destroy(Organization $organization, CommitteeMember $committeeMember)
    {
        // Ensure the member belongs to the organization
        if ($committeeMember->organization_id !== $organization->id) {
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
