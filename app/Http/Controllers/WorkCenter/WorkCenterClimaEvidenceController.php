<?php

namespace App\Http\Controllers\WorkCenter;

use App\Http\Controllers\Controller;
use App\Http\Requests\WorkCenter\StoreWorkCenterClimaEvidenceRequest;
use App\Models\WorkCenter;
use App\Models\WorkCenterClimaEvidence;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class WorkCenterClimaEvidenceController extends Controller
{
    use AuthorizesRequests;

    public function store(StoreWorkCenterClimaEvidenceRequest $request, WorkCenter $workCenter): RedirectResponse
    {
        $this->authorize('viewWorkCenterDashboard', $workCenter);

        $validated = $request->validated();
        $evidenceFile = $request->file('evidence_file');
        $path = $evidenceFile->store("work-centers/{$workCenter->id}/clima/evidences", 'public');

        $workCenter->climaEvidences()->create([
            'title' => $validated['title'] ?? null,
            'description' => $validated['description'] ?? null,
            'storage_path' => $path,
            'original_filename' => $evidenceFile->getClientOriginalName(),
            'mime_type' => $evidenceFile->getMimeType() ?? 'image/jpeg',
            'file_size' => $evidenceFile->getSize(),
            'is_published' => (bool) ($validated['is_published'] ?? false),
            'uploaded_by' => $request->user()?->id,
            'published_at' => ($validated['is_published'] ?? false) ? now() : null,
        ]);

        return back()->with('flash', [
            'type' => 'success',
            'title' => 'Éxito',
            'message' => 'La evidencia fue cargada correctamente.',
        ]);
    }

    public function togglePublish(WorkCenter $workCenter, WorkCenterClimaEvidence $evidence): RedirectResponse
    {
        $this->authorize('viewWorkCenterDashboard', $workCenter);

        if (! request()->user()?->hasRole(['admin', 'super-admin'])) {
            abort(403, 'No autorizado.');
        }

        if ($evidence->work_center_id !== $workCenter->id) {
            abort(403, 'No autorizado.');
        }

        $publish = ! $evidence->is_published;
        $evidence->update([
            'is_published' => $publish,
            'published_at' => $publish ? now() : null,
        ]);

        return back()->with('flash', [
            'type' => 'success',
            'title' => 'Éxito',
            'message' => $publish ? 'Evidencia publicada.' : 'Evidencia cambiada a borrador.',
        ]);
    }

    public function download(WorkCenter $workCenter, WorkCenterClimaEvidence $evidence): BinaryFileResponse
    {
        $this->authorize('viewWorkCenterDashboard', $workCenter);

        if ($evidence->work_center_id !== $workCenter->id) {
            abort(403, 'No autorizado.');
        }

        return response()->download(Storage::disk('public')->path($evidence->storage_path), $evidence->original_filename);
    }

    public function destroy(WorkCenter $workCenter, WorkCenterClimaEvidence $evidence): RedirectResponse
    {
        $this->authorize('viewWorkCenterDashboard', $workCenter);

        if (! request()->user()?->hasRole(['admin', 'super-admin'])) {
            abort(403, 'No autorizado.');
        }

        if ($evidence->work_center_id !== $workCenter->id) {
            abort(403, 'No autorizado.');
        }

        Storage::disk('public')->delete($evidence->storage_path);
        $evidence->delete();

        return back()->with('flash', [
            'type' => 'success',
            'title' => 'Éxito',
            'message' => 'La evidencia fue eliminada correctamente.',
        ]);
    }
}
