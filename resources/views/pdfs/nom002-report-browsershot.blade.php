<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Revisión Mensual de Extintores - {{ $organization->name }}</title>
    <script src="https://cdn.jsdelivr.net/npm/vue@3.5.13/dist/vue.global.prod.js"></script>
    <style>
        @page {
            size: letter; /* 8.5in x 11in (215.9mm x 279.4mm) */
            margin: 10mm 10mm;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Arial', sans-serif;
            font-size: 10pt;
            color: #333;
            line-height: 1.4;
            width: 8.5in;
            max-width: 8.5in;
        }

        /* Header Table Styles */
        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            border: 2px solid #000;
        }

        .header-table td {
            border: 1px solid #000;
            padding: 8px;
            vertical-align: middle;
        }

        /* Logo Column */
        .logo-column {
            width: 25%;
            text-align: center;
            padding: 15px 10px;
        }

        .logo-column img {
            max-width: 120px;
            max-height: 100px;
            margin-bottom: 10px;
        }

        .logo-column .org-name {
            font-size: 11pt;
            font-weight: bold;
            color: #1e40af;
            line-height: 1.3;
        }

        /* Title Column */
        .title-column {
            width: 55%;
            padding: 10px 15px;
        }

        .main-title {
            font-size: 14pt;
            font-weight: bold;
            color: #1e40af;
            text-align: center;
            margin-bottom: 5px;
        }

        .compliance-text {
            font-size: 9pt;
            text-align: justify;
            line-height: 1.4;
            color: #333;
        }

        /* Info Column */
        .info-column {
            width: 20%;
            text-align: center;
            padding: 10px;
        }

        .info-row {
            margin-bottom: 8px;
            font-size: 9pt;
        }

        .info-label {
            font-weight: bold;
            display: inline;
        }

        .info-value {
            display: inline;
        }

        /* Content sections */
        .content-section {
            margin-top: 20px;
        }

        .section-title {
            font-size: 12pt;
            font-weight: bold;
            color: #1e40af;
            margin-bottom: 10px;
            padding-bottom: 5px;
            border-bottom: 2px solid #e5e7eb;
        }

        /* Inspection Matrix Table */
        .matrix-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 8pt;
            margin-top: 15px;
        }

        .matrix-table th,
        .matrix-table td {
            border: 1px solid #000;
            padding: 4px;
            text-align: center;
            vertical-align: middle;
        }

        .matrix-table th {
            background-color: #f3f4f6;
            font-weight: bold;
            font-size: 7pt;
        }

        .matrix-table .header-row {
            background-color: #1e40af;
            color: white;
            font-weight: bold;
        }

        .matrix-table .extinguisher-col {
            background-color: #f8f9fa;
            font-weight: bold;
            text-align: left;
            padding-left: 8px;
            min-width: 120px;
        }

        .matrix-table .check-ok {
            color: #16a34a;
            font-size: 14pt;
            font-weight: bold;
        }

        .matrix-table .check-issue {
            color: #dc2626;
            font-size: 14pt;
            font-weight: bold;
        }

        .matrix-table .no-data {
            color: #9ca3af;
            font-size: 10pt;
        }

        /* Rotate header text for space */
        .rotate-text {
            writing-mode: vertical-rl;
            text-orientation: mixed;
            white-space: nowrap;
            max-height: 150px;
            font-size: 6pt;
            padding: 2px;
        }

        /* Legend */
        .legend {
            display: flex;
            gap: 20px;
            margin-top: 15px;
            font-size: 9pt;
            justify-content: center;
        }

        .legend-item {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .legend-symbol {
            font-size: 14pt;
            font-weight: bold;
        }

        /* Footer */
        .footer {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 1px solid #ccc;
            font-size: 8pt;
            color: #666;
            text-align: center;
        }
    </style>
</head>
<body>
    <div id="app">
        <!-- Header Table -->
        <table class="header-table">
            <tr>
                <!-- Logo/Organization Column -->
                <td class="logo-column" rowspan="3">
                    @if($organization->logo_path && file_exists(public_path('storage/' . $organization->logo_path)))
                        <img src="{{ public_path('storage/' . $organization->logo_path) }}" alt="{{ $organization->name }}">
                    @endif
                    <div class="org-name">{{ $organization->name }}</div>
                </td>
                
                <!-- Title Column -->
                <td class="title-column">
                    <div class="main-title">REVISIÓN MENSUAL DE EXTINTORES</div>
                </td>
                
                <!-- Info Column - Emisión -->
                <td class="info-column">
                    <div class="info-row">
                        <span class="info-label">Emisión:</span>
                        <span class="info-value">18/04/2023</span>
                    </div>
                </td>
            </tr>
            <tr>
                <!-- Compliance Text -->
                <td class="title-column">
                    <div class="compliance-text">
                        Evaluación de la conformidad NOM-002-STPS-2010 condiciones de seguridad, prevención y protección contra incendios en los centros de trabajo
                    </div>
                </td>
                
                <!-- Info Column - Revisión -->
                <td class="info-column">
                    <div class="info-row">
                        <span class="info-label">Revisión:</span>
                        <span class="info-value">1</span>
                    </div>
                </td>
            </tr>
        </table>

        <!-- Content will go here -->
        <div class="content-section">
            <div class="section-title">Matriz de Inspección Mensual de Extintores</div>
            
            <!-- Legend -->
            <div class="legend">
                <div class="legend-item">
                    <span class="legend-symbol" style="color: #16a34a;">✓</span>
                    <span>Conforme (OK)</span>
                </div>
                <div class="legend-item">
                    <span class="legend-symbol" style="color: #dc2626;">✗</span>
                    <span>No Conforme (Problema)</span>
                </div>
                <div class="legend-item">
                    <span class="legend-symbol" style="color: #9ca3af;">-</span>
                    <span>Sin inspección</span>
                </div>
            </div>

            @if($assets->count() > 0)
                <table class="matrix-table">
                    <thead>
                        <tr class="header-row">
                            <th rowspan="2">Extintor</th>
                            <th rowspan="2">Ubicación</th>
                            <th rowspan="2">Última Inspección</th>
                            <th colspan="{{ count($checklist) }}">Puntos de Verificación</th>
                        </tr>
                        <tr>
                            @foreach($checklist as $index => $procedure)
                                <th class="rotate-text">{{ $index }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($assets as $asset)
                            @php
                                $latestInspection = $asset->inspections->first();
                            @endphp
                            <tr>
                                <td class="extinguisher-col">{{ $asset->consecutive_number }}</td>
                                <td style="text-align: left; font-size: 7pt;">{{ $asset->location }}</td>
                                <td style="font-size: 7pt;">
                                    @if($latestInspection)
                                        {{ $latestInspection->inspection_date->format('d/m/Y') }}
                                    @else
                                        <span class="no-data">-</span>
                                    @endif
                                </td>
                                
                                @foreach($checklist as $index => $procedure)
                                    <td>
                                        @if($latestInspection && isset($latestInspection->checklist_results[$index]))
                                            @php
                                                $result = $latestInspection->checklist_results[$index];
                                                $status = $result['status'] ?? 'ok';
                                            @endphp
                                            @if($status === 'ok')
                                                <span class="check-ok">✓</span>
                                            @else
                                                <span class="check-issue">✗</span>
                                            @endif
                                        @else
                                            <span class="no-data">-</span>
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <p style="text-align: center; color: #666; padding: 20px;">
                    No hay extintores registrados para esta organización.
                </p>
            @endif
        </div>

        <!-- Anomalies Summary -->
        @if($assets->count() > 0)
            <div class="content-section">
                <div class="section-title">Resumen de Anomalías y Seguimiento</div>
                @php
                    $hasAnomalies = false;
                @endphp
                @foreach($assets as $asset)
                    @php
                        $latestInspection = $asset->inspections->first();
                    @endphp
                    @if($latestInspection && !empty($latestInspection->anomalies_followup))
                        @php
                            $hasAnomalies = true;
                        @endphp
                        <div style="margin-bottom: 15px; padding: 10px; background: #f8f9fa; border-left: 3px solid #dc2626;">
                            <p style="font-weight: bold; margin-bottom: 5px;">
                                Extintor {{ $asset->consecutive_number }} - {{ $asset->location }}
                            </p>
                            <p style="font-size: 9pt; color: #555;">
                                {{ $latestInspection->anomalies_followup }}
                            </p>
                            <p style="font-size: 8pt; color: #666; margin-top: 5px;">
                                Inspector: {{ $latestInspection->inspector_name }} | 
                                Fecha: {{ $latestInspection->inspection_date->format('d/m/Y') }}
                            </p>
                        </div>
                    @endif
                @endforeach
                @if(!$hasAnomalies)
                    <p style="text-align: center; color: #16a34a; padding: 15px; font-weight: bold;">
                        ✓ No se reportaron anomalías en las inspecciones más recientes
                    </p>
                @endif
            </div>
        @endif

        <!-- Footer -->
        <div class="footer">
            <p>Documento generado el {{ $generatedDate }}</p>
            <p>{{ $organization->name }} - Reporte NOM-002-STPS-2010</p>
        </div>
    </div>

    <script>
        const { createApp } = Vue;

        createApp({
            data() {
                return {
                    // Data for the report will go here
                }
            },
            mounted() {
                console.log('NOM-002 Report mounted');
            }
        }).mount('#app');
    </script>
</body>
</html>
