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
            margin-bottom: 8px;
            padding-bottom: 5px;
            border-bottom: 2px solid #e5e7eb;
        }

        .section-description {
            font-size: 9pt;
            color: #555;
            background: #eff6ff;
            padding: 10px;
            border-left: 3px solid #3b82f6;
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

        .risk-nulo { background-color: #00CED1 !important; color: white; }
        .risk-bajo { background-color: #28A745 !important; color: white; }
        .risk-medio { background-color: #FFFF00 !important; color: black; }
        .risk-alto { background-color: #FFA500 !important; color: black; }
        .risk-muy-alto { background-color: #FF0000 !important; color: white; }

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

        <!-- Final Risk Section -->
        <div class="section">
            <h3 class="section-title">V.5.1.- Calificación Final</h3>
            
            <div class="section-description">
                <strong>Importancia según NOM-035:</strong> La calificación final representa el nivel de riesgo psicosocial global del centro de trabajo, considerando todos los factores evaluados. Niveles Medio, Alto o Muy Alto requieren acciones preventivas y correctivas inmediatas según lo establece la norma.
            </div>

            <div class="chart-container">
                <canvas ref="finalRiskChart"></canvas>
            </div>

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
                    <tr v-for="level in riskLevels" :key="level">
                        <td :class="'risk-' + level.toLowerCase().replace(' ', '-')" style="text-align: left; font-weight: bold;">@{{ level }}</td>
                        <td><strong>@{{ finalRisk[level] || 0 }}</strong></td>
                        <td>@{{ getPercentage(finalRisk[level] || 0, totalParticipants) }}%</td>
                    </tr>
                    <tr style="background: #e5e7eb;">
                        <td style="text-align: left; font-weight: bold;">TOTAL</td>
                        <td><strong>@{{ totalParticipants }}</strong></td>
                        <td><strong>100%</strong></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="page-break"></div>

        <!-- Categories Section -->
        <div class="section">
            <h3 class="section-title">V.5.2.- Cuantificación por Categoría</h3>
            
            <div class="section-description">
                <strong>Importancia según NOM-035:</strong> Las categorías agrupan los factores de riesgo en cinco grandes áreas: Ambiente de Trabajo, Factores Propios de la Actividad, Organización del Tiempo, Liderazgo y Relaciones, y Entorno Organizacional. Permiten identificar áreas críticas para intervención prioritaria.
            </div>

            <div class="charts-grid" v-if="hasCategories">
                <div class="chart-item" v-for="(levels, name) in categories" :key="'cat-' + name">
                    <h4>@{{ name }}</h4>
                    <div class="chart-item-canvas">
                        <canvas :ref="'categoryChart_' + name"></canvas>
                    </div>
                </div>
            </div>
            <p v-else style="color: #666; font-style: italic;">No hay datos de categorías disponibles.</p>
        </div>

        <!-- Domains Section -->
        <div class="section">
            <h3 class="section-title">V.5.3.- Cuantificación por Dominio</h3>
            
            <div class="section-description">
                <strong>Importancia según NOM-035:</strong> Los dominios son conjuntos específicos de condiciones psicosociales evaluadas (condiciones ambientales, carga de trabajo, falta de control, jornada, interferencia trabajo-familia, liderazgo, relaciones, violencia, reconocimiento, sentido de pertenencia). Identifican factores concretos que requieren atención.
            </div>

            <div class="charts-grid" v-if="hasDomains">
                <div class="chart-item" v-for="(levels, name) in domains" :key="'dom-' + name">
                    <h4>@{{ name }}</h4>
                    <div class="chart-item-canvas">
                        <canvas :ref="'domainChart_' + name"></canvas>
                    </div>
                </div>
            </div>
            <p v-else style="color: #666; font-style: italic;">No hay datos de dominios disponibles.</p>
        </div>

        <div class="page-break"></div>

        <!-- Dimensions Section -->
        <div class="section">
            <h3 class="section-title">V.5.4.- Cuantificación por Dimensión</h3>
            
            <div class="section-description">
                <strong>Importancia según NOM-035:</strong> Las dimensiones representan los aspectos más específicos y detallados de cada dominio. Permiten un análisis granular de las condiciones de trabajo y orientan intervenciones puntuales y efectivas según el numeral 8.2 de la NOM-035.
            </div>

            <div class="charts-grid" v-if="hasDimensions">
                <div class="chart-item" v-for="(levels, name) in dimensions" :key="'dim-' + name">
                    <h4>@{{ name }}</h4>
                    <div class="chart-item-canvas">
                        <canvas :ref="'dimensionChart_' + name"></canvas>
                    </div>
                </div>
            </div>
            <p v-else style="color: #666; font-style: italic;">No hay datos de dimensiones disponibles.</p>
        </div>

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
                    riskColors: ['#00CED1', '#28A745', '#FFFF00', '#FFA500', '#FF0000'],
                    finalRisk: diagnosticData.final_risk || {},
                    categories: diagnosticData.categories || {},
                    domains: diagnosticData.domains || {},
                    dimensions: diagnosticData.dimensions || {},
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
                    const canvas = this.$refs.finalRiskChart;
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
                createIndividualChart(name, levels, refPrefix) {
                    const canvasArray = this.$refs[refPrefix + name];
                    if (!canvasArray || canvasArray.length === 0) return;
                    
                    const canvas = canvasArray[0];
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
                }
            },
            mounted() {
                this.$nextTick(() => {
                    setTimeout(() => {
                        this.createFinalRiskChart();
                        
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
