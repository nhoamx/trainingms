<?php

namespace App\Http\Controllers;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

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
     * Genera PDF con las hojas OMR
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

        // Configurar DomPDF para tamaño carta y márgenes de 10mm
        $pdf = PDF::setOptions([
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled' => true,
            'defaultFont' => 'Arial',
        ]);

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

        $pdf->loadHTML($htmlContent);
        $pdf->setPaper('letter', 'portrait');

        // Configurar márgenes de 10mm (aproximadamente 28 puntos)
        $pdf->setOption('margin-top', 28);
        $pdf->setOption('margin-bottom', 28);
        $pdf->setOption('margin-left', 28);
        $pdf->setOption('margin-right', 28);

        $filename = str_replace(' ', '_', strtolower($title)).'_'.date('Y-m-d_H-i-s').'.pdf';

        return $pdf->download($filename);
    }
}
