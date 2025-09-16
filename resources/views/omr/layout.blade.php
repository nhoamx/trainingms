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
            page-break-after: always;
        }
        
        .page:last-child {
            page-break-after: auto;
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

        /* Estilos de folio y fecha movidos a plantillas individuales */

        /* Contenido principal */
        .content {
            margin-top: 10mm;
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
        
        .bubble-small {
            width: 3mm;
            height: 3mm;
            border: 1px solid black;
            border-radius: 50%;
        }
        
        .bubble-tiny {
            width: 3mm;
            height: 3mm;
            border: 1.5px solid black;
            border-radius: 50%;
        }
        
        .bubble-filled,
        .bubble-small.bubble-filled,
        .bubble-tiny.bubble-filled {
            background-color: black;
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

    </div>
</body>
</html>