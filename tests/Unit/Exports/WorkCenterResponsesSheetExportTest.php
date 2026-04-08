<?php

namespace Tests\Unit\Exports;

use App\Exports\WorkCenterResponsesSheetExport;
use PHPUnit\Framework\TestCase;

class WorkCenterResponsesSheetExportTest extends TestCase
{
    public function test_it_uses_business_friendly_headings(): void
    {
        $sheet = new WorkCenterResponsesSheetExport([
            'id' => 'wc-1',
            'code' => 'CT-01',
            'name' => 'Centro Principal',
            'is_primary' => true,
            'evaluations' => [],
        ]);

        $headings = $sheet->headings();

        $this->assertSame('Folio personal', $headings[0]);
        $this->assertSame('Folios de evaluacion', $headings[1]);
        $this->assertSame('Nombre evaluado', $headings[2]);
        $this->assertSame('Origen', $headings[3]);
        $this->assertSame('Edad', $headings[4]);
        $this->assertSame('Guia III - Pregunta 1', $headings[17]);
        $this->assertContains('Guia III - Pregunta 1', $headings);
        $this->assertContains('Acontecimiento traumatico 1', $headings);
        $this->assertContains('Guia I - Pregunta 1', $headings);
        $this->assertContains('Edad', $headings);
        $this->assertContains('Departamento / Seccion / Area', $headings);
    }

    public function test_it_orders_rows_by_source_and_then_folio(): void
    {
        $workCenter = [
            'id' => 'wc-1',
            'code' => 'CT-01',
            'name' => 'Centro Principal',
            'is_primary' => true,
            'evaluations' => [
                [
                    'folio' => '0003',
                    'personal_folio' => '0123',
                    'evaluee_name' => 'Juan Perez',
                    'source' => 'paper',
                    'referencia_iii' => ['pregunta_1' => 'si'],
                    'referencia_i_acontecimientos_traumaticos' => null,
                    'referencia_i' => null,
                    'referencia_v' => null,
                ],
                [
                    'folio' => '0002',
                    'personal_folio' => '0001',
                    'evaluee_name' => 'Ana Lopez',
                    'source' => 'online',
                    'referencia_iii' => ['pregunta_1' => 'no'],
                    'referencia_i_acontecimientos_traumaticos' => null,
                    'referencia_i' => null,
                    'referencia_v' => null,
                ],
                [
                    'folio' => '0001',
                    'personal_folio' => '0001',
                    'evaluee_name' => 'Ana Lopez',
                    'source' => 'online',
                    'referencia_iii' => ['pregunta_1' => 'si'],
                    'referencia_i_acontecimientos_traumaticos' => null,
                    'referencia_i' => null,
                    'referencia_v' => null,
                ],
            ],
        ];

        $sheet = new WorkCenterResponsesSheetExport($workCenter);
        $rows = $sheet->array();

        $this->assertCount(2, $rows);
        $this->assertSame('0001', $rows[0][0]);
        $this->assertSame('0001, 0002', $rows[0][1]);
        $this->assertSame('Ana Lopez', $rows[0][2]);
        $this->assertSame('En línea', $rows[0][3]);

        $this->assertSame('0123', $rows[1][0]);
        $this->assertSame('0003', $rows[1][1]);
        $this->assertSame('Juan Perez', $rows[1][2]);
        $this->assertSame('Presencial', $rows[1][3]);
    }
}
