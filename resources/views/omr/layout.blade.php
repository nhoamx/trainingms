<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title') - NOM-035-STPS-2018</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            background: white;
            color: black;
        }

        .page {
            width: 210mm;
            min-height: 297mm;
            margin: 0 auto;
            background: white;
            padding: 10mm;
            position: relative;
        }

        /* Marcadores de alineación */
        .alignment-marker {
            position: absolute;
            width: 8mm;
            height: 8mm;
            background: black;
        }

        .marker-top-left {
            top: 5mm;
            left: 5mm;
        }

        .marker-top-right {
            top: 5mm;
            right: 5mm;
        }

        .marker-bottom-left {
            bottom: 5mm;
            left: 5mm;
        }

        .marker-bottom-right {
            bottom: 5mm;
            right: 5mm;
        }

        /* Encabezado */
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid black;
            padding-bottom: 10px;
        }

        .header h1 {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .header h2 {
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .header p {
            font-size: 10px;
        }

        /* Información del folio */
        .folio-section {
            position: absolute;
            top: 10mm;
            left: 10mm;
            border: 2px solid black;
            padding: 5px;
            background: #f0f0f0;
        }

        .folio-section h3 {
            font-size: 10px;
            margin-bottom: 5px;
            text-align: center;
        }

        .folio-boxes {
            display: flex;
            gap: 2px;
        }

        .folio-box {
            width: 8mm;
            height: 8mm;
            border: 1px solid black;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }

        /* Información de fecha */
        .date-section {
            position: absolute;
            top: 10mm;
            right: 10mm;
            border: 2px solid black;
            padding: 5px;
            background: #f0f0f0;
        }

        .date-section h3 {
            font-size: 10px;
            margin-bottom: 5px;
            text-align: center;
        }

        .date-row {
            display: flex;
            align-items: center;
            margin-bottom: 3px;
        }

        .date-label {
            width: 20mm;
            font-size: 9px;
        }

        .date-boxes {
            display: flex;
            gap: 1px;
        }

        .date-box {
            width: 6mm;
            height: 6mm;
            border: 1px solid black;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 8px;
        }

        /* Contenido principal */
        .content {
            margin-top: 40mm;
            clear: both;
        }

        /* Burbujas para respuestas */
        .bubble {
            width: 4mm;
            height: 4mm;
            border: 1px solid black;
            border-radius: 50%;
            display: inline-block;
            margin: 0 2mm 0 1mm;
            vertical-align: middle;
        }

        .question-row {
            display: flex;
            align-items: center;
            margin-bottom: 4mm;
            min-height: 6mm;
        }

        .question-number {
            width: 10mm;
            font-weight: bold;
            flex-shrink: 0;
        }

        .answer-options {
            flex: 1;
            display: flex;
            align-items: center;
            gap: 3mm;
        }

        .option-group {
            display: flex;
            align-items: center;
            gap: 1mm;
        }

        .option-label {
            font-size: 10px;
            margin-right: 1mm;
        }

        /* Secciones específicas */
        .section-title {
            font-size: 14px;
            font-weight: bold;
            margin: 10mm 0 5mm 0;
            text-align: center;
            background: #e0e0e0;
            padding: 2mm;
            border: 1px solid black;
        }

        .instructions {
            background: #f8f8f8;
            border: 1px solid #ccc;
            padding: 3mm;
            margin-bottom: 5mm;
            font-size: 10px;
        }

        /* Utilidades de impresión */
        @media print {
            .page {
                margin: 0;
                box-shadow: none;
                page-break-after: always;
            }
            
            body {
                background: white;
            }
        }

        /* Grillas para preguntas múltiples */
        .questions-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 2mm;
            margin: 5mm 0;
        }

        @media print {
            .questions-grid {
                break-inside: avoid;
            }
        }

        /* Estilos para Referencia V (datos demográficos) */
        .demographic-section {
            margin-bottom: 8mm;
            border: 1px solid black;
            padding: 3mm;
        }

        .demographic-title {
            font-weight: bold;
            font-size: 11px;
            margin-bottom: 3mm;
            background: #e0e0e0;
            padding: 1mm;
            text-align: center;
        }

        .demographic-options {
            display: flex;
            flex-wrap: wrap;
            gap: 2mm;
        }

        .demographic-option {
            display: flex;
            align-items: center;
            margin: 1mm 3mm 1mm 0;
        }
    </style>
</head>
<body>
    <div class="page">
        <!-- Marcadores de alineación -->
        <div class="alignment-marker marker-top-left"></div>
        <div class="alignment-marker marker-top-right"></div>
        <div class="alignment-marker marker-bottom-left"></div>
        <div class="alignment-marker marker-bottom-right"></div>

        <!-- Información del folio -->
        <div class="folio-section">
            <h3>FOLIO</h3>
            <div class="folio-boxes">
                @for ($i = 1; $i <= 9; $i++)
                    <div class="folio-box">{{ $i }}</div>
                @endfor
            </div>
        </div>

        <!-- Información de fecha -->
        <div class="date-section">
            <h3>FECHA</h3>
            <div class="date-row">
                <span class="date-label">Día:</span>
                <div class="date-boxes">
                    <div class="date-box">D</div>
                    <div class="date-box">D</div>
                </div>
            </div>
            <div class="date-row">
                <span class="date-label">Mes:</span>
                <div class="date-boxes">
                    <div class="date-box">M</div>
                    <div class="date-box">M</div>
                </div>
            </div>
            <div class="date-row">
                <span class="date-label">Año:</span>
                <div class="date-boxes">
                    <div class="date-box">A</div>
                    <div class="date-box">A</div>
                    <div class="date-box">A</div>
                    <div class="date-box">A</div>
                </div>
            </div>
        </div>

        <!-- Encabezado -->
        <div class="header">
            <h1>IDENTIFICACIÓN Y ANÁLISIS DE LOS FACTORES DE RIESGO PSICOSOCIAL</h1>
            <h2>Y EVALUACIÓN DEL ENTORNO ORGANIZACIONAL EN LOS CENTROS DE TRABAJO</h2>
            <p>NOM-035-STPS-2018</p>
            <h2>@yield('guide-title')</h2>
        </div>

        <!-- Contenido específico de cada guía -->
        <div class="content">
            @yield('content')
        </div>

        <!-- Pie de página -->
        <div style="position: absolute; bottom: 5mm; left: 10mm; right: 10mm; text-align: center; font-size: 8px; border-top: 1px solid black; padding-top: 2mm;">
            <p>Este documento debe llenarse con tinta azul o negra. Marque completamente las burbujas. No utilice marcas fuera de las burbujas.</p>
            <p>NOM-035-STPS-2018 - Factores de riesgo psicosocial en el trabajo</p>
        </div>
    </div>
</body>
</html>