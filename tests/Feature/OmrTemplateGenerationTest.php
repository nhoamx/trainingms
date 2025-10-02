<?php

namespace Tests\Feature;

use Barryvdh\DomPDF\Facade\Pdf;
use Tests\TestCase;

class OmrTemplateGenerationTest extends TestCase
{
    /**
     * Test para generar PDFs de los templates OMR con marcadores optimizados.
     */
    public function test_generate_omr_reference_pdfs(): void
    {
        $templates = [
            'referencia-i' => [
                'title' => 'Referencia I - Acontecimientos Traumáticos',
                'folio' => '13001234567',
            ],
            'referencia-iii' => [
                'title' => 'Referencia III - Factores de Riesgo',
                'folio' => '12001234567',
            ],
            'referencia-v' => [
                'title' => 'Referencia V - Datos Demográficos',
                'folio' => '17001234567',
            ],
        ];

        foreach ($templates as $template => $config) {
            $this->generateOmrPdf($template, $config);
        }

        $this->assertTrue(true, 'PDFs generados exitosamente');
    }

    /**
     * Genera un PDF para un template OMR específico.
     */
    private function generateOmrPdf(string $template, array $config): void
    {
        try {
            // Datos específicos para cada template
            $viewData = $this->getTemplateData($template, $config);

            // Crear vista con datos de prueba
            $view = view("omr.{$template}", $viewData);

            // Generar PDF
            $pdf = Pdf::loadHTML($view->render())
                ->setPaper('letter', 'portrait')
                ->setOptions([
                    'dpi' => 300,
                    'defaultFont' => 'Arial',
                    'isHtml5ParserEnabled' => true,
                    'isPhpEnabled' => true,
                    'margin-top' => 0,
                    'margin-right' => 0,
                    'margin-bottom' => 0,
                    'margin-left' => 0,
                ]);

            // Crear directorio si no existe
            $outputDir = storage_path('app/public/omr-tests');
            if (! file_exists($outputDir)) {
                mkdir($outputDir, 0755, true);
            }

            // Guardar PDF
            $filename = "{$template}-test-".date('Y-m-d-H-i-s').'.pdf';
            $filepath = "{$outputDir}/{$filename}";

            $pdf->save($filepath);

            echo "\n✅ PDF generado: {$filename}";
            echo "\n📁 Ubicación: {$filepath}";

            // Copiar a directorio Docker si existe
            $dockerDir = base_path('docker');
            if (file_exists($dockerDir)) {
                $dockerPath = "{$dockerDir}/{$template}-reference.pdf";
                copy($filepath, $dockerPath);
                echo "\n📦 Copiado a Docker: {$template}-reference.pdf";
            }

        } catch (\Exception $e) {
            echo "\n❌ Error generando PDF para {$template}: {$e->getMessage()}";
            throw $e;
        }
    }

    /**
     * Obtiene datos específicos para cada template.
     */
    private function getTemplateData(string $template, array $config): array
    {
        $baseData = [
            'folio' => $config['folio'],
        ];

        switch ($template) {
            case 'referencia-i':
                return array_merge($baseData, [
                    'questions' => $this->getGuideIQuestions(),
                    'totalQuestions' => 22,
                ]);

            case 'referencia-iii':
                return array_merge($baseData, [
                    'generalQuestions' => $this->getReferenceIIIGeneralQuestions(),
                    'conditionalQuestions' => $this->getReferenceIIIConditionalQuestions(),
                    'totalQuestions' => 72,
                ]);

            case 'referencia-v':
                return array_merge($baseData, [
                    'questions' => $this->getReferenceVQuestions(),
                    'demographics' => $this->getDemographicData(),
                ]);

            default:
                return $baseData;
        }
    }

    /**
     * Datos de prueba para Guía I.
     */
    private function getGuideIQuestions(): array
    {
        return collect(range(1, 22))->map(function ($num) {
            return "¿Ha presenciado o experimentado algún acontecimiento traumático severo número {$num}?";
        })->toArray();
    }

    /**
     * Datos de prueba para Referencia III - Preguntas generales.
     */
    private function getReferenceIIIGeneralQuestions(): array
    {
        return collect(range(1, 46))->mapWithKeys(function ($num) {
            return [$num => "Pregunta general de factores de riesgo psicosocial número {$num}"];
        })->toArray();
    }

    /**
     * Datos de prueba para Referencia III - Preguntas condicionales.
     */
    private function getReferenceIIIConditionalQuestions(): array
    {
        return collect(range(47, 72))->mapWithKeys(function ($num) {
            return [$num => "Pregunta condicional de factores de riesgo psicosocial número {$num}"];
        })->toArray();
    }

    /**
     * Datos de prueba para Referencia III.
     */
    private function getReferenceIIIQuestions(): array
    {
        return collect(range(1, 72))->map(function ($num) {
            return "Pregunta de factores de riesgo psicosocial número {$num}";
        })->toArray();
    }

    /**
     * Datos de prueba para Referencia V.
     */
    private function getReferenceVQuestions(): array
    {
        return [];
    }

    /**
     * Datos demográficos de prueba.
     */
    private function getDemographicData(): array
    {
        return [
            'sexo' => ['Masculino', 'Femenino'],
            'edad' => ['18-25', '26-35', '36-45', '46-55', '56+'],
            'escolaridad' => ['Primaria', 'Secundaria', 'Preparatoria', 'Universidad'],
        ];
    }

    /**
     * Test para validar que los marcadores OMR estén correctamente posicionados.
     */
    public function test_omr_markers_positioning(): void
    {
        $templates = ['referencia-i', 'referencia-iii', 'referencia-v'];

        foreach ($templates as $template) {
            $viewData = $this->getTemplateData($template, ['folio' => '12345678901']);
            $view = view("omr.{$template}", $viewData);

            $html = $view->render();

            // Verificar que no haya marcadores duplicados en el contenido
            $markerCount = substr_count($html, 'alignment-marker marker-top-left');
            $this->assertEquals(
                1,
                $markerCount,
                "Template {$template} debería tener exactamente 1 marcador TL, encontrados: {$markerCount}"
            );

            // Verificar que el layout incluya los marcadores
            $this->assertStringContainsString(
                'alignment-marker',
                $html,
                "Template {$template} debería incluir marcadores del layout"
            );
        }
    }

    /**
     * Test para validar CSS de marcadores OMR.
     */
    public function test_omr_marker_css_properties(): void
    {
        $view = view('omr.layout');
        $html = $view->render();

        // Verificar propiedades CSS de los marcadores
        $this->assertStringContainsString('width: 10mm', $html);
        $this->assertStringContainsString('height: 10mm', $html);
        $this->assertStringContainsString('background: black', $html);
        $this->assertStringContainsString('top: 3mm', $html);
        $this->assertStringContainsString('bottom: 3mm', $html);
        $this->assertStringContainsString('left: 3mm', $html);
        $this->assertStringContainsString('right: 3mm', $html);
        $this->assertStringContainsString('z-index: 9999', $html);
    }
}
