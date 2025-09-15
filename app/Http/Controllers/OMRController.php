<?php

namespace App\Http\Controllers;

class OMRController extends Controller
{
    /**
     * Mostrar hoja OMR para Guía de Referencia I
     */
    public function referenciaI()
    {
        $config = config('referencia_i');
        $questions = [];

        // Flatten the questions from all sections
        foreach ($config as $sectionQuestions) {
            $questions = array_merge($questions, $sectionQuestions);
        }

        return view('omr.referencia-i', [
            'questions' => $questions,
            'totalQuestions' => count($questions),
        ]);
    }

    /**
     * Mostrar hoja OMR para Guía de Referencia III
     */
    public function referenciaIII()
    {
        $config = config('referencia_iii');
        $generalQuestions = $config['general'];
        $conditionalSections = $config['conditional_sections'];

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
    public function referenciaV()
    {
        $config = config('referencia_v');

        return view('omr.referencia-v', [
            'config' => $config,
        ]);
    }

    /**
     * Mostrar hoja OMR para Escala Cisneros
     */
    public function escalaCisneros()
    {
        $questions = config('escala_cisneros');

        return view('omr.escala-cisneros', [
            'questions' => $questions,
            'totalQuestions' => count($questions),
        ]);
    }
}
