<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Informe Ejecutivo NOM-035 - {{ $organization->name }}</title>
    <script src="https://cdn.jsdelivr.net/npm/vue@3.5.13/dist/vue.global.prod.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
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

        .section {
            margin-bottom: 25px;
            page-break-inside: avoid;
        }

        .section-title {
            font-size: 12pt;
            color: #1e40af;
            margin-bottom: 10px;
            padding-bottom: 5px;
            border-bottom: 2px solid #e5e7eb;
        }

        .section-subtitle {
            font-size: 11pt;
            color: #3b82f6;
            margin: 10px 0 8px 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
            font-size: 9pt;
        }

        table th {
            background: #2563eb;
            color: white;
            padding: 8px;
            text-align: center;
            font-weight: bold;
        }

        table td {
            border: 1px solid #ddd;
            padding: 6px;
            text-align: center;
        }

        table tbody tr:nth-child(even) {
            background: #f8f9fa;
        }

        .chart-container {
            width: 100%;
            max-width: 500px;
            margin: 15px auto;
            page-break-inside: avoid;
        }

        .risk-badge {
            padding: 3px 8px;
            border-radius: 3px;
            font-weight: bold;
            font-size: 8pt;
        }

        .risk-nulo { background: #d1fae5; color: #065f46; }
        .risk-bajo { background: #dbeafe; color: #1e40af; }
        .risk-medio { background: #fef3c7; color: #92400e; }
        .risk-alto { background: #fed7aa; color: #9a3412; }
        .risk-muy-alto { background: #fee2e2; color: #991b1b; }

        .summary-box {
            background: #eff6ff;
            border-left: 4px solid #3b82f6;
            padding: 12px;
            margin: 10px 0;
            border-radius: 3px;
        }

        .footer {
            text-align: center;
            font-size: 8pt;
            color: #666;
            margin-top: 30px;
            padding-top: 15px;
            border-top: 1px solid #ddd;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <h1>Informe Ejecutivo</h1>
        <h2>Factores de Riesgo Psicosocial NOM-035-STPS-2018</h2>
        <div class="subtitle">
            Identificación, Análisis y Prevención de Factores de Riesgo Psicosocial
        </div>
    </div>

    <!-- Organization Info -->
    <div class="organization-info">
        <h3>{{ $organization->name }}</h3>
        <p><strong>Fecha de generación:</strong> {{ $generatedDate }}</p>
        <p><strong>Total de evaluaciones:</strong> {{ $executiveData['analisis_cuantitativo_final']['total'] }}</p>
    </div>

    <!-- 1. Análisis Cuantitativo de los Factores de Riesgo Psicosocial - Calificación Final -->
    <div class="section">
        <h3 class="section-title">1. Análisis Cuantitativo de los Factores de Riesgo Psicosocial - Calificación Final</h3>
        
        <div class="summary-box">
            <strong>Resumen:</strong> Distribución general de niveles de riesgo psicosocial según NOM-035-STPS-2018
        </div>

        <div class="chart-container">
            <canvas id="chart-calificacion-final"></canvas>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Nivel de Riesgo</th>
                    <th>Cantidad</th>
                    <th>Porcentaje</th>
                </tr>
            </thead>
            <tbody>
                @foreach(['Nulo', 'Bajo', 'Medio', 'Alto', 'Muy Alto'] as $level)
                <tr>
                    <td><span class="risk-badge risk-{{ strtolower(str_replace(' ', '-', $level)) }}">{{ $level }}</span></td>
                    <td>{{ $executiveData['analisis_cuantitativo_final']['distribution'][$level] }}</td>
                    <td>{{ $executiveData['analisis_cuantitativo_final']['percentages'][$level] }}%</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <!-- 2. Análisis Cuantitativo de Actos de Violencia Laboral -->
    <div class="section">
        <h3 class="section-title">2. Análisis Cuantitativo de Actos de Violencia Laboral</h3>
        
        <table>
            <thead>
                <tr>
                    <th>Concepto</th>
                    <th>Cantidad</th>
                    <th>Porcentaje</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Trabajadores afectados por violencia laboral</td>
                    <td>{{ $executiveData['analisis_violencia_laboral']['affected_count'] }}</td>
                    <td>{{ $executiveData['analisis_violencia_laboral']['percentage'] }}%</td>
                </tr>
                <tr>
                    <td>Total de trabajadores evaluados</td>
                    <td>{{ $executiveData['analisis_violencia_laboral']['total'] }}</td>
                    <td>100%</td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- 3. Evaluación del Entorno Organizacional -->
    <div class="section">
        <h3 class="section-title">3. Evaluación del Entorno Organizacional</h3>
        
        <table>
            <thead>
                <tr>
                    <th>Nivel de Riesgo</th>
                    <th>Cantidad</th>
                    <th>Porcentaje</th>
                </tr>
            </thead>
            <tbody>
                @foreach(['Nulo', 'Bajo', 'Medio', 'Alto', 'Muy Alto'] as $level)
                <tr>
                    <td><span class="risk-badge risk-{{ strtolower(str_replace(' ', '-', $level)) }}">{{ $level }}</span></td>
                    <td>{{ $executiveData['evaluacion_entorno']['distribution'][$level] }}</td>
                    <td>{{ $executiveData['evaluacion_entorno']['percentages'][$level] }}%</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <!-- 4. Análisis Cuantitativo por Dimensión (Promedio por Pregunta) -->
    <div class="section" style="page-break-before: always;">
        <h3 class="section-title">4. Análisis Cuantitativo de los Factores de Riesgo Psicosocial por Dimensión</h3>
        
        @foreach($executiveData['analisis_dimensiones'] as $dimensionName => $items)
        <h4 class="section-subtitle">{{ $dimensionName }}</h4>
        <table>
            <thead>
                <tr>
                    <th style="width: 10%">No.</th>
                    <th style="width: 70%">Pregunta</th>
                    <th style="width: 20%">Promedio</th>
                </tr>
            </thead>
            <tbody>
                @foreach($items as $item)
                <tr>
                    <td>{{ $item['item_numero'] }}</td>
                    <td style="text-align: left; padding-left: 10px;">{{ $item['item_text'] }}</td>
                    <td>{{ $item['average_score'] }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endforeach
    </div>

    <!-- 5. Análisis Cualitativo -->
    <div class="section" style="page-break-before: always;">
        <h3 class="section-title">5. Análisis Cualitativo de los Factores de Riesgo Psicosocial</h3>
        
        <!-- Por Género -->
        <h4 class="section-subtitle">a) Por Género</h4>
        <table>
            <thead>
                <tr>
                    <th>Género</th>
                    <th>Nulo</th>
                    <th>Bajo</th>
                    <th>Medio</th>
                    <th>Alto</th>
                    <th>Muy Alto</th>
                </tr>
            </thead>
            <tbody>
                @foreach($executiveData['analisis_cualitativo']['por_genero'] as $gender => $distribution)
                <tr>
                    <td>{{ $gender }}</td>
                    @foreach(['Nulo', 'Bajo', 'Medio', 'Alto', 'Muy Alto'] as $level)
                    <td>{{ $distribution[$level] }}</td>
                    @endforeach
                </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Por Naturaleza de Funciones -->
        <h4 class="section-subtitle">b) Por Naturaleza de Funciones</h4>
        <table>
            <thead>
                <tr>
                    <th>Función</th>
                    <th>Nulo</th>
                    <th>Bajo</th>
                    <th>Medio</th>
                    <th>Alto</th>
                    <th>Muy Alto</th>
                </tr>
            </thead>
            <tbody>
                @foreach($executiveData['analisis_cualitativo']['por_funciones'] as $function => $distribution)
                <tr>
                    <td>{{ $function }}</td>
                    @foreach(['Nulo', 'Bajo', 'Medio', 'Alto', 'Muy Alto'] as $level)
                    <td>{{ $distribution[$level] }}</td>
                    @endforeach
                </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Por Áreas -->
        <h4 class="section-subtitle">c) Por Áreas</h4>
        <table>
            <thead>
                <tr>
                    <th>Área</th>
                    <th>Nulo</th>
                    <th>Bajo</th>
                    <th>Medio</th>
                    <th>Alto</th>
                    <th>Muy Alto</th>
                </tr>
            </thead>
            <tbody>
                @foreach($executiveData['analisis_cualitativo']['por_areas'] as $area => $distribution)
                <tr>
                    <td>{{ $area }}</td>
                    @foreach(['Nulo', 'Bajo', 'Medio', 'Alto', 'Muy Alto'] as $level)
                    <td>{{ $distribution[$level] }}</td>
                    @endforeach
                </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Por Jornada Laboral -->
        <h4 class="section-subtitle">d) Por Jornada Laboral</h4>
        <table>
            <thead>
                <tr>
                    <th>Jornada</th>
                    <th>Nulo</th>
                    <th>Bajo</th>
                    <th>Medio</th>
                    <th>Alto</th>
                    <th>Muy Alto</th>
                </tr>
            </thead>
            <tbody>
                @foreach($executiveData['analisis_cualitativo']['por_jornada'] as $jornada => $distribution)
                <tr>
                    <td>{{ $jornada }}</td>
                    @foreach(['Nulo', 'Bajo', 'Medio', 'Alto', 'Muy Alto'] as $level)
                    <td>{{ $distribution[$level] }}</td>
                    @endforeach
                </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Por Puestos -->
        <h4 class="section-subtitle">e) Por Puestos</h4>
        <table>
            <thead>
                <tr>
                    <th>Puesto</th>
                    <th>Nulo</th>
                    <th>Bajo</th>
                    <th>Medio</th>
                    <th>Alto</th>
                    <th>Muy Alto</th>
                </tr>
            </thead>
            <tbody>
                @foreach($executiveData['analisis_cualitativo']['por_puestos'] as $puesto => $distribution)
                <tr>
                    <td>{{ $puesto }}</td>
                    @foreach(['Nulo', 'Bajo', 'Medio', 'Alto', 'Muy Alto'] as $level)
                    <td>{{ $distribution[$level] }}</td>
                    @endforeach
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <!-- 6. Identificación de Trabajadores con Factores de Riesgo -->
    <div class="section" style="page-break-before: always;">
        <h3 class="section-title">6. Identificación de los Trabajadores con Factores de Riesgo Psicosocial</h3>
        
        <div class="summary-box">
            <p><strong>Total de trabajadores en riesgo:</strong> {{ $executiveData['identificacion_trabajadores_riesgo']['total_riesgo'] }}</p>
            <p>Medio: {{ $executiveData['identificacion_trabajadores_riesgo']['total_medio'] }} | 
               Alto: {{ $executiveData['identificacion_trabajadores_riesgo']['total_alto'] }} | 
               Muy Alto: {{ $executiveData['identificacion_trabajadores_riesgo']['total_muy_alto'] }}</p>
        </div>

        @foreach(['Medio', 'Alto', 'Muy Alto'] as $level)
        @if(count($executiveData['identificacion_trabajadores_riesgo']['trabajadores'][$level]) > 0)
        <h4 class="section-subtitle">Nivel {{ $level }}</h4>
        <table>
            <thead>
                <tr>
                    <th>Folio Personal</th>
                    <th>Nombre</th>
                    <th>Puntuación</th>
                </tr>
            </thead>
            <tbody>
                @foreach($executiveData['identificacion_trabajadores_riesgo']['trabajadores'][$level] as $worker)
                <tr>
                    <td>{{ $worker['personal_folio'] }}</td>
                    <td>{{ $worker['name'] }}</td>
                    <td>{{ $worker['score'] }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif
        @endforeach
    </div>

    <!-- 7. Acontecimientos Traumáticos Severos -->
    <div class="section">
        <h3 class="section-title">7. Identificación de Trabajadores Sujetos a Acontecimientos Traumáticos Severos</h3>
        
        <div class="summary-box">
            <p><strong>Total de trabajadores afectados:</strong> {{ $executiveData['identificacion_trabajadores_trauma']['total_affected'] }}</p>
        </div>

        @if($executiveData['identificacion_trabajadores_trauma']['total_affected'] > 0)
        <table>
            <thead>
                <tr>
                    <th>Folio Personal</th>
                    <th>Nombre</th>
                    <th>Eventos Reportados</th>
                </tr>
            </thead>
            <tbody>
                @foreach($executiveData['identificacion_trabajadores_trauma']['trabajadores'] as $worker)
                <tr>
                    <td>{{ $worker['personal_folio'] }}</td>
                    <td>{{ $worker['name'] }}</td>
                    <td>{{ count(array_filter($worker['events'], fn($v) => strtoupper($v) === 'SI')) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif
    </div>

    <!-- 8. Violencia Laboral -->
    <div class="section">
        <h3 class="section-title">8. Identificación de Trabajadores Sujetos a Actos de Violencia Laboral</h3>
        
        <div class="summary-box">
            <p><strong>Total de trabajadores afectados:</strong> {{ $executiveData['identificacion_trabajadores_violencia']['total_affected'] }}</p>
        </div>

        @if($executiveData['identificacion_trabajadores_violencia']['total_affected'] > 0)
        <table>
            <thead>
                <tr>
                    <th>Folio Personal</th>
                    <th>Nombre</th>
                    <th>Eventos de Violencia</th>
                </tr>
            </thead>
            <tbody>
                @foreach($executiveData['identificacion_trabajadores_violencia']['trabajadores'] as $worker)
                <tr>
                    <td>{{ $worker['personal_folio'] }}</td>
                    <td>{{ $worker['name'] }}</td>
                    <td>{{ count($worker['events']) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif
    </div>

    <!-- 9. Identificación por Calificación Final -->
    <div class="section" style="page-break-before: always;">
        <h3 class="section-title">9. Identificación de Trabajadores por Calificación Final y Factor de Riesgo</h3>
        
        <table>
            <thead>
                <tr>
                    <th>Nivel de Riesgo</th>
                    <th>Cantidad de Trabajadores</th>
                </tr>
            </thead>
            <tbody>
                @foreach(['Nulo', 'Bajo', 'Medio', 'Alto', 'Muy Alto'] as $level)
                <tr>
                    <td><span class="risk-badge risk-{{ strtolower(str_replace(' ', '-', $level)) }}">{{ $level }}</span></td>
                    <td>{{ $executiveData['identificacion_por_calificacion']['counts'][$level] }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <!-- 10. Análisis de Dominios -->
    <div class="section">
        <h3 class="section-title">10. Análisis Cuantitativo de los Dominios - Calificación Final</h3>
        
        @foreach($executiveData['analisis_dominios'] as $domainName => $domainData)
        <h4 class="section-subtitle">{{ $domainName }}</h4>
        <table>
            <thead>
                <tr>
                    <th>Nivel de Riesgo</th>
                    <th>Cantidad</th>
                    <th>Porcentaje</th>
                </tr>
            </thead>
            <tbody>
                @foreach(['Nulo', 'Bajo', 'Medio', 'Alto', 'Muy Alto'] as $level)
                <tr>
                    <td><span class="risk-badge risk-{{ strtolower(str_replace(' ', '-', $level)) }}">{{ $level }}</span></td>
                    <td>{{ $domainData['distribution'][$level] }}</td>
                    <td>{{ $domainData['percentages'][$level] }}%</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endforeach
    </div>

    <!-- 11. Identificación por Categoría -->
    <div class="section" style="page-break-before: always;">
        <h3 class="section-title">11. Identificación de Trabajadores por Categoría de Riesgo</h3>
        
        @foreach($executiveData['identificacion_por_categoria'] as $categoryName => $categoryData)
        <h4 class="section-subtitle">{{ $categoryName }}</h4>
        <table>
            <thead>
                <tr>
                    <th>Nivel de Riesgo</th>
                    <th>Cantidad de Trabajadores</th>
                </tr>
            </thead>
            <tbody>
                @foreach(['Nulo', 'Bajo', 'Medio', 'Alto', 'Muy Alto'] as $level)
                <tr>
                    <td><span class="risk-badge risk-{{ strtolower(str_replace(' ', '-', $level)) }}">{{ $level }}</span></td>
                    <td>{{ $categoryData['counts'][$level] }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endforeach
    </div>

    <!-- Footer -->
    <div class="footer">
        <p>Informe generado el {{ $generatedDate }}</p>
        <p>NOM-035-STPS-2018 - Factores de riesgo psicosocial en el trabajo</p>
    </div>

    <!-- Charts Script -->
    <script>
        const { createApp } = Vue;

        createApp({
            data() {
                return {
                    executiveData: @json($executiveData)
                }
            },
            mounted() {
                this.renderCalificacionFinalChart();
            },
            methods: {
                renderCalificacionFinalChart() {
                    const ctx = document.getElementById('chart-calificacion-final');
                    const distribution = this.executiveData.analisis_cuantitativo_final.distribution;
                    
                    new Chart(ctx, {
                        type: 'bar',
                        data: {
                            labels: ['Nulo', 'Bajo', 'Medio', 'Alto', 'Muy Alto'],
                            datasets: [{
                                label: 'Cantidad de Trabajadores',
                                data: [
                                    distribution['Nulo'],
                                    distribution['Bajo'],
                                    distribution['Medio'],
                                    distribution['Alto'],
                                    distribution['Muy Alto']
                                ],
                                backgroundColor: [
                                    '#d1fae5',
                                    '#dbeafe',
                                    '#fef3c7',
                                    '#fed7aa',
                                    '#fee2e2'
                                ],
                                borderColor: [
                                    '#065f46',
                                    '#1e40af',
                                    '#92400e',
                                    '#9a3412',
                                    '#991b1b'
                                ],
                                borderWidth: 2
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: true,
                            plugins: {
                                legend: {
                                    display: false
                                },
                                title: {
                                    display: true,
                                    text: 'Distribución de Calificación Final',
                                    font: {
                                        size: 14
                                    }
                                }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    ticks: {
                                        stepSize: 1
                                    }
                                }
                            }
                        }
                    });
                }
            }
        }).mount('body');
    </script>
</body>
</html>
