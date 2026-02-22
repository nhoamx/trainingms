<?php

namespace App\Http\Controllers\WorkCenter;

use App\Http\Controllers\Controller;
use App\Http\Requests\WorkCenter\StoreWorkCenterSensitizationVideoRequest;
use App\Models\WorkCenter;
use App\Models\WorkCenterSensitizationVideo;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;

class WorkCenterSensitizationVideoController extends Controller
{
    use AuthorizesRequests;

    public function store(StoreWorkCenterSensitizationVideoRequest $request, WorkCenter $workCenter): RedirectResponse
    {
        $this->authorize('viewWorkCenterDashboard', $workCenter);

        $validated = $request->validated();
        $videoFile = $request->file('video');

        $path = $videoFile->store("work-centers/{$workCenter->id}/sensitization-videos", 'public');

        $workCenter->sensitizationVideos()->create([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'audience' => $validated['audience'],
            'storage_path' => $path,
            'original_filename' => $videoFile->getClientOriginalName(),
            'mime_type' => $videoFile->getMimeType() ?? 'video/mp4',
            'file_size' => $videoFile->getSize(),
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_active' => true,
            'uploaded_by' => $request->user()?->id,
        ]);

        return back()->with('flash', [
            'type' => 'success',
            'title' => 'Éxito',
            'message' => 'Video de sensibilización cargado correctamente.',
        ]);
    }

    public function destroy(WorkCenter $workCenter, WorkCenterSensitizationVideo $video): RedirectResponse
    {
        $this->authorize('viewWorkCenterDashboard', $workCenter);

        if (! request()->user()?->hasRole(['admin', 'super-admin'])) {
            abort(403, 'No autorizado.');
        }

        if ($video->work_center_id !== $workCenter->id) {
            abort(403, 'No autorizado para eliminar este video.');
        }

        Storage::disk('public')->delete($video->storage_path);
        $video->delete();

        return back()->with('flash', [
            'type' => 'success',
            'title' => 'Éxito',
            'message' => 'Video eliminado correctamente.',
        ]);
    }
}
