<?php

namespace App\Http\Controllers\WorkCenter;

use App\Http\Controllers\Controller;
use App\Http\Requests\WorkCenter\StoreWorkCenterClimaReportRequest;
use App\Models\WorkCenter;
use App\Models\WorkCenterClimaReport;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class WorkCenterClimaReportController extends Controller
{
    use AuthorizesRequests;

    public function store(StoreWorkCenterClimaReportRequest $request, WorkCenter $workCenter): RedirectResponse
    {
        $this->authorize('viewWorkCenterDashboard', $workCenter);

        $validated = $request->validated();
        $reportFile = $request->file('report_file');
        $lang = $validated['language'];
        $orgId = $workCenter->organization_id;
        $extension = $reportFile->getClientOriginalExtension() ?: 'pdf';
        $path = $reportFile->storeAs(
            "{$orgId}/{$workCenter->id}/clima_laboral",
            "informe_{$lang}.{$extension}",
            'public'
        );

        if (($validated['is_active'] ?? false) === true) {
            $workCenter->climaReports()->where('language', $lang)->update(['is_active' => false]);
        }

        $workCenter->climaReports()->create([
            'title' => $validated['title'],
            'language' => $lang,
            'storage_path' => $path,
            'original_filename' => $reportFile->getClientOriginalName(),
            'mime_type' => $reportFile->getMimeType() ?? 'application/pdf',
            'file_size' => $reportFile->getSize(),
            'is_published' => (bool) ($validated['is_published'] ?? false),
            'is_active' => (bool) ($validated['is_active'] ?? false),
            'uploaded_by' => $request->user()?->id,
            'published_at' => ($validated['is_published'] ?? false) ? now() : null,
        ]);

        return back()->with('flash', [
            'type' => 'success',
            'title' => 'Éxito',
            'message' => 'El informe fue cargado correctamente.',
        ]);
    }

    public function togglePublish(WorkCenter $workCenter, WorkCenterClimaReport $report): RedirectResponse
    {
        $this->authorize('viewWorkCenterDashboard', $workCenter);

        if (! request()->user()?->hasRole(['admin', 'super-admin'])) {
            abort(403, 'No autorizado.');
        }

        if ($report->work_center_id !== $workCenter->id) {
            abort(403, 'No autorizado.');
        }

        $publish = ! $report->is_published;
        $report->update([
            'is_published' => $publish,
            'published_at' => $publish ? now() : null,
        ]);

        return back()->with('flash', [
            'type' => 'success',
            'title' => 'Éxito',
            'message' => $publish ? 'Informe publicado.' : 'Informe cambiado a borrador.',
        ]);
    }

    public function setActive(WorkCenter $workCenter, WorkCenterClimaReport $report): RedirectResponse
    {
        $this->authorize('viewWorkCenterDashboard', $workCenter);

        if (! request()->user()?->hasRole(['admin', 'super-admin'])) {
            abort(403, 'No autorizado.');
        }

        if ($report->work_center_id !== $workCenter->id) {
            abort(403, 'No autorizado.');
        }

        $workCenter->climaReports()->update(['is_active' => false]);
        $report->update(['is_active' => true]);

        return back()->with('flash', [
            'type' => 'success',
            'title' => 'Éxito',
            'message' => 'Se marcó el informe como vigente.',
        ]);
    }

    public function download(WorkCenter $workCenter, WorkCenterClimaReport $report): BinaryFileResponse
    {
        $this->authorize('viewWorkCenterDashboard', $workCenter);

        if ($report->work_center_id !== $workCenter->id) {
            abort(403, 'No autorizado.');
        }

        if (! request()->user()?->hasRole(['admin', 'super-admin']) && ! $report->is_published) {
            abort(403, 'No autorizado para descargar este informe.');
        }

        return response()->download(Storage::disk('public')->path($report->storage_path), $report->original_filename);
    }

    public function destroy(WorkCenter $workCenter, WorkCenterClimaReport $report): RedirectResponse
    {
        $this->authorize('viewWorkCenterDashboard', $workCenter);

        if (! request()->user()?->hasRole(['admin', 'super-admin'])) {
            abort(403, 'No autorizado.');
        }

        if ($report->work_center_id !== $workCenter->id) {
            abort(403, 'No autorizado.');
        }

        Storage::disk('public')->delete($report->storage_path);
        $report->delete();

        return back()->with('flash', [
            'type' => 'success',
            'title' => 'Éxito',
            'message' => 'El informe fue eliminado correctamente.',
        ]);
    }
}
