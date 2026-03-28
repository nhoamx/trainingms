<?php

namespace App\Http\Controllers\WorkCenter;

use App\Http\Controllers\Controller;
use App\Http\Requests\WorkCenter\StoreWorkCenterConclusionsFileRequest;
use App\Models\WorkCenter;
use App\Models\WorkCenterConclusionsFile;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class WorkCenterConclusionsFileController extends Controller
{
    use AuthorizesRequests;

    public function store(StoreWorkCenterConclusionsFileRequest $request, WorkCenter $workCenter): RedirectResponse
    {
        $this->authorize('viewWorkCenterDashboard', $workCenter);

        $validated = $request->validated();
        $uploadedFile = $request->file('conclusions_file');
        $slot = (int) $validated['slot'];
        $extension = $uploadedFile->getClientOriginalExtension() ?: 'pdf';
        $path = $uploadedFile->storeAs(
            "{$workCenter->id}/conclusions",
            "slot_{$slot}.{$extension}",
            'public'
        );

        $existing = $workCenter->conclusionsFiles()->where('slot', $slot)->first();
        if ($existing) {
            Storage::disk($existing->disk)->delete($existing->path);
            $existing->delete();
        }

        $workCenter->conclusionsFiles()->create([
            'slot' => $slot,
            'title' => $validated['title'],
            'color' => $validated['color'],
            'disk' => 'public',
            'path' => $path,
            'original_filename' => $uploadedFile->getClientOriginalName(),
            'file_size' => $uploadedFile->getSize(),
            'mime_type' => $uploadedFile->getMimeType() ?? 'application\/pdf',
            'is_published' => (bool) ($validated['is_published'] ?? false),
            'uploaded_by' => $request->user()?->id,
        ]);

        return back()->with('flash', [
            'type' => 'success',
            'title' => 'Éxito',
            'message' => 'Archivo cargado correctamente.',
        ]);
    }

    public function download(WorkCenter $workCenter, WorkCenterConclusionsFile $file): StreamedResponse
    {
        $this->authorize('viewWorkCenterDashboard', $workCenter);
        abort_unless($file->work_center_id === $workCenter->id, 403);

        return Storage::disk($file->disk)->download($file->path, $file->original_filename);
    }

    public function togglePublish(WorkCenter $workCenter, WorkCenterConclusionsFile $file): RedirectResponse
    {
        $this->authorize('viewWorkCenterDashboard', $workCenter);
        abort_unless($file->work_center_id === $workCenter->id, 403);
        abort_unless(request()->user()?->hasRole(['admin', 'super-admin']), 403);

        $file->update(['is_published' => ! $file->is_published]);

        return back();
    }

    public function destroy(WorkCenter $workCenter, WorkCenterConclusionsFile $file): RedirectResponse
    {
        $this->authorize('viewWorkCenterDashboard', $workCenter);
        abort_unless($file->work_center_id === $workCenter->id, 403);
        abort_unless(request()->user()?->hasRole(['admin', 'super-admin']), 403);

        Storage::disk($file->disk)->delete($file->path);
        $file->delete();

        return back()->with('flash', [
            'type' => 'success',
            'title' => 'Éxito',
            'message' => 'Archivo eliminado.',
        ]);
    }
}
