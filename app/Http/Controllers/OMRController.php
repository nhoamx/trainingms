<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Browsershot\Browsershot;

class OMRController extends Controller
{
    /**
     * Mostrar hoja OMR para Guía de Referencia I
     */
    public function referenciaI(Request $request)
    {
        $config = config('referencia_i');
        $questions = [];

        // Flatten the questions from all sections
        foreach ($config as $sectionQuestions) {
            $questions = array_merge($questions, $sectionQuestions);
        }

        // Si se solicita PDF, generar y descargar
        if ($request->has('download') && $request->download === 'pdf') {
            return $this->generatePdf('referencia-i', 'Guía de Referencia I', [
                'questions' => $questions,
                'totalQuestions' => count($questions),
            ], $request);
        }

        return view('omr.referencia-i', [
            'questions' => $questions,
            'totalQuestions' => count($questions),
        ]);
    }

    /**
     * Mostrar hoja OMR para Guía de Referencia III
     */
    public function referenciaIII(Request $request)
    {
        $config = config('referencia_iii');
        $generalQuestions = $config['general'];
        $conditionalSections = $config['conditional_sections'];

        // Si se solicita PDF, generar y descargar
        if ($request->has('download') && $request->download === 'pdf') {
            return $this->generatePdf('referencia-iii', 'Guía de Referencia III', [
                'generalQuestions' => $generalQuestions,
                'conditionalSections' => $conditionalSections,
                'totalGeneralQuestions' => count($generalQuestions),
                'totalConditionalSections' => count($conditionalSections),
            ], $request);
        }

        return view('omr.referencia-iii', [
            'generalQuestions' => $generalQuestions,
            'conditionalSections' => $conditionalSections,
            'totalGeneralQuestions' => count($generalQuestions),
            'totalConditionalSections' => count($conditionalSections),
        ]);
    }

    /**
     * Mostrar hoja OMR para Guía de Referencia V
     */
    public function referenciaV(Request $request)
    {
        $config = config('referencia_v');

        // Si se solicita PDF, generar y descargar
        if ($request->has('download') && $request->download === 'pdf') {
            return $this->generatePdf('referencia-v', 'Guía de Referencia V', [
                'config' => $config,
            ], $request);
        }

        return view('omr.referencia-v', [
            'config' => $config,
        ]);
    }

    /**
     * Mostrar hoja OMR para Escala Cisneros
     */
    public function escalaCisneros(Request $request)
    {
        $questions = config('escala_cisneros');

        // Si se solicita PDF, generar y descargar
        if ($request->has('download') && $request->download === 'pdf') {
            return $this->generatePdf('escala-cisneros', 'Escala Cisneros', [
                'questions' => $questions,
                'totalQuestions' => count($questions),
            ], $request);
        }

        return view('omr.escala-cisneros', [
            'questions' => $questions,
            'totalQuestions' => count($questions),
        ]);
    }

    /**
     * Genera PDF con las hojas OMR usando Browsershot
     */
    private function generatePdf(string $viewName, string $title, array $data, Request $request)
    {
        // Obtener folios de la request
        $folios = $request->input('folios', []);

        if (empty($folios)) {
            return back()->with('error', 'No se proporcionaron folios para generar el PDF.');
        }

        // Si folios viene como string separado por comas, convertir a array
        if (is_string($folios)) {
            $folios = explode(',', $folios);
        }

        // Limpiar los folios y asegurar formato
        $folios = array_map(function ($folio) {
            return str_pad(trim($folio), 4, '0', STR_PAD_LEFT);
        }, $folios);

        // Generar una página por cada folio
        $htmlContent = '';
        foreach ($folios as $index => $folio) {
            $pageData = array_merge($data, ['folio' => $folio]);
            $htmlContent .= view("omr.$viewName", $pageData)->render();

            // Agregar salto de página excepto en la última página
            if ($index < count($folios) - 1) {
                $htmlContent .= '<div style="page-break-after: always;"></div>';
            }
        }

        // Generar nombre de archivo temporal y final
        $filename = str_replace(' ', '_', strtolower($title)).'_'.date('Y-m-d_H-i-s').'.pdf';
        $tempPath = storage_path('app/temp/'.$filename);

        // Crear directorio temporal si no existe
        if (! file_exists(dirname($tempPath))) {
            mkdir(dirname($tempPath), 0755, true);
        }

        // Configurar Browsershot para WSL/Windows
        $browsershot = Browsershot::html($htmlContent)
            ->noSandbox()
            ->format('Letter')
            ->margins(10, 10, 10, 10) // top, right, bottom, left en mm
            ->showBackground()
            ->waitUntilNetworkIdle();

        // Configurar node y npm paths si estamos en WSL
        if (PHP_OS_FAMILY === 'Linux' && file_exists('/mnt/c/Program Files/nodejs/node.exe')) {
            $browsershot->setNodeBinary('/mnt/c/Program Files/nodejs/node.exe');
            $browsershot->setNpmBinary('/mnt/c/Program Files/nodejs/npm');
        }

        $browsershot->save($tempPath);

        // Retornar el archivo para descarga y luego eliminarlo
        return response()->download($tempPath)->deleteFileAfterSend(true);
    }
}
