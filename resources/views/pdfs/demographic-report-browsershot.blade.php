<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Informe Demográfico - {{ $organization->name }}</title>
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

        .charts-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 20px;
        }

        .chart-item {
            border: 1px solid #e5e7eb;
            border-radius: 5px;
            padding: 10px;
            background: #f9fafb;
        }

        .chart-item-title {
            font-size: 10pt;
            font-weight: bold;
            color: #1e40af;
            text-align: center;
            margin-bottom: 8px;
        }

        .chart-container {
            height: 200px;
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

        <div v-for="(section, sectionIndex) in sections" :key="sectionIndex" class="section">
            <h3 class="section-title">@{{ section.title }}</h3>

            <!-- NOM-035 Description for each demographic category -->
            <div class="section-description">
                <span v-html="getSectionDescription(section.title)"></span>
            </div>

            <!-- Individual charts grid - one chart per demographic value -->
            <div class="charts-grid">
                <div v-for="(item, itemIndex) in section.data" :key="itemIndex" class="chart-item">
                    <div class="chart-item-title">@{{ item.name }}</div>
                    <div class="chart-container">
                        <canvas :ref="el => setChartRef(sectionIndex + '-' + itemIndex, el)"></canvas>
                    </div>
                </div>
            </div>

            <!-- Data table -->
            <table>
                <thead>
                    <tr>
                        <th style="width: 25%;">@{{ section.title }}</th>
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
                    <tr v-for="item in section.data" :key="item.name">
                        <td style="text-align: left; font-weight: bold;">@{{ item.name }}</td>
                        <td><strong>@{{ item.total }}</strong></td>
                        <td>@{{ item.risk_levels['Nulo'] || 0 }}</td>
                        <td>@{{ item.risk_levels['Bajo'] || 0 }}</td>
                        <td>@{{ item.risk_levels['Medio'] || 0 }}</td>
                        <td>@{{ item.risk_levels['Alto'] || 0 }}</td>
                        <td>@{{ item.risk_levels['Muy Alto'] || 0 }}</td>
                        <td>@{{ (item.risk_levels['Nulo'] || 0) + (item.risk_levels['Bajo'] || 0) }}</td>
                        <td>@{{ (item.risk_levels['Medio'] || 0) + (item.risk_levels['Alto'] || 0) + (item.risk_levels['Muy Alto'] || 0) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="footer">
            <p>Informe Demográfico - {{ $organization->name }} | Generado el {{ $generatedDate }}</p>
            <p>Este documento es confidencial y de uso exclusivo para fines de la NOM-035-STPS-2018</p>
        </div>
    </div>

    <script>
        const { createApp } = Vue;

        // Register ChartDataLabels plugin globally
        Chart.register(ChartDataLabels);

        const demographicData = @json($demographicData);

        createApp({
            data() {
                return {
                    sections: demographicData,
                    chartRefs: {},
                    charts: []
                }
            },
            methods: {
                setChartRef(key, el) {
                    if (el) {
                        this.chartRefs[key] = el;
                    }
                },
                getSectionDescription(title) {
                    const descriptions = {
                        'Sexo': '<strong>Importancia según NOM-035:</strong> El análisis por sexo permite identificar si existen diferencias en la exposición a factores de riesgo psicosocial entre hombres y mujeres, lo cual es fundamental para diseñar intervenciones específicas y promover la igualdad de género en el ambiente laboral.',
                        
                        'Estado Civil': '<strong>Importancia según NOM-035:</strong> El estado civil puede influir en la percepción de factores como la interferencia trabajo-familia y el equilibrio de vida laboral. Identificar patrones ayuda a implementar políticas de conciliación que beneficien a todos los trabajadores.',
                        
                        'Nivel de Estudios': '<strong>Importancia según NOM-035:</strong> El nivel educativo puede relacionarse con el tipo de actividades, autonomía y exigencias laborales. Este análisis permite ajustar programas de capacitación y desarrollo acorde a las necesidades y capacidades del personal.',
                        
                        'Tipo de Puesto': '<strong>Importancia según NOM-035:</strong> Los distintos tipos de puesto (operativo, técnico, supervisor, gerencial) tienen exposición diferenciada a factores de riesgo como carga de trabajo, autonomía y liderazgo. Identificar estos patrones es clave para intervenciones dirigidas por nivel jerárquico.',
                        
                        'Tipo de Contratación': '<strong>Importancia según NOM-035:</strong> El tipo de contratación influye directamente en la percepción de inestabilidad laboral, sentido de pertenencia y factores del entorno organizacional. La NOM-035 reconoce la importancia de atender estas diferencias para garantizar condiciones laborales dignas.',
                        
                        'Tipo de Jornada': '<strong>Importancia según NOM-035:</strong> Las diferentes jornadas laborales (diurna, nocturna, mixta) tienen impacto directo en la salud física y mental de los trabajadores. El análisis permite detectar riesgos asociados a turnos específicos y diseñar medidas preventivas adecuadas.'
                    };
                    
                    return descriptions[title] || '<strong>Importancia según NOM-035:</strong> Este factor demográfico permite identificar patrones de riesgo psicosocial específicos para diferentes grupos de trabajadores, facilitando intervenciones focalizadas y efectivas.';
                },
                createIndividualChart(key, itemName, itemData) {
                    const canvas = this.chartRefs[key];
                    if (!canvas) return;

                    const ctx = canvas.getContext('2d');
                    
                    // Prepare data for single item chart
                    const riskLevels = ['Nulo', 'Bajo', 'Medio', 'Alto', 'Muy Alto'];
                    const colors = ['#00CED1', '#28A745', '#FFFF00', '#FFA500', '#FF0000'];
                    const data = riskLevels.map(level => itemData.risk_levels[level] || 0);

                    const chart = new Chart(ctx, {
                        type: 'bar',
                        data: {
                            labels: riskLevels,
                            datasets: [{
                                label: 'Cantidad',
                                data: data,
                                backgroundColor: colors,
                                borderColor: colors,
                                borderWidth: 1
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    display: false
                                },
                                tooltip: {
                                    callbacks: {
                                        title: function(context) {
                                            return itemName + ' - ' + context[0].label;
                                        }
                                    }
                                },
                                datalabels: {
                                    display: true,
                                    color: function(context) {
                                        const bgColor = context.dataset.backgroundColor[context.dataIndex];
                                        return bgColor === '#FFFF00' || bgColor === '#FFA500' ? '#000000' : '#FFFFFF';
                                    },
                                    font: {
                                        weight: 'bold',
                                        size: 11
                                    },
                                    formatter: function(value) {
                                        return value > 0 ? value : '';
                                    },
                                    anchor: 'end',
                                    align: 'top'
                                }
                            },
                            scales: {
                                x: {
                                    ticks: {
                                        font: {
                                            size: 9
                                        }
                                    }
                                },
                                y: {
                                    beginAtZero: true,
                                    ticks: {
                                        precision: 0,
                                        font: {
                                            size: 9
                                        }
                                    }
                                }
                            }
                        }
                    });

                    this.charts.push(chart);
                }
            },
            mounted() {
                // Wait for next tick to ensure refs are available
                this.$nextTick(() => {
                    // Small delay to ensure DOM is fully ready for Browsershot
                    setTimeout(() => {
                        this.sections.forEach((section, sectionIndex) => {
                            section.data.forEach((item, itemIndex) => {
                                const key = sectionIndex + '-' + itemIndex;
                                this.createIndividualChart(key, item.name, item);
                            });
                        });
                    }, 500);
                });
            }
        }).mount('#app');
    </script>
</body>
</html>
