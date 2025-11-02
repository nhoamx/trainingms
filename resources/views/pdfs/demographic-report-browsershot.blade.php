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

        .chart-item {
            border: 1px solid #e5e7eb;
            border-radius: 5px;
            padding: 15px;
            background: #f9fafb;
            margin-bottom: 15px;
        }

        .chart-container {
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

        .table-note {
            font-size: 8pt;
            color: #666;
            margin-top: 5px;
            font-style: italic;
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

            <!-- Single chart showing distribution by category -->
            <div class="chart-item" style="max-width: 800px; margin: 0 auto 20px;">
                <div class="chart-container" style="height: 300px;">
                    <canvas :ref="el => setChartRef('section-' + sectionIndex, el)"></canvas>
                </div>
            </div>

            <!-- Data table -->
            <table>
                <thead>
                    <tr>
                        <th style="width: 20%;">@{{ section.title }}</th>
                        <th style="width: 8%;">Total</th>
                        <th class="risk-nulo" style="width: 9%;">Nulo</th>
                        <th class="risk-bajo" style="width: 9%;">Bajo</th>
                        <th class="risk-medio" style="width: 9%;">Medio</th>
                        <th class="risk-alto" style="width: 9%;">Alto</th>
                        <th class="risk-muy-alto" style="width: 9%;">Muy Alto</th>
                        <th class="risk-bajo" style="width: 9%;">Nu+Ba</th>
                        <th class="risk-muy-alto" style="width: 9%;">Me+Al+MA</th>
                        <th style="width: 9%;">CF*</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="item in section.data" :key="item.name">
                        <td style="text-align: left; font-weight: bold;">@{{ normalizeLabel(item.name) }}</td>
                        <td><strong>@{{ item.total }}</strong></td>
                        <td>@{{ item.risk_levels['Nulo'] || 0 }}</td>
                        <td>@{{ item.risk_levels['Bajo'] || 0 }}</td>
                        <td>@{{ item.risk_levels['Medio'] || 0 }}</td>
                        <td>@{{ item.risk_levels['Alto'] || 0 }}</td>
                        <td>@{{ item.risk_levels['Muy Alto'] || 0 }}</td>
                        <td>@{{ (item.risk_levels['Nulo'] || 0) + (item.risk_levels['Bajo'] || 0) }}</td>
                        <td>@{{ (item.risk_levels['Medio'] || 0) + (item.risk_levels['Alto'] || 0) + (item.risk_levels['Muy Alto'] || 0) }}</td>
                        <td>@{{ calculateCF(item) }}</td>
                    </tr>
                </tbody>
            </table>
            
            <div class="table-note">
                <strong>CF*:</strong> Calificación Final - Porcentaje de trabajadores en niveles de riesgo Medio, Alto y Muy Alto (Me+Al+MA / Total × 100)
            </div>
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
                normalizeLabel(label) {
                    if (!label) return '';
                    
                    // Convert to string if not already
                    const text = String(label);
                    
                    // Replace underscores with spaces and capitalize each word
                    return text
                        .split('_')
                        .map(word => word.charAt(0).toUpperCase() + word.slice(1).toLowerCase())
                        .join(' ');
                },
                calculateCF(item) {
                    const total = item.total || 0;
                    if (total === 0) return 0;
                    
                    const meAlMa = (item.risk_levels['Medio'] || 0) + 
                                   (item.risk_levels['Alto'] || 0) + 
                                   (item.risk_levels['Muy Alto'] || 0);
                    
                    return Math.round((meAlMa / total) * 100);
                },
                getSectionDescription(title) {
                    const descriptions = {
                        'Distribución Conforme a Género': '<strong>Importancia según NOM-035:</strong> El análisis por género permite identificar si existen diferencias en la exposición a factores de riesgo psicosocial entre hombres y mujeres, lo cual es fundamental para diseñar intervenciones específicas y promover la igualdad de género en el ambiente laboral.',
                        
                        'Distribución Conforme a Estado Civil': '<strong>Importancia según NOM-035:</strong> El estado civil puede influir en la percepción de factores como la interferencia trabajo-familia y el equilibrio de vida laboral. Identificar patrones ayuda a implementar políticas de conciliación que beneficien a todos los trabajadores.',
                        
                        'Distribución Conforme a Rango de Edad': '<strong>Importancia según NOM-035:</strong> La edad de los trabajadores puede relacionarse con diferentes niveles de experiencia, adaptabilidad y necesidades en el entorno laboral. Este análisis permite diseñar intervenciones apropiadas para cada grupo etario.',
                        
                        'Distribución Conforme a Nivel de Estudios': '<strong>Importancia según NOM-035:</strong> El nivel educativo puede relacionarse con el tipo de actividades, autonomía y exigencias laborales. Este análisis permite ajustar programas de capacitación y desarrollo acorde a las necesidades y capacidades del personal.',
                        
                        'Distribución Conforme al Puesto': '<strong>Importancia según NOM-035:</strong> Los distintos puestos tienen responsabilidades y exigencias diferenciadas. Identificar patrones de riesgo por puesto es clave para intervenciones específicas y mejoras en las condiciones laborales.',
                        
                        'Distribución Conforme a Tipo de Contratación': '<strong>Importancia según NOM-035:</strong> El tipo de contratación influye directamente en la percepción de inestabilidad laboral, sentido de pertenencia y factores del entorno organizacional. La NOM-035 reconoce la importancia de atender estas diferencias para garantizar condiciones laborales dignas.',
                        
                        'Distribución Conforme a Tipo de Personal': '<strong>Importancia según NOM-035:</strong> La diferenciación entre personal sindicalizado y de confianza puede revelar diferencias en las condiciones laborales y exposición a riesgos psicosociales, permitiendo intervenciones equitativas.',
                        
                        'Distribución Conforme al Tipo de Jornada Laboral': '<strong>Importancia según NOM-035:</strong> Las diferentes jornadas laborales (diurna, nocturna, mixta) tienen impacto directo en la salud física y mental de los trabajadores. El análisis permite detectar riesgos asociados a jornadas específicas y diseñar medidas preventivas adecuadas.',
                        
                        'Distribución Conforme a Rotación de Turnos': '<strong>Importancia según NOM-035:</strong> La rotación de turnos puede afectar significativamente el bienestar, descanso y vida familiar de los trabajadores. Identificar su impacto en los niveles de riesgo permite implementar esquemas de rotación más saludables.',
                        
                        'Distribución Conforme al Área': '<strong>Importancia según NOM-035:</strong> Las diferentes áreas o departamentos pueden tener exposiciones distintas a factores de riesgo psicosocial según sus funciones y dinámicas específicas. Este análisis permite intervenciones focalizadas por área.',
                        
                        'Distribución Conforme a Tiempo en el Puesto Actual (Antigüedad)': '<strong>Importancia según NOM-035:</strong> La antigüedad en el puesto puede relacionarse con el nivel de adaptación, dominio de funciones y satisfacción laboral. Identificar patrones ayuda a mejorar procesos de inducción y desarrollo profesional.'
                    };
                    
                    return descriptions[title] || '<strong>Importancia según NOM-035:</strong> Este factor demográfico permite identificar patrones de riesgo psicosocial específicos para diferentes grupos de trabajadores, facilitando intervenciones focalizadas y efectivas.';
                },
                createSectionChart(key, section) {
                    const canvas = this.chartRefs[key];
                    if (!canvas) return;

                    const ctx = canvas.getContext('2d');
                    
                    // Prepare data - labels are the demographic values, data is the total count
                    const labels = section.data.map(item => this.normalizeLabel(item.name));
                    const data = section.data.map(item => item.total);
                    
                    // Use different colors for each bar
                    const colorPalette = [
                        '#3b82f6', // Blue
                        '#10b981', // Green
                        '#f59e0b', // Amber
                        '#ef4444', // Red
                        '#8b5cf6', // Purple
                        '#ec4899', // Pink
                        '#14b8a6', // Teal
                        '#f97316', // Orange
                        '#06b6d4', // Cyan
                        '#84cc16', // Lime
                        '#6366f1', // Indigo
                        '#a855f7', // Violet
                    ];
                    
                    const colors = labels.map((_, index) => colorPalette[index % colorPalette.length]);

                    const chart = new Chart(ctx, {
                        type: 'bar',
                        data: {
                            labels: labels,
                            datasets: [{
                                label: 'Total',
                                data: data,
                                backgroundColor: colors,
                                borderColor: colors.map(c => c),
                                borderWidth: 2
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
                                        label: function(context) {
                                            return 'Total: ' + context.parsed.y;
                                        }
                                    }
                                },
                                datalabels: {
                                    display: true,
                                    color: '#FFFFFF',
                                    font: {
                                        weight: 'bold',
                                        size: 14
                                    },
                                    formatter: function(value) {
                                        return value;
                                    },
                                    anchor: 'center',
                                    align: 'center'
                                }
                            },
                            scales: {
                                x: {
                                    ticks: {
                                        font: {
                                            size: 9
                                        },
                                        maxRotation: 45,
                                        minRotation: 45
                                    }
                                },
                                y: {
                                    beginAtZero: true,
                                    ticks: {
                                        precision: 0,
                                        font: {
                                            size: 10
                                        }
                                    },
                                    title: {
                                        display: true,
                                        text: 'Cantidad de Trabajadores',
                                        font: {
                                            size: 11,
                                            weight: 'bold'
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
                            const key = 'section-' + sectionIndex;
                            this.createSectionChart(key, section);
                        });
                    }, 500);
                });
            }
        }).mount('#app');
    </script>
</body>
</html>
