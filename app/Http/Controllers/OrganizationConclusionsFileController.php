<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOrganizationConclusionsFileRequest;
use App\Models\Organization;
use App\Models\OrganizationConclusionsFile;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OrganizationConclusionsFileController extends Controller
{
    use AuthorizesRequests;

    public function store(StoreOrganizationConclusionsFileRequest $request, Organization $organization): RedirectResponse
    {
        $this->authorize('viewOrganizationDashboard', $organization);

        $validated = $request->validated();
        $uploadedFile = $request->file('conclusions_file');
        $slot = (int) $validated['slot'];
        $extension = $uploadedFile->getClientOriginalExtension() ?: 'pdf';
        $path = $uploadedFile->storeAs(
            "{$organization->id}/conclusions",
            "slot_{$slot}.{$extension}",
            'public'
        );

        // Replace any existing file in this slot
        $existing = $organization->conclusionsFiles()->where('slot', $slot)->first();
        if ($existing) {
            Storage::disk($existing->disk)->delete($existing->path);
            $existing->delete();
        }

        $organization->conclusionsFiles()->create([
            'slot' => $slot,
            'title' => $validated['title'],
            'color' => $validated['color'],
            'disk' => 'public',
            'path' => $path,
            'original_filename' => $uploadedFile->getClientOriginalName(),
            'file_size' => $uploadedFile->getSize(),
            'mime_type' => $uploadedFile->getMimeType() ?? 'application/pdf',
            'is_published' => (bool) ($validated['is_published'] ?? false),
            'uploaded_by' => $request->user()?->id,
        ]);

        return back()->with('flash', [
            'type' => 'success',
            'title' => 'Éxito',
            'message' => 'Archivo cargado correctamente.',
        ]);
    }

    public function download(Organization $organization, OrganizationConclusionsFile $file): StreamedResponse
    {
        $this->authorize('viewOrganizationDashboard', $organization);

        abort_unless($file->organization_id === $organization->id, 403);

        return Storage::disk($file->disk)->download($file->path, $file->original_filename);
    }

    public function togglePublish(Organization $organization, OrganizationConclusionsFile $file): RedirectResponse
    {
        $this->authorize('viewOrganizationDashboard', $organization);
        abort_unless($file->organization_id === $organization->id, 403);
        abort_unless(request()->user()?->hasRole(['admin', 'super-admin']), 403);

        $file->update(['is_published' => ! $file->is_published]);

        return back();
    }

    public function destroy(Organization $organization, OrganizationConclusionsFile $file): RedirectResponse
    {
        $this->authorize('viewOrganizationDashboard', $organization);
        abort_unless($file->organization_id === $organization->id, 403);
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
