<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAssetRequest;
use App\Http\Requests\UpdateAssetRequest;
use App\Models\Asset;
use App\Models\Organization;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use ZipArchive;

class AssetController extends Controller
{
    public function index(Organization $organization)
    {
        $assets = $organization->assets()
            ->where('asset_category', 'extintor')
            ->orderByRaw('CAST(consecutive_number AS UNSIGNED)')
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
        $qrCodeWithLabel = $this->generateQrWithLabel($asset);

        $filename = $this->sanitizeFilename($asset->location).'-'.$asset->serial_number.'.svg';

        return response($qrCodeWithLabel)
            ->header('Content-Type', 'image/svg+xml')
            ->header('Content-Disposition', 'attachment; filename="'.$filename.'"');
    }

    public function downloadAllQr(Organization $organization)
    {
        $assets = $organization->assets()
            ->where('asset_category', 'extintor')
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
            $qrCodeWithLabel = $this->generateQrWithLabel($asset);

            $location = $asset->location ?? 'sin-ubicacion';
            $filename = $this->sanitizeFilename($location).'-'.$asset->serial_number.'.svg';
            $zip->addFromString($filename, $qrCodeWithLabel);
        }

        $zip->close();

        return response()->download($zipPath, $zipFileName)->deleteFileAfterSend(true);
    }

    public function inspect(Asset $asset)
    {
        $user = auth()->user();
        $isInspector = $user && $user->hasAnyRole(['admin', 'super-admin']);

        return Inertia::render('Assets/Inspect', [
            'title' => 'Inspección de Extintor',
            'asset' => $asset->load(['organization', 'inspections' => function ($query) {
                $query->latest()->limit(5);
            }]),
            'isAuthenticated' => (bool) $user,
            'isInspector' => $isInspector,
        ]);
    }

    public function createInspection(Asset $asset)
    {
        $user = auth()->user();
        $canInspect = $user && $user->hasAnyRole(['admin', 'super-admin']);

        return Inertia::render('Assets/CreateInspection', [
            'title' => 'Nueva Inspección',
            'asset' => $asset->load('organization'),
            'checklist' => config('asset_inspection_checklist.checklist'),
            'canInspect' => $canInspect,
        ]);
    }

    public function storeInspection(\App\Http\Requests\StoreAssetInspectionRequest $request, Asset $asset)
    {
        // Verificar que el usuario tenga permisos de inspector
        if (! auth()->user() || ! auth()->user()->hasAnyRole(['admin', 'super-admin'])) {
            abort(403, 'No autorizado para realizar inspecciones');
        }

        $data = $request->validated();
        $data['asset_id'] = $asset->id;

        \App\Models\AssetInspection::create($data);

        return redirect()
            ->route('assets.inspect', $asset)
            ->with('success', 'Inspección registrada exitosamente');
    }

    private function generateQrWithLabel(Asset $asset): string
    {
        $qrContent = route('assets.inspect', $asset);

        // Generar el QR code básico
        $qrCode = QrCode::format('svg')
            ->size(300)
            ->margin(2)
            ->generate($qrContent);

        // Extraer el contenido del SVG del QR
        $qrSvg = simplexml_load_string($qrCode);
        $qrWidth = (int) $qrSvg['width'];
        $qrHeight = (int) $qrSvg['height'];

        // Configuración del label y padding
        $labelHeight = 40;
        $padding = 15;
        $borderWidth = 2;

        // Dimensiones totales con padding
        $contentWidth = $qrWidth + ($padding * 2);
        $contentHeight = $qrHeight + $labelHeight + ($padding * 2);

        $fontSize = 16;

        // Crear un nuevo SVG que contenga el QR y el label con borde
        $svg = '<?xml version="1.0" encoding="UTF-8"?>';
        $svg .= '<svg xmlns="http://www.w3.org/2000/svg" width="'.$contentWidth.'" height="'.$contentHeight.'" viewBox="0 0 '.$contentWidth.' '.$contentHeight.'">';

        // Fondo blanco
        $svg .= '<rect width="'.$contentWidth.'" height="'.$contentHeight.'" fill="white"/>';

        // Borde negro alrededor de todo
        $svg .= '<rect x="'.($borderWidth / 2).'" y="'.($borderWidth / 2).'" width="'.($contentWidth - $borderWidth).'" height="'.($contentHeight - $borderWidth).'" fill="none" stroke="#000000" stroke-width="'.$borderWidth.'"/>';

        // Agregar el QR code centrado con padding
        $qrInnerSvg = preg_replace('/<\?xml.*?\?>/s', '', $qrCode);
        $qrInnerSvg = preg_replace('/<svg[^>]*>/s', '<g transform="translate('.$padding.','.$padding.')">', $qrInnerSvg);
        $qrInnerSvg = str_replace('</svg>', '</g>', $qrInnerSvg);
        $svg .= $qrInnerSvg;

        // Agregar el label: "n° consecutivo - ubicación"
        $label = $asset->consecutive_number.' - '.($asset->location ?? 'Sin ubicación');
        $textY = $qrHeight + $padding + 25;
        $svg .= '<text x="'.($contentWidth / 2).'" y="'.$textY.'" font-family="Arial, sans-serif" font-size="'.$fontSize.'" font-weight="bold" text-anchor="middle" fill="#000000">';
        $svg .= htmlspecialchars($label, ENT_XML1, 'UTF-8');
        $svg .= '</text>';

        $svg .= '</svg>';

        return $svg;
    }

    private function sanitizeFilename(string $filename): string
    {
        $filename = str_replace(['/', '\\', ':', '*', '?', '"', '<', '>', '|'], '-', $filename);
        $filename = preg_replace('/\s+/', '-', $filename);
        $filename = preg_replace('/-+/', '-', $filename);

        return trim($filename, '-');
    }

    public function downloadTemplate(Organization $organization)
    {
        return Excel::download(
            new \App\Exports\AssetsTemplateExport($organization, includeExisting: true),
            'plantilla-extintores-'.$this->sanitizeFilename($organization->name).'.xlsx'
        );
    }

    public function importAssets(\Illuminate\Http\Request $request, Organization $organization)
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls', 'max:10240'],
        ]);

        $file = $request->file('file');
        $path = $file->store('imports/assets', 'local');

        \App\Jobs\ProcessAssetsImport::dispatch(
            $path,
            $organization->id,
            auth()->id()
        );

        return redirect()
            ->route('organizations.assets.index', $organization)
            ->with('success', 'La importación se está procesando. Los resultados estarán disponibles en unos momentos.');
    }
}
