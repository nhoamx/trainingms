<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Informe de Resultados Diagnóstico - {{ $organization->name }}</title>
    <script src="https://cdn.jsdelivr.net/npm/vue@3.5.13/dist/vue.global.prod.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.2.0/dist/chartjs-plugin-datalabels.min.js"></script>
    <style>
        @page {
            margin: 5mm 5mm;
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
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #d1d5db;
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
            border-left: 4px solid #6b7280;
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
            margin-bottom: 8px;
            padding-bottom: 5px;
            border-bottom: 2px solid #e5e7eb;
        }

        .section-description {
            font-size: 9pt;
            color: #555;
            background: #f3f4f6;
            padding: 10px;
            border-left: 3px solid #6b7280;
            margin-bottom: 15px;
            line-height: 1.5;
            border-radius: 3px;
        }

        .section-description strong {
            color: #1e40af;
        }

        .introduction {
            background: #fef3c7;
            padding: 12px;
            margin-bottom: 20px;
            border-left: 4px solid #f59e0b;
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

        .chart-container {
            height: 300px;
            position: relative;
            margin-bottom: 20px;
        }

        .charts-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 20px;
        }

        .chart-item {
            background: white;
            padding: 10px;
            border: 1px solid #e5e7eb;
            border-radius: 5px;
        }

        .chart-item h4 {
            font-size: 10pt;
            color: #1e40af;
            margin-bottom: 8px;
            text-align: center;
        }

        .chart-item-canvas {
            height: 250px;
            position: relative;
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

        .risk-nulo { background-color: #06b6d4 !important; color: white; }
        .risk-bajo { background-color: #22c55e !important; color: white; }
        .risk-medio { background-color: #facc15 !important; color: black; }
        .risk-alto { background-color: #f97316 !important; color: white; }
        .risk-muy-alto { background-color: #ef4444 !important; color: white; }

        .footer {
            margin-top: 20px;
            text-align: center;
            font-size: 8pt;
            color: #666;
            padding-top: 10px;
            border-top: 1px solid #e5e7eb;
        }

        .page-break {
            page-break-after: always;
        }
    </style>
</head>
<body>
    <div id="app">
        <div class="header">
            <h1>INFORME DE RESULTADOS DIAGNÓSTICO</h1>
            <h2>{{ $organization->name }}</h2>
            <p class="subtitle">NOM-035-STPS-2018</p>
            <p class="subtitle">Factores de Riesgo Psicosocial en el Trabajo</p>
        </div>

        <div class="organization-info">
            <h3>I.- Datos del Centro de Trabajo</h3>
            
            <table style="width: 100%; border-collapse: collapse; margin-bottom: 15px;">
                <tr>
                    <td style="width: 30%; padding: 4px 0; vertical-align: top;"><strong>Razón Social</strong></td>
                    <td style="width: 70%; padding: 4px 0; vertical-align: top;">{{ $organization->razon_social ?? $organization->name }}</td>
                </tr>
                @if($organization->nombre)
                <tr>
                    <td style="padding: 4px 0; vertical-align: top;"><strong>Nombre</strong></td>
                    <td style="padding: 4px 0; vertical-align: top;">{{ $organization->nombre }}</td>
                </tr>
                @endif
                @if($organization->rfc)
                <tr>
                    <td style="padding: 4px 0; vertical-align: top;"><strong>RFC</strong></td>
                    <td style="padding: 4px 0; vertical-align: top;">{{ $organization->rfc }}</td>
                </tr>
                @endif
                @if($organization->registro_patronal)
                <tr>
                    <td style="padding: 4px 0; vertical-align: top;"><strong>Registro Patronal</strong></td>
                    <td style="padding: 4px 0; vertical-align: top;">{{ $organization->registro_patronal }}</td>
                </tr>
                @endif
            </table>

            @if($organization->calle_numero || $organization->colonia || $organization->municipio || $organization->estado)
            <h4 style="font-size: 11pt; color: #1e40af; margin: 12px 0 8px 0;">Domicilio</h4>
            <table style="width: 100%; border-collapse: collapse; margin-bottom: 15px;">
                @if($organization->calle_numero)
                <tr>
                    <td style="width: 30%; padding: 4px 0; vertical-align: top;"><strong>Calle y No.</strong></td>
                    <td style="width: 70%; padding: 4px 0; vertical-align: top;">{{ $organization->calle_numero }}@if($organization->codigo_postal) C.P. {{ $organization->codigo_postal }}@endif</td>
                </tr>
                @endif
                @if($organization->colonia)
                <tr>
                    <td style="padding: 4px 0; vertical-align: top;"><strong>Colonia</strong></td>
                    <td style="padding: 4px 0; vertical-align: top;">{{ $organization->colonia }}</td>
                </tr>
                @endif
                @if($organization->municipio)
                <tr>
                    <td style="padding: 4px 0; vertical-align: top;"><strong>Municipio</strong></td>
                    <td style="padding: 4px 0; vertical-align: top;">{{ $organization->municipio }}</td>
                </tr>
                @endif
                @if($organization->estado)
                <tr>
                    <td style="padding: 4px 0; vertical-align: top;"><strong>Estado</strong></td>
                    <td style="padding: 4px 0; vertical-align: top;">{{ $organization->estado }}</td>
                </tr>
                @endif
            </table>
            @endif

            @if($organization->contacto_nombre || $organization->contacto_nombre_2)
            <h4 style="font-size: 11pt; color: #1e40af; margin: 12px 0 8px 0;">Contacto</h4>
            <table style="width: 100%; border-collapse: collapse; margin-bottom: 15px;">
                @if($organization->contacto_nombre)
                <tr>
                    <td style="width: 30%; padding: 4px 0; vertical-align: top;"><strong>Nombre</strong></td>
                    <td style="width: 70%; padding: 4px 0; vertical-align: top;">{{ $organization->contacto_nombre }}</td>
                </tr>
                @endif
                @if($organization->contacto_puesto)
                <tr>
                    <td style="padding: 4px 0; vertical-align: top;"><strong>Puesto</strong></td>
                    <td style="padding: 4px 0; vertical-align: top;">{{ $organization->contacto_puesto }}</td>
                </tr>
                @endif
                @if($organization->contacto_email)
                <tr>
                    <td style="padding: 4px 0; vertical-align: top;"><strong>Correo electrónico</strong></td>
                    <td style="padding: 4px 0; vertical-align: top;">{{ $organization->contacto_email }}</td>
                </tr>
                @endif
                @if($organization->contacto_telefono)
                <tr>
                    <td style="padding: 4px 0; vertical-align: top;"><strong>Móvil</strong></td>
                    <td style="padding: 4px 0; vertical-align: top;">{{ $organization->contacto_telefono }}</td>
                </tr>
                @endif
            </table>
            @endif

            @if($organization->contacto_nombre_2)
            <h4 style="font-size: 11pt; color: #1e40af; margin: 12px 0 8px 0;">Contacto</h4>
            <table style="width: 100%; border-collapse: collapse; margin-bottom: 15px;">
                <tr>
                    <td style="width: 30%; padding: 4px 0; vertical-align: top;"><strong>Nombre</strong></td>
                    <td style="width: 70%; padding: 4px 0; vertical-align: top;">{{ $organization->contacto_nombre_2 }}</td>
                </tr>
                @if($organization->contacto_puesto_2)
                <tr>
                    <td style="padding: 4px 0; vertical-align: top;"><strong>Puesto</strong></td>
                    <td style="padding: 4px 0; vertical-align: top;">{{ $organization->contacto_puesto_2 }}</td>
                </tr>
                @endif
                @if($organization->contacto_email_2)
                <tr>
                    <td style="padding: 4px 0; vertical-align: top;"><strong>Correo electrónico</strong></td>
                    <td style="padding: 4px 0; vertical-align: top;">{{ $organization->contacto_email_2 }}</td>
                </tr>
                @endif
                @if($organization->contacto_telefono_2)
                <tr>
                    <td style="padding: 4px 0; vertical-align: top;"><strong>Móvil</strong></td>
                    <td style="padding: 4px 0; vertical-align: top;">{{ $organization->contacto_telefono_2 }}</td>
                </tr>
                @endif
            </table>
            @endif

            @if($organization->actividad_principal)
            <h4 style="font-size: 11pt; color: #1e40af; margin: 12px 0 8px 0;">Actividad Principal</h4>
            <p style="margin-bottom: 15px;">{{ $organization->actividad_principal }}</p>
            @endif
        </div>

        @php
            // Calculate gender distribution from demographic data (Referencia V)
            $totalParticipants = $diagnosticData['total_participants'] ?? 0;
            $genderData = collect($demographicData ?? [])->firstWhere('title', 'Distribución Conforme a Género');
            
            $hombres = 0;
            $mujeres = 0;
            $gne = 0;
            
            if ($genderData && isset($genderData['data'])) {
                foreach ($genderData['data'] as $item) {
                    $gender = $item['name'] ?? '';
                    $count = $item['total'] ?? 0;
                    
                    if (stripos($gender, 'Hombre') !== false || stripos($gender, 'Masculino') !== false) {
                        $hombres += $count;
                    } elseif (stripos($gender, 'Mujer') !== false || stripos($gender, 'Femenino') !== false) {
                        $mujeres += $count;
                    } else {
                        $gne += $count;
                    }
                }
            }
        @endphp

        @if($totalParticipants > 0)
        <div class="section">
            <h3 class="section-title">Colaboradores</h3>
            <table>
                <thead>
                    <tr>
                        <th style="width: 50%; background-color: #dbeafe;">Concepto</th>
                        <th style="width: 16.66%; background-color: #dbeafe;">Total</th>
                        <th style="width: 16.66%; background-color: #dbeafe;">Hombres</th>
                        <th style="width: 16.66%; background-color: #dbeafe;">Mujeres</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td style="text-align: left;">Número de trabajadores que se les aplicó el cuestionario / Muestra</td>
                        <td><strong>{{ $totalParticipants }}</strong></td>
                        <td>{{ $hombres }}</td>
                        <td>{{ $mujeres }}</td>
                    </tr>
                    @if($organization->comite_integrantes)
                    <tr>
                        <td style="text-align: left;">Integrantes del comité de atención y seguimiento a factores de riesgos psicosociales</td>
                        <td><strong>{{ $organization->comite_integrantes }}</strong></td>
                        <td>{{ $organization->comite_hombres ?? 0 }}</td>
                        <td>{{ $organization->comite_mujeres ?? 0 }}</td>
                    </tr>
                    @endif
                    @if($organization->fecha_aplicacion)
                    <tr>
                        <td colspan="4" style="text-align: left; padding-top: 10px;">
                            <strong>Fecha de aplicación de las guías de referencia I / III / V</strong>
                            <span style="float: right; padding-right: 20px;">{{ \Carbon\Carbon::parse($organization->fecha_aplicacion)->locale('es')->isoFormat('MMMM YYYY') }}</span>
                        </td>
                    </tr>
                    @endif
                </tbody>
            </table>
        </div>
        @endif

        @include('pdfs.sections.objetivo')

        @include('pdfs.sections.introduccion')

        @include('pdfs.sections.marco-juridico')

        @include('pdfs.sections.metodo-utilizado')

        @include('pdfs.sections.resultados-header')

        @include('pdfs.sections.participantes')

        @include('pdfs.sections.acontecimientos-traumaticos')

        @include('pdfs.sections.violencia-laboral')

        @include('pdfs.sections.entorno-organizacional')

        @include('pdfs.sections.calificacion-final')

        @include('pdfs.sections.cuantificacion-categorias')

        @include('pdfs.sections.cuantificacion-dominios')

        @include('pdfs.sections.cuantificacion-dimensiones')

        @include('pdfs.sections.cuantificacion-respuestas')
    @include('pdfs.sections.cuantificacion-bloque-preguntas')

        @include('pdfs.sections.conclusiones')

        @include('pdfs.sections.recomendaciones')

        <div class="footer">
            <p>Informe de Resultados Diagnóstico - {{ $organization->name }} | Generado el {{ $generatedDate }}</p>
            <p>Este documento es confidencial y de uso exclusivo para fines de la NOM-035-STPS-2018</p>
        </div>
    </div>

    <script>
        const { createApp } = Vue;

        // Register ChartDataLabels plugin globally
        Chart.register(ChartDataLabels);

        const diagnosticData = @json($diagnosticData);

        createApp({
            data() {
                return {
                    riskLevels: ['Nulo', 'Bajo', 'Medio', 'Alto', 'Muy Alto'],
                    riskColors: ['#06b6d4', '#22c55e', '#facc15', '#f97316', '#ef4444'],
                    finalRisk: diagnosticData.final_risk || {},
                    categories: diagnosticData.categories || {},
                    domains: diagnosticData.domains || {},
                    dimensions: diagnosticData.dimensions || {},
                    blocks: diagnosticData.blocks || {},
                    totalParticipants: diagnosticData.total_participants || 0,
                    charts: []
                }
            },
            computed: {
                hasCategories() {
                    return Object.keys(this.categories).length > 0;
                },
                hasDomains() {
                    return Object.keys(this.domains).length > 0;
                },
                hasDimensions() {
                    return Object.keys(this.dimensions).length > 0;
                }
            },
            methods: {
                getPercentage(value, total) {
                    if (!total || total === 0) return '0.0';
                    return ((value / total) * 100).toFixed(1);
                },
                createFinalRiskChart() {
                    const canvas = document.getElementById('finalRiskChart');
                    if (!canvas) return;

                    const ctx = canvas.getContext('2d');
                    const data = this.riskLevels.map(level => this.finalRisk[level] || 0);

                    const chart = new Chart(ctx, {
                        type: 'bar',
                        data: {
                            labels: this.riskLevels,
                            datasets: [{
                                label: 'Participantes',
                                data: data,
                                backgroundColor: this.riskColors,
                                borderColor: this.riskColors,
                                borderWidth: 1
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: { display: false },
                                title: {
                                    display: true,
                                    text: 'Distribución de Riesgo Final',
                                    font: { size: 14, weight: 'bold' },
                                    color: '#1e40af'
                                },
                                datalabels: {
                                    display: true,
                                    color: (context) => {
                                        const bgColor = context.dataset.backgroundColor[context.dataIndex];
                                        return bgColor === '#FFFF00' || bgColor === '#FFA500' ? '#000000' : '#FFFFFF';
                                    },
                                    font: { weight: 'bold', size: 12 },
                                    formatter: (value) => value > 0 ? value : '',
                                    anchor: 'end',
                                    align: 'top'
                                }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    ticks: { precision: 0 }
                                }
                            }
                        }
                    });

                    this.charts.push(chart);
                },
                createEntornoOrganizacionalCharts() {
                    // Dominios específicos de entorno organizacional
                    const entornoDomains = [
                        'Carga de trabajo',
                        'Falta de control sobre el trabajo',
                        'Jornada de trabajo',
                        'Liderazgo',
                        'Reconocimiento del desempeño',
                        'Insuficiente sentido de pertenencia e inestabilidad'
                    ];
                    
                    entornoDomains.forEach(domainName => {
                        if (this.domains && this.domains[domainName]) {
                            const canvasId = 'entornoChart_' + domainName.replace(/ /g, '_');
                            const canvas = document.getElementById(canvasId);
                            if (!canvas) return;

                            const ctx = canvas.getContext('2d');
                            const levels = this.domains[domainName];
                            const data = this.riskLevels.map(level => levels[level] || 0);

                            const chart = new Chart(ctx, {
                                type: 'bar',
                                data: {
                                    labels: this.riskLevels,
                                    datasets: [{
                                        label: 'Participantes',
                                        data: data,
                                        backgroundColor: this.riskColors,
                                        borderColor: this.riskColors,
                                        borderWidth: 1
                                    }]
                                },
                                options: {
                                    responsive: true,
                                    maintainAspectRatio: false,
                                    plugins: {
                                        legend: { display: false },
                                        title: { display: false },
                                        datalabels: {
                                            display: true,
                                            color: (context) => {
                                                const bgColor = context.dataset.backgroundColor[context.dataIndex];
                                                return bgColor === '#facc15' || bgColor === '#f97316' ? '#000000' : '#FFFFFF';
                                            },
                                            font: { weight: 'bold', size: 11 },
                                            formatter: (value) => value > 0 ? value : '',
                                            anchor: 'end',
                                            align: 'top'
                                        }
                                    },
                                    scales: {
                                        y: {
                                            beginAtZero: true,
                                            ticks: { 
                                                precision: 0,
                                                font: { size: 9 }
                                            }
                                        },
                                        x: {
                                            ticks: {
                                                font: { size: 9 }
                                            }
                                        }
                                    }
                                }
                            });

                            this.charts.push(chart);
                        }
                    });
                },
                createIndividualChart(name, levels, refPrefix) {
                    const canvasId = refPrefix + name.replace(/ /g, '_');
                    const canvas = document.getElementById(canvasId);
                    if (!canvas) return;

                    const ctx = canvas.getContext('2d');
                    const data = this.riskLevels.map(level => levels[level] || 0);

                    const chart = new Chart(ctx, {
                        type: 'bar',
                        data: {
                            labels: this.riskLevels,
                            datasets: [{
                                label: 'Participantes',
                                data: data,
                                backgroundColor: this.riskColors,
                                borderColor: this.riskColors,
                                borderWidth: 1
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: { display: false },
                                title: { display: false },
                                datalabels: {
                                    display: true,
                                    color: (context) => {
                                        const bgColor = context.dataset.backgroundColor[context.dataIndex];
                                        return bgColor === '#FFFF00' || bgColor === '#FFA500' ? '#000000' : '#FFFFFF';
                                    },
                                    font: { weight: 'bold', size: 11 },
                                    formatter: (value) => value > 0 ? value : '',
                                    anchor: 'end',
                                    align: 'top'
                                }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    ticks: { 
                                        precision: 0,
                                        font: { size: 9 }
                                    }
                                },
                                x: {
                                    ticks: {
                                        font: { size: 9 }
                                    }
                                }
                            }
                        }
                    });

                    this.charts.push(chart);
                },
                createViolencePieChart() {
                    const canvas = document.getElementById('violencePieChart');
                    if (!canvas) return;

                    const ctx = canvas.getContext('2d');
                    
                    // Extract violence domain data
                    let violenceData = [0, 0, 0, 0, 0];
                    if (this.domains && this.domains['Violencia']) {
                        violenceData = this.riskLevels.map(level => this.domains['Violencia'][level] || 0);
                    }

                    const chart = new Chart(ctx, {
                        type: 'pie',
                        data: {
                            labels: this.riskLevels,
                            datasets: [{
                                data: violenceData,
                                backgroundColor: this.riskColors,
                                borderColor: '#ffffff',
                                borderWidth: 2
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    display: true,
                                    position: 'bottom',
                                    labels: {
                                        font: { size: 11 },
                                        padding: 15,
                                        generateLabels: (chart) => {
                                            const data = chart.data;
                                            if (data.labels.length && data.datasets.length) {
                                                return data.labels.map((label, i) => {
                                                    const value = data.datasets[0].data[i];
                                                    const total = data.datasets[0].data.reduce((a, b) => a + b, 0);
                                                    const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                                                    return {
                                                        text: `${label}: ${value} (${percentage}%)`,
                                                        fillStyle: data.datasets[0].backgroundColor[i],
                                                        hidden: false,
                                                        index: i
                                                    };
                                                });
                                            }
                                            return [];
                                        }
                                    }
                                },
                                title: {
                                    display: true,
                                    text: 'Distribución de Riesgo - Dominio Violencia',
                                    font: { size: 14, weight: 'bold' },
                                    color: '#1e40af',
                                    padding: { top: 10, bottom: 20 }
                                },
                                datalabels: {
                                    display: true,
                                    color: (context) => {
                                        const bgColor = context.dataset.backgroundColor[context.dataIndex];
                                        return bgColor === '#FFFF00' || bgColor === '#FFA500' ? '#000000' : '#FFFFFF';
                                    },
                                    font: { weight: 'bold', size: 12 },
                                    formatter: (value, context) => {
                                        if (value === 0) return '';
                                        const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                        const percentage = ((value / total) * 100).toFixed(1);
                                        return `${value}\n(${percentage}%)`;
                                    }
                                }
                            }
                        }
                    });

                    this.charts.push(chart);
                },
                createBlockPieCharts() {
                    const colors = ['#065f46', '#a7f3d0'];
                    if (!this.blocks || Object.keys(this.blocks).length === 0) return;

                    Object.entries(this.blocks).forEach(([blockNo, data]) => {
                        const canvas = document.getElementById('blockChart_' + blockNo);
                        if (!canvas) return;
                        const ctx = canvas.getContext('2d');
                        const obtained = data.obtained || 0;
                        const max = data.max || 0;
                        const remaining = Math.max(0, max - obtained);

                        const chart = new Chart(ctx, {
                            type: 'pie',
                            data: {
                                labels: ['Obtenido', 'No obtenido'],
                                datasets: [{
                                    data: [obtained, remaining],
                                    backgroundColor: colors,
                                    borderColor: '#ffffff',
                                    borderWidth: 2
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: {
                                    legend: {
                                        display: true,
                                        position: 'bottom',
                                        labels: {
                                            font: { size: 10 },
                                            padding: 12,
                                            generateLabels: (chart) => {
                                                const dataSet = chart.data.datasets[0];
                                                const labels = chart.data.labels;
                                                const total = dataSet.data.reduce((a,b)=>a+b,0);
                                                return labels.map((label, i) => {
                                                    const value = dataSet.data[i];
                                                    const pct = total > 0 ? ((value/total)*100).toFixed(1) : 0;
                                                    return { text: `${label}: ${value} (${pct}%)`, fillStyle: dataSet.backgroundColor[i], hidden: false, index: i };
                                                });
                                            }
                                        }
                                    },
                                    datalabels: {
                                        display: true,
                                        color: (context) => {
                                            const bg = context.dataset.backgroundColor[context.dataIndex];
                                            return bg === '#a7f3d0' ? '#065f46' : '#FFFFFF';
                                        },
                                        font: { weight: 'bold', size: 11 },
                                        formatter: (value, context) => {
                                            if (value === 0) return '';
                                            const total = context.dataset.data.reduce((a,b)=>a+b,0);
                                            const pct = total > 0 ? ((value/total)*100).toFixed(1) : 0;
                                            return `${value}\n(${pct}%)`;
                                        }
                                    }
                                }
                            }
                        });
                        this.charts.push(chart);
                    });
                }
            },
            mounted() {
                this.$nextTick(() => {
                    setTimeout(() => {
                        this.createFinalRiskChart();
                        this.createViolencePieChart();
                        this.createBlockPieCharts();
                        this.createEntornoOrganizacionalCharts();
                        
                        if (this.hasCategories) {
                            Object.entries(this.categories).forEach(([name, levels]) => {
                                this.createIndividualChart(name, levels, 'categoryChart_');
                            });
                        }
                        
                        if (this.hasDomains) {
                            Object.entries(this.domains).forEach(([name, levels]) => {
                                this.createIndividualChart(name, levels, 'domainChart_');
                            });
                        }
                        
                        if (this.hasDimensions) {
                            Object.entries(this.dimensions).forEach(([name, levels]) => {
                                this.createIndividualChart(name, levels, 'dimensionChart_');
                            });
                        }
                    }, 500);
                });
            }
        }).mount('#app');
    </script>
</body>
</html>
