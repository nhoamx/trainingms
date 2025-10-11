<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Informe Demográfico - {{ $organization->name }}</title>
    <style>
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
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 3px solid #2563eb;
        }

        .header h1 {
            font-size: 18pt;
            color: #1e40af;
            margin-bottom: 5px;
        }

        .header h2 {
            font-size: 14pt;
            color: #3b82f6;
            margin-bottom: 10px;
        }

        .header .subtitle {
            font-size: 9pt;
            color: #666;
        }

        .organization-info {
            background: #f8f9fa;
            padding: 12px;
            margin-bottom: 20px;
            border-radius: 5px;
            border-left: 4px solid #2563eb;
        }

        .organization-info h3 {
            font-size: 12pt;
            color: #1e40af;
            margin-bottom: 8px;
        }

        .organization-info p {
            font-size: 9pt;
            margin-bottom: 3px;
        }

        .section {
            margin-bottom: 30px;
            page-break-inside: avoid;
        }

        .section-title {
            font-size: 13pt;
            color: #1e40af;
            margin-bottom: 12px;
            padding-bottom: 5px;
            border-bottom: 2px solid #e5e7eb;
        }

        .chart-container {
            margin-bottom: 15px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            font-size: 9pt;
        }

        table thead {
            background: #2563eb;
            color: white;
        }

        table thead th {
            padding: 8px 5px;
            text-align: center;
            font-weight: bold;
            border: 1px solid #1e40af;
        }

        table tbody td {
            padding: 6px 5px;
            text-align: center;
            border: 1px solid #d1d5db;
        }

        table tbody tr:nth-child(even) {
            background: #f9fafb;
        }

        table tbody tr:hover {
            background: #e5e7eb;
        }

        .risk-nulo { background-color: #00CED1 !important; color: white; }
        .risk-bajo { background-color: #28A745 !important; color: white; }
        .risk-medio { background-color: #FFFF00 !important; color: black; }
        .risk-alto { background-color: #FFA500 !important; color: black; }
        .risk-muy-alto { background-color: #FF0000 !important; color: white; }

        .footer {
            position: fixed;
            bottom: 0;
            width: 100%;
            text-align: center;
            font-size: 8pt;
            color: #666;
            padding-top: 10px;
            border-top: 1px solid #e5e7eb;
        }

        .page-break {
            page-break-after: always;
        }

        .summary-stats {
            display: flex;
            justify-content: space-around;
            margin: 15px 0;
            padding: 10px;
            background: #f0f9ff;
            border-radius: 5px;
        }

        .stat-box {
            text-align: center;
        }

        .stat-box .value {
            font-size: 20pt;
            font-weight: bold;
            color: #2563eb;
        }

        .stat-box .label {
            font-size: 9pt;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>INFORME DEMOGRÁFICO</h1>
        <h2>{{ $organization->name }}</h2>
        <p class="subtitle">NOM-035-STPS-2018</p>
        <p class="subtitle">Factores de Riesgo Psicosocial en el Trabajo</p>
    </div>

    <div class="organization-info">
        <h3>Datos del Centro de Trabajo</h3>
        <p><strong>Razón Social:</strong> {{ $organization->razon_social ?? $organization->name }}</p>
        @if($organization->rfc)
        <p><strong>RFC:</strong> {{ $organization->rfc }}</p>
        @endif
        @if($organization->municipio && $organization->estado)
        <p><strong>Ubicación:</strong> {{ $organization->municipio }}, {{ $organization->estado }}</p>
        @endif
        <p><strong>Fecha de Generación:</strong> {{ $generatedDate }}</p>
    </div>

    @foreach($demographicData as $section)
    <div class="section">
        <h3 class="section-title">{{ $section['title'] }}</h3>

        <table>
            <thead>
                <tr>
                    <th style="width: 25%;">{{ $section['title'] }}</th>
                    <th style="width: 10%;">Total</th>
                    <th class="risk-nulo" style="width: 11%;">Nulo</th>
                    <th class="risk-bajo" style="width: 11%;">Bajo</th>
                    <th class="risk-medio" style="width: 11%;">Medio</th>
                    <th class="risk-alto" style="width: 11%;">Alto</th>
                    <th class="risk-muy-alto" style="width: 11%;">Muy Alto</th>
                    <th class="risk-bajo" style="width: 10%;">Nu+Ba</th>
                    <th class="risk-muy-alto" style="width: 10%;">Me+Al+MA</th>
                </tr>
            </thead>
            <tbody>
                @foreach($section['data'] as $item)
                <tr>
                    <td style="text-align: left; font-weight: bold;">{{ $item['name'] }}</td>
                    <td><strong>{{ $item['total'] }}</strong></td>
                    <td>{{ $item['risk_levels']['Nulo'] ?? 0 }}</td>
                    <td>{{ $item['risk_levels']['Bajo'] ?? 0 }}</td>
                    <td>{{ $item['risk_levels']['Medio'] ?? 0 }}</td>
                    <td>{{ $item['risk_levels']['Alto'] ?? 0 }}</td>
                    <td>{{ $item['risk_levels']['Muy Alto'] ?? 0 }}</td>
                    <td>{{ ($item['risk_levels']['Nulo'] ?? 0) + ($item['risk_levels']['Bajo'] ?? 0) }}</td>
                    <td>{{ ($item['risk_levels']['Medio'] ?? 0) + ($item['risk_levels']['Alto'] ?? 0) + ($item['risk_levels']['Muy Alto'] ?? 0) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endforeach

    <div class="footer">
        <p>Informe Demográfico - {{ $organization->name }} | Generado el {{ $generatedDate }}</p>
        <p>Este documento es confidencial y de uso exclusivo para fines de la NOM-035-STPS-2018</p>
    </div>
</body>
</html>
