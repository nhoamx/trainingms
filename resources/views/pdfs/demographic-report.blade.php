<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Informe Demográfico - {{ $organization->name }}</title>
    <style>
        @page {
            /* Page margins for DomPDF */
            margin: 5mm 5mm;
            padding: 5mm 5mm;
        }

        * {
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

        /* Chart styles for demographic distributions */
        .chart-wrapper {
            margin: 15px 0 20px 0;
            page-break-inside: avoid;
        }

        .chart-title {
            font-size: 10pt;
            font-weight: bold;
            color: #1e40af;
            margin-bottom: 8px;
            text-align: center;
        }

        .bar-chart {
            width: 100%;
            margin-bottom: 5px;
        }

        .bar-row {
            display: table;
            width: 100%;
            margin-bottom: 8px;
            font-size: 9pt;
        }

        .bar-label {
            display: table-cell;
            width: 30%;
            padding-right: 10px;
            text-align: right;
            vertical-align: middle;
            font-weight: bold;
            color: #333;
        }

        .bar-container {
            display: table-cell;
            width: 60%;
            vertical-align: middle;
            position: relative;
            height: 24px;
        }

        .bar-segments {
            display: flex;
            width: 100%;
            height: 100%;
            border: 1px solid #d1d5db;
            border-radius: 3px;
            overflow: hidden;
        }

        .bar-segment {
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 8pt;
            font-weight: bold;
            color: white;
            text-shadow: 0 0 2px rgba(0,0,0,0.3);
        }

        .bar-segment.nulo { background-color: #00CED1; color: white; }
        .bar-segment.bajo { background-color: #28A745; color: white; }
        .bar-segment.medio { background-color: #FFFF00; color: black; text-shadow: none; }
        .bar-segment.alto { background-color: #FFA500; color: black; text-shadow: none; }
        .bar-segment.muy-alto { background-color: #FF0000; color: white; }

        .bar-total {
            display: table-cell;
            width: 10%;
            text-align: center;
            vertical-align: middle;
            font-weight: bold;
            color: #1e40af;
        }
    </style>
</head>
<body>
    @php
        $referenciaV = config('referencia_v');

        // Map section title to demographic key used in data storage
        $keyByTitle = [
            'Sexo' => 'sexo',
            'Estado Civil' => 'estado_civil',
            'Nivel de Estudios' => 'nivel_estudios',
            'Tipo de Puesto' => 'tipo_puesto',
            'Tipo de Contratación' => 'tipo_contratacion',
            'Tipo de Jornada' => 'tipo_jornada',
        ];

        // Build options list for a given demographic key from config/referencia_v.php
        $optionsForKey = function (string $key) use ($referenciaV) {
            // Some keys live under datos_laborales in config
            $root = $referenciaV[$key] ?? ($referenciaV['datos_laborales'][$key] ?? null);
            if ($root === null) {
                return [];
            }

            // nivel_estudios contains nested options (e.g., Primaria => [Terminada, Incompleta])
            if ($key === 'nivel_estudios' && is_array($root)) {
                $flat = [];
                foreach ($root as $label => $maybeChildren) {
                    if (is_array($maybeChildren)) {
                        foreach ($maybeChildren as $child) {
                            $flat[] = $label.' - '.$child;
                        }
                    } else {
                        // Plain string option
                        $flat[] = is_string($label) ? $label : (string) $maybeChildren;
                    }
                }
                return $flat;
            }

            // Simple flat arrays
            return is_array($root) ? array_values($root) : [];
        };

        // Simple slugify/normalize for matching values to config labels
        $slug = function (?string $text) {
            if (!is_string($text)) { return ''; }
            $normalized = str_replace(['_', '/'], [' ', ' '], $text);
            // remove accents if possible
            if (function_exists('iconv')) {
                $trans = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $normalized);
                if ($trans !== false) { $normalized = $trans; }
            }
            $normalized = strtolower($normalized);
            // keep letters/numbers/spaces/parentheses hyphen-friendly
            $normalized = preg_replace('/[^a-z0-9\s\-\(\)]+/i', ' ', $normalized);
            $normalized = preg_replace('/\s+/', ' ', trim($normalized));
            // collapse to dash-separated for compare
            return str_replace(' ', '-', $normalized);
        };

        // Map a raw stored value to a label from config for the given section title
        $mapLabel = function (string $sectionTitle, $raw) use ($keyByTitle, $optionsForKey, $slug) {
            // If raw is an array with label/value use label preference
            if (is_array($raw)) {
                $raw = $raw['label'] ?? $raw['value'] ?? '';
            }
            if (!is_string($raw)) {
                return $raw;
            }

            $key = $keyByTitle[$sectionTitle] ?? null;
            if (!$key) {
                return $raw; // fallback
            }

            $options = $optionsForKey($key);
            if (empty($options)) {
                return $raw; // fallback
            }

            // Direct match first
            foreach ($options as $opt) {
                if ($opt === $raw) {
                    return $opt;
                }
            }

            // Slug/normalized match (handles snake_case vs spaced, accents, parentheses)
            $rawSlug = $slug($raw);
            foreach ($options as $opt) {
                if ($slug($opt) === $rawSlug) {
                    return $opt;
                }
            }

            // Minor heuristics: remove parentheses content for matching
            $rawNoParens = trim(preg_replace('/\([^)]*\)/', '', $raw));
            $rawNoParensSlug = $slug($rawNoParens);
            foreach ($options as $opt) {
                $optNoParens = trim(preg_replace('/\([^)]*\)/', '', $opt));
                if ($slug($optNoParens) === $rawNoParensSlug) {
                    return $opt;
                }
            }

            // Fallback: beautify snake_case
            if (function_exists('mb_convert_case')) {
                return mb_convert_case(str_replace('_', ' ', $raw), MB_CASE_TITLE, 'UTF-8');
            }
            return ucwords(str_replace('_', ' ', $raw));
        };
    @endphp
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

        @php
            // Calculate totals for chart rendering
            $chartData = [];
            foreach ($section['data'] as $item) {
                $chartData[] = [
                    'name' => $mapLabel($section['title'], $item['name']),
                    'total' => $item['total'],
                    'nulo' => $item['risk_levels']['Nulo'] ?? 0,
                    'bajo' => $item['risk_levels']['Bajo'] ?? 0,
                    'medio' => $item['risk_levels']['Medio'] ?? 0,
                    'alto' => $item['risk_levels']['Alto'] ?? 0,
                    'muy_alto' => $item['risk_levels']['Muy Alto'] ?? 0,
                ];
            }

            // Calculate max value for scaling chart
            $maxTotal = 0;
            foreach ($chartData as $row) {
                if ($row['total'] > $maxTotal) {
                    $maxTotal = $row['total'];
                }
            }
            $maxTotal = $maxTotal > 0 ? $maxTotal : 1; // Avoid division by zero
        @endphp

        <!-- Chart visualization above table -->
        <div class="chart-wrapper">
            <div class="bar-chart">
                @foreach($chartData as $row)
                <div class="bar-row">
                    <div class="bar-label">{{ $row['name'] }}</div>
                    <div class="bar-container">
                        @if($row['total'] > 0)
                            <div class="bar-segments">
                                @if($row['nulo'] > 0)
                                    <div class="bar-segment nulo" style="width: {{ ($row['nulo'] / $maxTotal) * 100 }}%;">
                                        {{ $row['nulo'] }}
                                    </div>
                                @endif
                                @if($row['bajo'] > 0)
                                    <div class="bar-segment bajo" style="width: {{ ($row['bajo'] / $maxTotal) * 100 }}%;">
                                        {{ $row['bajo'] }}
                                    </div>
                                @endif
                                @if($row['medio'] > 0)
                                    <div class="bar-segment medio" style="width: {{ ($row['medio'] / $maxTotal) * 100 }}%;">
                                        {{ $row['medio'] }}
                                    </div>
                                @endif
                                @if($row['alto'] > 0)
                                    <div class="bar-segment alto" style="width: {{ ($row['alto'] / $maxTotal) * 100 }}%;">
                                        {{ $row['alto'] }}
                                    </div>
                                @endif
                                @if($row['muy_alto'] > 0)
                                    <div class="bar-segment muy-alto" style="width: {{ ($row['muy_alto'] / $maxTotal) * 100 }}%;">
                                        {{ $row['muy_alto'] }}
                                    </div>
                                @endif
                            </div>
                        @else
                            <div class="bar-segments" style="background: #f3f4f6;">
                                <div style="text-align: center; width: 100%; font-size: 8pt; color: #9ca3af;">Sin datos</div>
                            </div>
                        @endif
                    </div>
                    <div class="bar-total">{{ $row['total'] }}</div>
                </div>
                @endforeach
            </div>
        </div>

        <!-- Data table below chart -->
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
                    <td style="text-align: left; font-weight: bold;">{{ $mapLabel($section['title'], $item['name']) }}</td>
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
