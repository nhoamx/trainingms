<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Informe de Resultados Diagnóstico - {{ $organization->name }}</title>
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

        .summary-stats {
            display: table;
            width: 100%;
            margin: 15px 0;
            background: #f0f9ff;
            border-radius: 5px;
        }

        .stat-box {
            display: table-cell;
            text-align: center;
            padding: 10px;
            vertical-align: middle;
        }

        .stat-box .value {
            font-size: 18pt;
            font-weight: bold;
            color: #2563eb;
            display: block;
        }

        .stat-box .label {
            font-size: 9pt;
            color: #666;
            display: block;
        }

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

        .introduction {
            background: #fef3c7;
            padding: 12px;
            margin-bottom: 20px;
            border-left: 4px solid #F8FF03;
            border-radius: 5px;
        }

        .introduction h3 {
            font-size: 11pt;
            color: #92400e;
            margin-bottom: 5px;
        }

        .introduction p {
            font-size: 9pt;
            color: #78350f;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>INFORME DE RESULTADOS DIAGNÓSTICO</h1>
        <h2>{{ $organization->name }}</h2>
        <p class="subtitle">NOM-035-STPS-2018</p>
        <p class="subtitle">Factores de Riesgo Psicosocial en el Trabajo</p>
    </div>

    <div class="organization-info">
        <h3>I.- Datos del Centro de Trabajo</h3>
        <p><strong>Razón Social:</strong> {{ $organization->razon_social ?? $organization->name }}</p>
        @if($organization->nombre)
        <p><strong>Nombre:</strong> {{ $organization->nombre }}</p>
        @endif
        @if($organization->rfc)
        <p><strong>RFC:</strong> {{ $organization->rfc }}</p>
        @endif
        @if($organization->registro_patronal)
        <p><strong>Registro Patronal:</strong> {{ $organization->registro_patronal }}</p>
        @endif
        @if($organization->calle_numero)
        <p><strong>Domicilio:</strong> {{ $organization->calle_numero }}</p>
        @endif
        @if($organization->colonia)
        <p><strong>Colonia:</strong> {{ $organization->colonia }} C.P. {{ $organization->codigo_postal ?? '' }}</p>
        @endif
        @if($organization->municipio && $organization->estado)
        <p><strong>Municipio:</strong> {{ $organization->municipio }} <strong>Estado:</strong> {{ $organization->estado }}</p>
        @endif
        @if($organization->actividad_principal)
        <p><strong>Actividad Principal:</strong> {{ $organization->actividad_principal }}</p>
        @endif
        <p><strong>Fecha de Generación:</strong> {{ $generatedDate }}</p>
    </div>

    @if($organization->total_trabajadores)
    <div class="section">
        <h3 class="section-title">Colaboradores</h3>
        <table>
            <thead>
                <tr>
                    <th style="width: 50%;">Concepto</th>
                    <th style="width: 16.66%;">Total</th>
                    <th style="width: 16.66%;">Hombres</th>
                    <th style="width: 16.66%;">Mujeres</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td style="text-align: left; font-weight: bold;">Número total de trabajadores</td>
                    <td><strong>{{ $organization->total_trabajadores ?? 0 }}</strong></td>
                    <td>{{ $organization->total_hombres ?? 0 }}</td>
                    <td>{{ $organization->total_mujeres ?? 0 }}</td>
                </tr>
                <tr>
                    <td style="text-align: left; font-weight: bold;">Número de trabajadores evaluados / Muestra</td>
                    <td><strong>{{ $organization->muestra_aplicada ?? $diagnosticData['total_participants'] ?? 0 }}</strong></td>
                    <td>{{ $organization->muestra_hombres ?? 0 }}</td>
                    <td>{{ $organization->muestra_mujeres ?? 0 }}</td>
                </tr>
                @if($organization->comite_integrantes)
                <tr>
                    <td style="text-align: left; font-weight: bold;">Integrantes del comité de atención y seguimiento</td>
                    <td><strong>{{ $organization->comite_integrantes }}</strong></td>
                    <td>{{ $organization->comite_hombres ?? 0 }}</td>
                    <td>{{ $organization->comite_mujeres ?? 0 }}</td>
                </tr>
                @endif
            </tbody>
        </table>
        @if($organization->fecha_aplicacion)
        <p style="margin-top: 10px;"><strong>Fecha de aplicación:</strong> {{ $organization->fecha_aplicacion->format('F Y') }}</p>
        @endif
    </div>
    @endif

    <div class="introduction">
        <h3>II.- Objetivo</h3>
        <p>Identificar, analizar y prevenir los factores de riesgo psicosocial, así como promover un entorno organizacional favorable en el centro de trabajo, conforme a la NOM-035-STPS-2018.</p>
    </div>

    <div class="section">
        <h3 class="section-title">V.5.1.- Calificación Final</h3>
        <p style="margin-bottom: 10px;">Distribución de participantes según nivel de riesgo final:</p>
        
        <table>
            <thead>
                <tr>
                    <th style="width: 40%;">Nivel de Riesgo</th>
                    <th style="width: 30%;">Cantidad</th>
                    <th style="width: 30%;">Porcentaje</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $total = array_sum($diagnosticData['final_risk']);
                @endphp
                @foreach(['Nulo', 'Bajo', 'Medio', 'Alto', 'Muy Alto'] as $level)
                <tr>
                    <td class="risk-{{ strtolower(str_replace(' ', '-', $level)) }}" style="text-align: left; font-weight: bold;">{{ $level }}</td>
                    <td><strong>{{ $diagnosticData['final_risk'][$level] ?? 0 }}</strong></td>
                    <td>{{ $total > 0 ? number_format(($diagnosticData['final_risk'][$level] ?? 0) / $total * 100, 1) : 0 }}%</td>
                </tr>
                @endforeach
                <tr style="background: #e5e7eb;">
                    <td style="text-align: left; font-weight: bold;">TOTAL</td>
                    <td><strong>{{ $total }}</strong></td>
                    <td><strong>100%</strong></td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="page-break"></div>

    <div class="section">
        <h3 class="section-title">V.5.2.- Cuantificación por Categoría</h3>
        @if(!empty($diagnosticData['categories']))
        <table>
            <thead>
                <tr>
                    <th style="width: 30%;">Categoría</th>
                    <th class="risk-nulo" style="width: 14%;">Nulo</th>
                    <th class="risk-bajo" style="width: 14%;">Bajo</th>
                    <th class="risk-medio" style="width: 14%;">Medio</th>
                    <th class="risk-alto" style="width: 14%;">Alto</th>
                    <th class="risk-muy-alto" style="width: 14%;">Muy Alto</th>
                </tr>
            </thead>
            <tbody>
                @foreach($diagnosticData['categories'] as $categoryName => $levels)
                <tr>
                    <td style="text-align: left; font-weight: bold;">{{ $categoryName }}</td>
                    <td>{{ $levels['Nulo'] ?? 0 }}</td>
                    <td>{{ $levels['Bajo'] ?? 0 }}</td>
                    <td>{{ $levels['Medio'] ?? 0 }}</td>
                    <td>{{ $levels['Alto'] ?? 0 }}</td>
                    <td>{{ $levels['Muy Alto'] ?? 0 }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @else
        <p style="color: #666; font-style: italic;">No hay datos de categorías disponibles.</p>
        @endif
    </div>

    <div class="section">
        <h3 class="section-title">V.5.3.- Cuantificación por Dominio</h3>
        @if(!empty($diagnosticData['domains']))
        <table>
            <thead>
                <tr>
                    <th style="width: 30%;">Dominio</th>
                    <th class="risk-nulo" style="width: 14%;">Nulo</th>
                    <th class="risk-bajo" style="width: 14%;">Bajo</th>
                    <th class="risk-medio" style="width: 14%;">Medio</th>
                    <th class="risk-alto" style="width: 14%;">Alto</th>
                    <th class="risk-muy-alto" style="width: 14%;">Muy Alto</th>
                </tr>
            </thead>
            <tbody>
                @foreach($diagnosticData['domains'] as $domainName => $levels)
                <tr>
                    <td style="text-align: left; font-weight: bold;">{{ $domainName }}</td>
                    <td>{{ $levels['Nulo'] ?? 0 }}</td>
                    <td>{{ $levels['Bajo'] ?? 0 }}</td>
                    <td>{{ $levels['Medio'] ?? 0 }}</td>
                    <td>{{ $levels['Alto'] ?? 0 }}</td>
                    <td>{{ $levels['Muy Alto'] ?? 0 }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @else
        <p style="color: #666; font-style: italic;">No hay datos de dominios disponibles.</p>
        @endif
    </div>

    <div class="page-break"></div>

    <div class="section">
        <h3 class="section-title">V.5.4.- Cuantificación por Dimensión</h3>
        @if(!empty($diagnosticData['dimensions']))
        <table>
            <thead>
                <tr>
                    <th style="width: 30%;">Dimensión</th>
                    <th class="risk-nulo" style="width: 14%;">Nulo</th>
                    <th class="risk-bajo" style="width: 14%;">Bajo</th>
                    <th class="risk-medio" style="width: 14%;">Medio</th>
                    <th class="risk-alto" style="width: 14%;">Alto</th>
                    <th class="risk-muy-alto" style="width: 14%;">Muy Alto</th>
                </tr>
            </thead>
            <tbody>
                @foreach($diagnosticData['dimensions'] as $dimensionName => $levels)
                <tr>
                    <td style="text-align: left; font-weight: bold;">{{ $dimensionName }}</td>
                    <td>{{ $levels['Nulo'] ?? 0 }}</td>
                    <td>{{ $levels['Bajo'] ?? 0 }}</td>
                    <td>{{ $levels['Medio'] ?? 0 }}</td>
                    <td>{{ $levels['Alto'] ?? 0 }}</td>
                    <td>{{ $levels['Muy Alto'] ?? 0 }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @else
        <p style="color: #666; font-style: italic;">No hay datos de dimensiones disponibles.</p>
        @endif
    </div>

    <div class="footer">
        <p>Informe de Resultados Diagnóstico - {{ $organization->name }} | Generado el {{ $generatedDate }}</p>
        <p>Este documento es confidencial y de uso exclusivo para fines de la NOM-035-STPS-2018</p>
    </div>
</body>
</html>
