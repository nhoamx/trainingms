@php
    // Extract organizational environment domains from diagnosticData
    // The data is now in format: domains['DomainName'] = ['Nulo' => count, 'Bajo' => count, ...]
    $relevantDomains = [
        'Carga de trabajo',
        'Falta de control sobre el trabajo',
        'Jornada de trabajo',
        'Liderazgo',
        'Reconocimiento del desempeño',
        'Insuficiente sentido de pertenencia e inestabilidad'
    ];
    
    $allRelevantDomainsData = [];
    
    foreach ($relevantDomains as $domainName) {
        if (isset($diagnosticData['domains'][$domainName])) {
            $levels = $diagnosticData['domains'][$domainName];
            
            // Calculate "Atención" (Medio + Alto + Muy Alto)
            $levels['Atención'] = ($levels['Medio'] ?? 0) + ($levels['Alto'] ?? 0) + ($levels['Muy Alto'] ?? 0);
            
            $allRelevantDomainsData[$domainName] = $levels;
        }
    }
@endphp

<div class="section">
    <h2 class="section-title">5.4 ENTORNO ORGANIZACIONAL</h2>
    
    <div class="section-content">
        <p style="margin-bottom: 12px;">
            Para determinar el nivel de riesgo, se consideró: el sentido de pertenencia de los trabajadores al centro de trabajo; la formación para la adecuada realización de las tareas encomendadas; la definición precisa de responsabilidades para los trabajadores; la participación proactiva y comunicación entre el patrón, sus representantes y los trabajadores; la distribución adecuada de cargas de trabajo, con jornadas de trabajo regulares, y la evaluación y el reconocimiento del desempeño, obteniendo la siguiente distribución:
        </p>

        <table>
            <thead>
                <tr>
                    <th style="text-align: left; width: 30%;">Descripción</th>
                    <th style="width: 12%;">Nulo</th>
                    <th style="width: 12%;">Bajo</th>
                    <th style="width: 12%;">Medio</th>
                    <th style="width: 12%;">Alto</th>
                    <th style="width: 10%;">Muy Alto</th>
                    <th style="width: 12%;">Atención</th>
                </tr>
            </thead>
            <tbody>
                @foreach($allRelevantDomainsData as $domainName => $levels)
                <tr>
                    <td style="text-align: left;">{{ $domainName }}</td>
                    <td>{{ $levels['Nulo'] }}</td>
                    <td>{{ $levels['Bajo'] }}</td>
                    <td>{{ $levels['Medio'] }}</td>
                    <td>{{ $levels['Alto'] }}</td>
                    <td>{{ $levels['Muy Alto'] }}</td>
                    <td><strong>{{ $levels['Atención'] }}</strong></td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <h4 style="font-size: 10pt; color: #1e40af; margin: 20px 0 10px 0; font-weight: bold;">
            Distribución de Niveles de Riesgo por Dominio
        </h4>
        
        <div class="charts-grid-entorno">
            @foreach($allRelevantDomainsData as $domainName => $levels)
            @php
                $canvasId = 'entornoChart_' . str_replace(' ', '_', $domainName);
                $total = array_sum(array_filter($levels, fn($key) => $key !== 'Atención', ARRAY_FILTER_USE_KEY));
            @endphp
            <div class="chart-item-entorno">
                <h4 style="font-size: 9pt; margin-bottom: 8px;">{{ $domainName }}</h4>
                <div class="chart-item-canvas-entorno">
                    <canvas id="{{ $canvasId }}"></canvas>
                </div>
                <table style="margin-top: 10px; font-size: 8pt; width: 100%;">
                    <thead>
                        <tr>
                            <th style="font-size: 8pt;">Nivel</th>
                            <th style="font-size: 8pt;">N°</th>
                            <th style="font-size: 8pt;">%</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach(['Nulo', 'Bajo', 'Medio', 'Alto', 'Muy Alto'] as $level)
                        @php
                            $count = $levels[$level] ?? 0;
                            $percentage = $total > 0 ? round(($count / $total) * 100, 1) : 0;
                            $riskClass = 'risk-' . strtolower(str_replace(' ', '-', $level));
                        @endphp
                        <tr>
                            <td class="{{ $riskClass }}" style="text-align: left; font-weight: bold; font-size: 8pt;">{{ $level }}</td>
                            <td style="font-size: 8pt;">{{ $count }}</td>
                            <td style="font-size: 8pt;">{{ $percentage }}%</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endforeach
        </div>
    </div>
</div>

<style>
    .section-content {
        font-size: 9.5pt;
        line-height: 1.6;
        color: #333;
    }

    .section-content p {
        text-align: justify;
    }

    .section-content table {
        font-size: 9pt;
    }

    .charts-grid-entorno {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 20px;
        margin-top: 15px;
    }

    .chart-item-entorno {
        break-inside: avoid;
        page-break-inside: avoid;
        background: #ffffff;
        padding: 12px;
        border: 1px solid #e5e7eb;
        border-radius: 4px;
    }

    .chart-item-entorno h4 {
        color: #1e40af;
        font-weight: bold;
        text-align: center;
        margin: 0 0 8px 0;
    }

    .chart-item-canvas-entorno {
        width: 100%;
        height: 200px;
        margin-bottom: 10px;
    }

    .chart-item-entorno table {
        width: 100%;
        margin-top: 10px;
        border-collapse: collapse;
        font-size: 8pt;
    }

    .chart-item-entorno table th {
        background-color: #f3f4f6;
        font-weight: bold;
        padding: 4px;
        text-align: center;
    }

    .chart-item-entorno table td {
        padding: 4px;
        text-align: center;
        border-top: 1px solid #e5e7eb;
    }

    .chart-item-entorno .risk-nulo {
        color: #059669;
    }

    .chart-item-entorno .risk-bajo {
        color: #FFFF00;
    }

    .chart-item-entorno .risk-medio {
        color: #FFA500;
    }

    .chart-item-entorno .risk-alto {
        color: #DC2626;
    }

    .chart-item-entorno .risk-muy-alto {
        color: #7F1D1D;
    }
</style>
