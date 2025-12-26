<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAssetRequest;
use App\Http\Requests\UpdateAssetRequest;
use App\Models\Asset;
use App\Models\Organization;
use Inertia\Inertia;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use ZipArchive;

class AssetController extends Controller
{
    public function index(Organization $organization)
    {
        $assets = $organization->assets()
            ->where('asset_type', 'extintor')
            ->orderBy('location')
            ->get();

        return Inertia::render('Assets/Index', [
            'title' => 'Extintores - '.$organization->name,
            'organization' => $organization,
            'assets' => $assets,
        ]);
    }

    public function create(Organization $organization)
    {
        return Inertia::render('Assets/Create', [
            'title' => 'Nuevo Extintor',
            'organization' => $organization,
        ]);
    }

    public function store(StoreAssetRequest $request, Organization $organization)
    {
        $data = $request->validated();
        $data['organization_id'] = $organization->id;

        Asset::create($data);

        return redirect()
            ->route('organizations.assets.index', $organization)
            ->with('success', 'Extintor creado exitosamente.');
    }

    public function edit(Organization $organization, Asset $asset)
    {
        return Inertia::render('Assets/Edit', [
            'title' => 'Editar Extintor',
            'organization' => $organization,
            'asset' => $asset,
        ]);
    }

    public function update(UpdateAssetRequest $request, Organization $organization, Asset $asset)
    {
        $asset->update($request->validated());

        return redirect()
            ->route('organizations.assets.index', $organization)
            ->with('success', 'Extintor actualizado exitosamente.');
    }

    public function destroy(Organization $organization, Asset $asset)
    {
        $asset->delete();

        return redirect()
            ->route('organizations.assets.index', $organization)
            ->with('success', 'Extintor eliminado exitosamente.');
    }

    public function qrCode(Organization $organization, Asset $asset)
    {
        $qrContent = route('assets.inspect', $asset);

        $qrCode = QrCode::format('svg')
            ->size(300)
            ->margin(2)
            ->generate($qrContent);

        return response($qrCode)
            ->header('Content-Type', 'image/svg+xml');
    }

    public function downloadQr(Organization $organization, Asset $asset)
    {
        $qrContent = route('assets.inspect', $asset);

        $qrCode = QrCode::format('svg')
            ->size(300)
            ->margin(2)
            ->generate($qrContent);

        $filename = $this->sanitizeFilename($asset->location).'-'.$asset->serial_number.'.svg';

        return response($qrCode)
            ->header('Content-Type', 'image/svg+xml')
            ->header('Content-Disposition', 'attachment; filename="'.$filename.'"');
    }

    public function downloadAllQr(Organization $organization)
    {
        $assets = $organization->assets()
            ->where('asset_type', 'extintor')
            ->get();

        if ($assets->isEmpty()) {
            return redirect()
                ->route('organizations.assets.index', $organization)
                ->with('error', 'No hay extintores para descargar.');
        }

        $zipFileName = 'extintores-qr-'.$this->sanitizeFilename($organization->name).'.zip';
        $zipPath = storage_path('app/temp/'.$zipFileName);

        if (! is_dir(storage_path('app/temp'))) {
            mkdir(storage_path('app/temp'), 0755, true);
        }

        $zip = new ZipArchive;
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            return redirect()
                ->route('organizations.assets.index', $organization)
                ->with('error', 'No se pudo crear el archivo ZIP.');
        }

        foreach ($assets as $asset) {
            $qrContent = route('assets.inspect', $asset);
            $qrCode = QrCode::format('svg')
                ->size(300)
                ->margin(2)
                ->generate($qrContent);

            $filename = $this->sanitizeFilename($asset->location).'-'.$asset->serial_number.'.svg';
            $zip->addFromString($filename, $qrCode);
        }

        $zip->close();

        return response()->download($zipPath, $zipFileName)->deleteFileAfterSend(true);
    }

    public function inspect(Asset $asset)
    {
        return Inertia::render('Assets/Inspect', [
            'title' => 'Inspección de Extintor',
            'asset' => $asset->load('organization'),
        ]);
    }

    private function sanitizeFilename(string $filename): string
    {
        $filename = str_replace(['/', '\\', ':', '*', '?', '"', '<', '>', '|'], '-', $filename);
        $filename = preg_replace('/\s+/', '-', $filename);
        $filename = preg_replace('/-+/', '-', $filename);

        return trim($filename, '-');
    }
}
