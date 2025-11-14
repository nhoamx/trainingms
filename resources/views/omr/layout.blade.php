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
            width: 215.9mm;  /* US Letter width */
            min-height: 279.4mm;  /* US Letter height */
            margin: 0 auto;
            background: white;
            padding: 8mm 10mm;
            position: relative;
            page-break-after: always;
        }
        
        .page:last-child {
            page-break-after: auto;
        }

        /* Marcadores de alineación - Optimizados para detección OMR */
        .alignment-marker {
            position: absolute;
            width: 8mm;
            height: 8mm;
            background: black;
            border-radius: 0;
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
            position: relative;
            text-align: center;
            margin-bottom: 8px;
            border-bottom: 2px solid black;
            padding-bottom: 5px;
            min-height: 18mm;
        }

        .header .header-logo {
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 32mm; /* reserved space for logo */
            padding: 2mm 0;
        }

        .header .header-logo img.org-logo {
            max-width: 30mm;
            max-height: 14mm;
            object-fit: contain;
        }

        .header h1 {
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 3px;
            line-height: 1.1;
        }

        .header h2 {
            font-size: 12px;
            font-weight: bold;
            margin-bottom: 3px;
            line-height: 1.1;
        }

        .header p {
            font-size: 9px;
            margin-bottom: 2px;
        }

        /* Estilos de folio y fecha movidos a plantillas individuales */

        /* Contenido principal */
        .content {
            margin-top: 6mm;
            clear: both;
        }

        /* Burbujas para respuestas */
        .bubble {
            width: 3.5mm;
            height: 3.5mm;
            border: 1px solid black;
            border-radius: 50%;
            display: inline-block;
            margin: 0 1.5mm 0 0.5mm;
            vertical-align: middle;
        }
        
        .bubble-small {
            width: 2.5mm;
            height: 2.5mm;
            border: 1px solid black;
            border-radius: 50%;
        }
        
        .bubble-tiny {
            width: 2mm;
            height: 2mm;
            border: 1px solid black;
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
            margin-bottom: 3mm;
            min-height: 5mm;
        }

        .question-number {
            width: 8mm;
            font-weight: bold;
            flex-shrink: 0;
            font-size: 9px;
        }

        .answer-options {
            flex: 1;
            display: flex;
            align-items: center;
            gap: 2mm;
        }

        .option-group {
            display: flex;
            align-items: center;
            gap: 0.5mm;
        }

        .option-label {
            font-size: 9px;
            margin-right: 0.5mm;
        }

        /* Secciones específicas */
        .section-title {
            font-size: 14px;
            font-weight: bold;
            margin: 10mm 0 5mm 0;
            text-align: center;
            padding: 2mm;
            border: 1px solid black;
        }

        .instructions {
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
        <!-- Marcadores de alineación en las 4 esquinas -->
        <div class="alignment-marker marker-top-left"></div>
        <div class="alignment-marker marker-top-right"></div>
        <div class="alignment-marker marker-bottom-left"></div>
        <div class="alignment-marker marker-bottom-right"></div>

        <!-- Encabezado -->
        <div class="header">
            @hasSection('header-logo')
                <div class="header-logo">
                    @yield('header-logo')
                </div>
            @endif
            @section('nom-header')
                <h1>IDENTIFICACIÓN Y ANÁLISIS DE LOS FACTORES DE RIESGO PSICOSOCIAL</h1>
                <h2>Y EVALUACIÓN DEL ENTORNO ORGANIZACIONAL EN LOS CENTROS DE TRABAJO</h2>
                <p>NOM-035-STPS-2018</p>
            @show
            <h2>@yield('guide-title')</h2>
        </div>

        <!-- Contenido específico de cada guía -->
        <div class="content">
            @yield('content')
        </div>

    </div>
</body>
</html>