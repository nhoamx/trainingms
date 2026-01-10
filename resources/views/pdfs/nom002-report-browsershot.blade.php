<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Revisión Mensual de Extintores - {{ $organization->name }}</title>
    <style>
        @page {
            size: letter;
            margin: 10mm 12mm;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', 'Helvetica Neue', Arial, sans-serif;
            font-size: 9pt;
            color: #1f2937;
            line-height: 1.4;
            width: 100%;
            max-width: 7.5in;
            margin: 0 auto;
            background: white;
            padding: 5mm;
        }

        /* Header Styles - Modern Design */
        .report-header {
            background: linear-gradient(135deg, #1e3a8a 0%, #1e40af 100%);
            padding: 12px 15px;
            border-radius: 6px;
            margin-bottom: 12px;
            color: white;
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
            page-break-after: avoid;
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 12px;
            flex: 1;
        }

        .logo-container {
            background: white;
            padding: 8px;
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            min-width: 70px;
            max-width: 70px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .logo-container img {
            max-width: 60px;
            max-height: 60px;
            object-fit: contain;
        }

        .header-info {
            flex: 1;
        }

        .org-name {
            font-size: 11pt;
            font-weight: 700;
            margin-bottom: 4px;
            letter-spacing: 0.3px;
        }

        .report-title {
            font-size: 13pt;
            font-weight: 800;
            margin-bottom: 4px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .report-subtitle {
            font-size: 7.5pt;
            opacity: 0.95;
            line-height: 1.3;
            max-width: 450px;
        }

        .header-metadata {
            background: rgba(255,255,255,0.15);
            padding: 10px;
            border-radius: 4px;
            min-width: 110px;
            backdrop-filter: blur(10px);
        }

        .metadata-item {
            margin-bottom: 5px;
            font-size: 8pt;
        }

        .metadata-item:last-child {
            margin-bottom: 0;
        }

        .metadata-label {
            font-weight: 600;
            opacity: 0.9;
            font-size: 7pt;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .metadata-value {
            font-weight: 700;
            font-size: 9pt;
            margin-top: 1px;
        }

        /* Stats Bar */
        .stats-bar {
            display: flex;
            gap: 10px;
            margin-bottom: 12px;
            padding: 10px;
            background: #f8fafc;
            border-radius: 6px;
            border-left: 3px solid #1e40af;
        }

        .stat-item {
            flex: 1;
            text-align: center;
            padding: 6px;
            background: white;
            border-radius: 4px;
            box-shadow: 0 1px 2px rgba(0,0,0,0.05);
        }

        .stat-value {
            font-size: 16pt;
            font-weight: 800;
            color: #1e40af;
            line-height: 1;
        }

        .stat-label {
            font-size: 7pt;
            color: #64748b;
            margin-top: 3px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 600;
        }

        /* Content sections */
        .content-section {
            margin-bottom: 15px;
        }

        .section-header {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 8px;
            padding-bottom: 6px;
            border-bottom: 2px solid #1e40af;
            page-break-after: avoid;
        }

        .section-icon {
            width: 20px;
            height: 20px;
            background: #1e40af;
            border-radius: 3px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 10pt;
        }

        .section-title {
            font-size: 11pt;
            font-weight: 700;
            color: #1e40af;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        /* Legend - Enhanced */
        .legend {
            display: flex;
            gap: 15px;
            margin-bottom: 8px;
            padding: 8px 12px;
            background: linear-gradient(to right, #f0f9ff, #e0f2fe);
            border-radius: 4px;
            border-left: 3px solid #0ea5e9;
            justify-content: center;
            page-break-after: avoid;
        }

        .legend-item {
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 8pt;
            font-weight: 600;
        }

        .legend-badge {
            width: 18px;
            height: 18px;
            border-radius: 3px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 11pt;
            font-weight: bold;
        }

        .legend-badge.success {
            background: #dcfce7;
            color: #16a34a;
        }

        .legend-badge.error {
            background: #fee2e2;
            color: #dc2626;
        }

        .legend-badge.neutral {
            background: #f1f5f9;
            color: #64748b;
        }

        /* Inspection Matrix Table - Enhanced */
        .matrix-table {
            width: 100%;
            border-collapse: collapse;
            border-spacing: 0;
            font-size: 7pt;
            border-radius: 4px;
            overflow: hidden;
            box-shadow: 0 1px 4px rgba(0,0,0,0.06);
        }

        .matrix-table th,
        .matrix-table td {
            border: 1px solid #e2e8f0;
            padding: 3px 2px;
            text-align: center;
            vertical-align: middle;
        }

        .matrix-table thead th {
            background: linear-gradient(135deg, #1e3a8a 0%, #1e40af 100%);
            color: white;
            font-weight: 700;
            font-size: 6.5pt;
            padding: 5px 2px;
            text-transform: uppercase;
            letter-spacing: 0.2px;
        }

        .matrix-table .subheader-row th {
            background: #3b82f6;
            font-size: 5.5pt;
            padding: 4px 1px;
        }

        .matrix-table tbody tr:nth-child(even) {
            background-color: #f8fafc;
        }

        .matrix-table tbody tr {
            page-break-inside: avoid;
        }

        .matrix-table .extinguisher-col {
            background: linear-gradient(to right, #eff6ff, #f0f9ff);
            font-weight: 700;
            text-align: left;
            padding-left: 6px;
            min-width: 70px;
            color: #1e40af;
            font-size: 7.5pt;
        }

        .matrix-table .location-col {
            text-align: left;
            padding-left: 5px;
            font-size: 6.5pt;
            color: #475569;
            max-width: 100px;
        }

        .matrix-table .date-col {
            font-size: 6.5pt;
            color: #64748b;
            font-weight: 600;
        }

        .check-cell {
            padding: 2px !important;
        }

        .check-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 14px;
            height: 14px;
            border-radius: 2px;
            font-size: 9pt;
            font-weight: bold;
        }

        .check-ok {
            background: #dcfce7;
            color: #16a34a;
        }

        .check-issue {
            background: #fee2e2;
            color: #dc2626;
        }

        .check-no-data {
            color: #94a3b8;
            font-size: 8pt;
            font-weight: 600;
        }

        /* Rotate header text */
        .rotate-text {
            writing-mode: vertical-rl;
            text-orientation: mixed;
            white-space: nowrap;
            max-height: 100px;
            font-size: 5.5pt;
            padding: 2px 1px;
        }

        /* Anomalies Section - Enhanced */
        .anomaly-card {
            margin-bottom: 8px;
            padding: 10px;
            background: white;
            border-radius: 4px;
            border-left: 3px solid #dc2626;
            box-shadow: 0 1px 2px rgba(0,0,0,0.06);
            page-break-inside: avoid;
        }

        .anomaly-header {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 5px;
        }

        .anomaly-icon {
            width: 22px;
            height: 22px;
            background: #fee2e2;
            border-radius: 3px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #dc2626;
            font-weight: bold;
            font-size: 11pt;
        }

        .anomaly-title {
            font-weight: 700;
            font-size: 8.5pt;
            color: #1f2937;
            flex: 1;
        }

        .anomaly-content {
            font-size: 8pt;
            color: #4b5563;
            line-height: 1.4;
            margin-bottom: 5px;
            padding-left: 30px;
        }

        .anomaly-meta {
            font-size: 7pt;
            color: #6b7280;
            padding-left: 30px;
            display: flex;
            gap: 12px;
        }

        .anomaly-meta-item {
            display: flex;
            align-items: center;
            gap: 3px;
        }

        .no-anomalies {
            text-align: center;
            padding: 15px;
            background: linear-gradient(to right, #dcfce7, #d1fae5);
            border-radius: 6px;
            border: 1px solid #86efac;
        }

        .no-anomalies-icon {
            font-size: 24pt;
            color: #16a34a;
            margin-bottom: 6px;
        }

        .no-anomalies-text {
            font-weight: 700;
            color: #166534;
            font-size: 9pt;
        }

        .empty-state {
            text-align: center;
            padding: 20px 15px;
            background: #f8fafc;
            border-radius: 6px;
            border: 1px dashed #cbd5e1;
        }

        .empty-state-icon {
            font-size: 28pt;
            color: #94a3b8;
            margin-bottom: 8px;
        }

        .empty-state-text {
            color: #64748b;
            font-size: 9pt;
            font-weight: 600;
        }

        /* Footer - Enhanced */
        .footer {
            margin-top: 15px;
            padding: 10px 15px;
            background: linear-gradient(to right, #f8fafc, #f1f5f9);
            border-top: 2px solid #1e40af;
            border-radius: 4px;
            font-size: 7pt;
            color: #64748b;
            page-break-inside: avoid;
        }

        .footer-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .footer-left {
            font-weight: 600;
        }

        .footer-right {
            text-align: right;
            font-size: 6.5pt;
        }

        /* Page break control */
        .avoid-break {
            page-break-inside: avoid;
        }

        .break-before {
            page-break-before: always;
        }

        .break-after {
            page-break-after: always;
        }

        /* Print optimization */
        @media print {
            body {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
                padding: 0;
            }
            
            .report-header {
                page-break-after: avoid;
            }
            
            .section-header {
                page-break-after: avoid;
            }
            
            .legend {
                page-break-after: avoid;
            }
            
            .matrix-table {
                page-break-before: avoid;
            }
            
            .matrix-table thead {
                display: table-header-group;
            }
            
            .matrix-table tbody tr {
                page-break-inside: avoid;
            }
            
            .anomaly-card {
                page-break-inside: avoid;
            }
            
            .footer {
                page-break-inside: avoid;
            }
        }
    </style>
</head>
<body>
    <div id="app">
        <!-- Modern Header -->
        <div class="report-header">
            <div class="header-content">
                <div class="header-left">
                    @if($organization->logo_path && file_exists(public_path('storage/' . $organization->logo_path)))
                        <div class="logo-container">
                            <img src="{{ public_path('storage/' . $organization->logo_path) }}" alt="{{ $organization->name }}">
                        </div>
                    @endif
                    <div class="header-info">
                        <div class="org-name">{{ $organization->name }}</div>
                        <div class="report-title">Revisión Mensual de Extintores</div>
                        <div class="report-subtitle">
                            Evaluación de conformidad NOM-002-STPS-2010 - Condiciones de seguridad, prevención y protección contra incendios
                        </div>
                    </div>
                </div>
                <div class="header-metadata">
                    <div class="metadata-item">
                        <div class="metadata-label">Emisión</div>
                        <div class="metadata-value">18/04/2023</div>
                    </div>
                    <div class="metadata-item">
                        <div class="metadata-label">Revisión</div>
                        <div class="metadata-value">1</div>
                    </div>
                    <div class="metadata-item">
                        <div class="metadata-label">Generado</div>
                        <div class="metadata-value">{{ now()->format('d/m/Y') }}</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Stats Bar -->
        @php
            $totalExtinguishers = $assets->count();
            $inspectedCount = $assets->filter(fn($a) => $a->inspections->first())->count();
            $anomaliesCount = $assets->filter(function($a) {
                $inspection = $a->inspections->first();
                return $inspection && !empty($inspection->anomalies_followup);
            })->count();
        @endphp
        
        <!-- Inspection Matrix Section -->
        <div class="content-section">
            <div class="section-header">
                <div class="section-icon">📋</div>
                <div class="section-title">Matriz de Inspección Mensual</div>
            </div>
            
            <!-- Enhanced Legend -->
            <div class="legend">
                <div class="legend-item">
                    <div class="legend-badge success">✓</div>
                    <span>Conforme (OK)</span>
                </div>
                <div class="legend-item">
                    <div class="legend-badge error">✗</div>
                    <span>No Conforme (Problema)</span>
                </div>
                <div class="legend-item">
                    <div class="legend-badge neutral">-</div>
                    <span>Sin inspección</span>
                </div>
            </div>
            
            <!-- Table or Empty State -->
            @if($assets->count() > 0)
                <table class="matrix-table">
                    <thead>
                        <tr class="header-row">
                            <th rowspan="2">Extintor</th>
                            <th rowspan="2">Ubicación</th>
                            <th rowspan="2">Última Inspección</th>
                            <th colspan="{{ count($checklist) }}">Puntos de Verificación NOM-002</th>
                        </tr>
                        <tr class="subheader-row">
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
                                <td class="location-col">{{ $asset->location }}</td>
                                <td class="date-col">
                                    @if($latestInspection)
                                        {{ $latestInspection->inspection_date->format('d/m/Y') }}
                                    @else
                                        <span class="check-no-data">-</span>
                                    @endif
                                </td>
                                
                                @foreach($checklist as $index => $procedure)
                                    <td class="check-cell">
                                        @if($latestInspection && isset($latestInspection->checklist_results[$index]))
                                            @php
                                                $result = $latestInspection->checklist_results[$index];
                                                $status = $result['status'] ?? 'ok';
                                            @endphp
                                            @if($status === 'ok')
                                                <span class="check-icon check-ok">✓</span>
                                            @else
                                                <span class="check-icon check-issue">✗</span>
                                            @endif
                                        @else
                                            <span class="check-no-data">-</span>
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <div class="empty-state">
                    <div class="empty-state-icon">🧯</div>
                    <div class="empty-state-text">No hay extintores registrados para esta organización</div>
                </div>
            @endif
        </div>
        
        <!-- Anomalías y Seguimiento Section -->
        @if($assets->count() > 0)
            <div class="content-section">
                <div class="section-header">
                    <div class="section-icon">⚠️</div>
                    <div class="section-title">Anomalías y Seguimiento</div>
                </div>
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
                        <div class="anomaly-card">
                            <div class="anomaly-header">
                                <div class="anomaly-icon">⚠</div>
                                <div class="anomaly-title">
                                    Extintor {{ $asset->consecutive_number }} - {{ $asset->location }}
                                </div>
                            </div>
                            <div class="anomaly-content">
                                {{ $latestInspection->anomalies_followup }}
                            </div>
                            <div class="anomaly-meta">
                                <div class="anomaly-meta-item">
                                    <strong>Inspector:</strong> {{ $latestInspection->inspector_name }}
                                </div>
                                <div class="anomaly-meta-item">
                                    <strong>Fecha:</strong> {{ $latestInspection->inspection_date->format('d/m/Y') }}
                                </div>
                            </div>
                        </div>
                    @endif
                @endforeach
                
                @if(!$hasAnomalies)
                    <div class="empty-state">
                        <div class="empty-state-icon">✅</div>
                        <div class="empty-state-text">No se han reportado anomalías en las inspecciones</div>
                    </div>
                @endif
            </div>
        @endif
        
        <!-- Enhanced Footer -->
        <div class="footer">
            <div class="footer-content">
                <div class="footer-left">
                    <div style="font-size: 9pt; margin-bottom: 3px;">📄 Documento generado el {{ $generatedDate }}</div>
                    <div>{{ $organization->name }}</div>
                </div>
                <div class="footer-right">
                    <div style="font-weight: 600; margin-bottom: 2px;">NOM-002-STPS-2010</div>
                    <div>Condiciones de Seguridad - Prevención y Protección contra Incendios</div>
                </div>
            </div>
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
