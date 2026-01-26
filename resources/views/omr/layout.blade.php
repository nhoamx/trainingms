<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title') - NOM-035-STPS-2018</title>
    <style>
        /* ============================================
           OMR MARKER SYSTEM - ASYMMETRIC PATTERN
           Prevents 180° rotation ambiguity
           
           ZONAS DE LA HOJA:
           1. 🟦 Zona de marcas: 0-5mm (solo escáner)
           2. 🟨 Zona de seguridad: 5-18mm (buffer vacío)
           3. 🟩 Zona de contenido: 18mm+ (texto/burbujas)
           ============================================ */
        
        :root {
            /* Marcadores OMR */
            --marker-offset: 5mm;           /* Distancia desde borde físico */
            --marker-large: 10mm;
            --marker-small: 7mm;
            --orientation-width: 14mm;
            --orientation-height: 4mm;
            
            /* Zonas de seguridad */
            --safe-zone-margin: 18mm;       /* Inicio de zona segura de contenido */
            --safe-zone-top: 20mm;          /* Margen superior aumentado (orientación + header) */
        }

        /* Ensure the PDF renderer uses exact Letter size with no outer margins */
        @page {
            size: 215.9mm 279.4mm; /* US Letter */
            margin: 0;
        }

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
            /* ZONA SEGURA DE CONTENIDO: Comienza a 18-20mm del borde
               - Marcas OMR: 5mm desde borde
               - Buffer de seguridad: 5mm adicionales
               - Contenido seguro: 18-20mm desde borde */
            padding: var(--safe-zone-top) var(--safe-zone-margin) var(--safe-zone-margin) var(--safe-zone-margin);
            position: relative;
        }

        /* ============================================
           MARCADORES DE ALINEACIÓN - PATRÓN ASIMÉTRICO
           🟦 ZONA DE MARCAS: Solo para el escáner OCR
           
           IMPORTANTE: Las marcas están en zona de escáner (5mm desde borde).
           El contenido NUNCA debe tocar esta zona.
           
           Sistema a prueba de rotación 180°:
           - TL: 10mm × 10mm (único grande)
           - TR: 7mm × 7mm (pequeño)
           - BL: 7mm × 7mm (pequeño)  
           - BR: AUSENTE (clave del patrón)
           - Orientación: 14mm × 4mm (rectángulo horizontal)
           ============================================ */
        
        /* Base para todos los marcadores */
        .omr-marker {
            position: absolute;
            background: #000;
            border-radius: 0;
            /* Los marcadores NO se ven afectados por el padding del .page
               porque usan position: absolute desde el borde del contenedor */
        }

        /* TOP LEFT - Marcador GRANDE (único) */
        .marker-top-left {
            top: var(--marker-offset);
            left: var(--marker-offset);
            width: var(--marker-large);
            height: var(--marker-large);
        }

        /* TOP RIGHT - Marcador pequeño */
        .marker-top-right {
            top: var(--marker-offset);
            right: var(--marker-offset);
            width: var(--marker-small);
            height: var(--marker-small);
        }

        /* BOTTOM LEFT - Marcador pequeño */
        .marker-bottom-left {
            bottom: var(--marker-offset);
            left: var(--marker-offset);
            width: var(--marker-small);
            height: var(--marker-small);
        }

        /* BOTTOM RIGHT - INTENCIONALMENTE AUSENTE
           Este marcador NO EXISTE para crear patrón asimétrico.
           Si detectas algo aquí, la hoja está rotada 180°. */

        /* MARCADOR DE ORIENTACIÓN - Rectángulo horizontal superior
           Referencia inequívoca: solo existe arriba en orientación correcta */
        .orientation-marker {
            position: absolute;
            top: var(--marker-offset);
            left: 50%;
            width: var(--orientation-width);
            height: var(--orientation-height);
            background: #000;
            transform: translateX(-50%);
        }

        /* Encabezado */
        .header {
            position: relative;
            text-align: center;
            margin-bottom: 8px;
            border-bottom: 2px solid black;
            padding-bottom: 5px;
            /* ✅ El header está en ZONA SEGURA (inicia a 20mm del borde superior)
               - No interfiere con marcas OMR (5mm)
               - No interfiere con marcador de orientación (5mm + 4mm height)
               - Buffer de seguridad: ~10mm entre orientación y contenido */
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

        /* Fecha debajo del header */
        .date-row {
            display: flex;
            gap: 3mm;
            align-items: center;
            justify-content: flex-end;
            font-size: 10px;
            font-weight: bold;
            margin-top: 4px;
            margin-bottom: 6mm;
        }

        .date-field {
            display: flex;
            align-items: center;
            gap: 1mm;
        }

        .date-field-label {
            font-size: 10px;
            font-weight: bold;
        }

        .date-field-line {
            width: 15mm;
            border-bottom: 2px solid black;
            height: 4mm;
        }

        /* Estilos de folio y fecha movidos a plantillas individuales */

        /* Contenido principal */
        .content {
            margin-top: 1mm;
            clear: both;
            /* ✅ Todo el contenido está en ZONA SEGURA
               - Preguntas, burbujas, texto: >18mm desde bordes laterales
               - Headers, títulos: >20mm desde borde superior
               - Footer, últimas preguntas: >18mm desde borde inferior */
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
        <!-- ============================================
             SISTEMA DE MARCADORES ASIMÉTRICO
             - 3 marcadores de esquina (TL grande, TR/BL pequeños)
             - 1 marcador de orientación (rectángulo horizontal)
             - SIN marcador bottom-right (patrón inequívoco)
             ============================================ -->
        <div class="omr-marker marker-top-left"></div>
        <div class="omr-marker marker-top-right"></div>
        <div class="omr-marker marker-bottom-left"></div>
        <div class="orientation-marker"></div>

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

        <!-- Fila de fecha colocada debajo del header (personalizable por plantilla) -->
        @section('date-row')
            <div class="date-row">
                <div class="date-field">
                    <span class="date-field-label">Día:</span>
                    <div class="date-field-line"></div>
                </div>
                <div class="date-field">
                    <span class="date-field-label">Mes:</span>
                    <div class="date-field-line"></div>
                </div>
                <div class="date-field">
                    <span class="date-field-label">Año:</span>
                    <div class="date-field-line"></div>
                </div>
            </div>
        @show

        <!-- Contenido específico de cada guía -->
        <div class="content">
            @yield('content')
        </div>

    </div>
</body>
</html>