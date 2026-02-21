<?php

namespace App\Http\Controllers\WorkCenter;

use App\Http\Controllers\Controller;
use App\Models\WorkCenter;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class WorkCenterConstitutiveActController extends Controller
{
    use AuthorizesRequests;

    public function uploadSubmitted(Request $request, WorkCenter $workCenter): RedirectResponse
    {
        $this->authorize('viewWorkCenterDashboard', $workCenter);

        if (! $request->user()?->hasRole('work_center_user') || $request->user()?->hasRole(['admin', 'super-admin'])) {
            abort(403, 'No autorizado.');
        }

        $validated = $request->validate([
            'constitutive_act_submitted' => ['required', 'file', 'mimes:pdf,doc,docx', 'max:10240'],
        ]);

        if ($workCenter->constitutive_act_submitted_path) {
            Storage::disk('public')->delete($workCenter->constitutive_act_submitted_path);
        }

        $path = $validated['constitutive_act_submitted']->store("work-centers/{$workCenter->id}/constitutive-act/submitted", 'public');

        $workCenter->update([
            'constitutive_act_submitted_path' => $path,
            'constitutive_act_submitted_at' => now(),
        ]);

        return back()->with('flash', [
            'type' => 'success',
            'title' => 'Éxito',
            'message' => 'El acta constitutiva fue cargada correctamente.',
        ]);
    }

    public function uploadAdmin(Request $request, WorkCenter $workCenter): RedirectResponse
    {
        $this->authorize('viewWorkCenterDashboard', $workCenter);

        if (! $request->user()?->hasRole(['admin', 'super-admin'])) {
            abort(403, 'No autorizado.');
        }

        $validated = $request->validate([
            'constitutive_act_admin' => ['required', 'file', 'mimes:pdf,doc,docx', 'max:10240'],
        ]);

        if ($workCenter->constitutive_act_admin_path) {
            Storage::disk('public')->delete($workCenter->constitutive_act_admin_path);
        }

        $path = $validated['constitutive_act_admin']->store("work-centers/{$workCenter->id}/constitutive-act/admin", 'public');

        $workCenter->update([
            'constitutive_act_admin_path' => $path,
            'constitutive_act_admin_at' => now(),
        ]);

        return back()->with('flash', [
            'type' => 'success',
            'title' => 'Éxito',
            'message' => 'La versión administrativa del acta fue cargada correctamente.',
        ]);
    }

    public function downloadSubmitted(WorkCenter $workCenter): BinaryFileResponse
    {
        $this->authorize('viewWorkCenterDashboard', $workCenter);

        if (! $workCenter->constitutive_act_submitted_path) {
            abort(404, 'No hay acta constitutiva cargada por el centro de trabajo.');
        }

        return response()->download(Storage::disk('public')->path($workCenter->constitutive_act_submitted_path));
    }

    public function downloadAdmin(WorkCenter $workCenter): BinaryFileResponse
    {
        $this->authorize('viewWorkCenterDashboard', $workCenter);

        if (! $workCenter->constitutive_act_admin_path) {
            abort(404, 'No hay acta constitutiva administrativa disponible.');
        }

        return response()->download(Storage::disk('public')->path($workCenter->constitutive_act_admin_path));
    }
}
