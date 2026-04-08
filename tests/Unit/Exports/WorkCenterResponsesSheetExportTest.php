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

        $this->assertSame('Folio', $headings[0]);
        $this->assertSame('Nombre evaluado', $headings[1]);
        $this->assertSame('Origen', $headings[2]);
        $this->assertSame('Edad', $headings[3]);
        $this->assertSame('Guia III - Pregunta 1', $headings[16]);
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
                    'evaluee_name' => 'Juan Perez',
                    'source' => 'paper',
                    'referencia_iii' => ['pregunta_1' => 'si'],
                    'referencia_i_acontecimientos_traumaticos' => null,
                    'referencia_i' => null,
                    'referencia_v' => null,
                ],
                [
                    'folio' => '0002',
                    'evaluee_name' => 'Ana Lopez',
                    'source' => 'online',
                    'referencia_iii' => ['pregunta_1' => 'no'],
                    'referencia_i_acontecimientos_traumaticos' => null,
                    'referencia_i' => null,
                    'referencia_v' => null,
                ],
                [
                    'folio' => '0001',
                    'evaluee_name' => 'Carlos Ruiz',
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

        $this->assertCount(3, $rows);
        $this->assertSame('0001', $rows[0][0]);
        $this->assertSame('Carlos Ruiz', $rows[0][1]);
        $this->assertSame('online', $rows[0][2]);
        $this->assertSame('0002', $rows[1][0]);
        $this->assertSame('Ana Lopez', $rows[1][1]);
        $this->assertSame('online', $rows[1][2]);
        $this->assertSame('0003', $rows[2][0]);
        $this->assertSame('Juan Perez', $rows[2][1]);
        $this->assertSame('paper', $rows[2][2]);
    }
}
