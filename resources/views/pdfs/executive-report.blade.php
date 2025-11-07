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
            background: #4b5563;
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

        .risk-nulo { background: #06b6d4; color: white; }
        .risk-bajo { background: #22c55e; color: white; }
        .risk-medio { background: #facc15; color: black; }
        .risk-alto { background: #f97316; color: white; }
        .risk-muy-alto { background: #ef4444; color: white; }

        .summary-box {
            background: #f3f4f6;
            border-left: 4px solid #6b7280;
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
        <h1>INFORME EJECUTIVO</h1>
        <h2>{{ strtoupper($organization->razon_social ?? $organization->nombre ?? $organization->name) }}</h2>
        <div class="subtitle">
            NOM-035-STPS-2018<br>
            Factores de Riesgo Psicosocial en el Trabajo
        </div>
    </div>

    <!-- I.- Datos del Centro de Trabajo -->
    <div class="section">
        <h3 class="section-title">I.- Datos del Centro de Trabajo</h3>
        
        <div class="organization-info">
            <table style="width: 100%; border-collapse: collapse; margin-bottom: 15px;">
                @if($organization->razon_social)
                <tr>
                    <td style="width: 30%; padding: 8px; border: 1px solid #ddd; background: #f8f9fa;"><strong>Razón Social</strong></td>
                    <td style="width: 70%; padding: 8px; border: 1px solid #ddd;">{{ $organization->razon_social }}</td>
                </tr>
                @endif
                
                @if($organization->rfc)
                <tr>
                    <td style="padding: 8px; border: 1px solid #ddd; background: #f8f9fa;"><strong>RFC</strong></td>
                    <td style="padding: 8px; border: 1px solid #ddd;">{{ $organization->rfc }}</td>
                </tr>
                @endif
                
                @if($organization->registro_patronal)
                <tr>
                    <td style="padding: 8px; border: 1px solid #ddd; background: #f8f9fa;"><strong>Registro Patronal</strong></td>
                    <td style="padding: 8px; border: 1px solid #ddd;">{{ $organization->registro_patronal }}</td>
                </tr>
                @endif
            </table>

            @if($organization->calle_numero || $organization->colonia || $organization->municipio || $organization->estado)
            <h4 style="font-size: 11pt; color: #1e40af; margin: 12px 0 8px 0;">Domicilio</h4>
            <table style="width: 100%; border-collapse: collapse; margin-bottom: 15px;">
                @if($organization->calle_numero)
                <tr>
                    <td style="width: 30%; padding: 8px; border: 1px solid #ddd; background: #f8f9fa;"><strong>Calle y No.</strong></td>
                    <td style="width: 70%; padding: 8px; border: 1px solid #ddd;">
                        {{ $organization->calle_numero }}
                        @if($organization->codigo_postal) C.P. {{ $organization->codigo_postal }}@endif
                    </td>
                </tr>
                @endif
                
                @if($organization->colonia)
                <tr>
                    <td style="padding: 8px; border: 1px solid #ddd; background: #f8f9fa;"><strong>Colonia</strong></td>
                    <td style="padding: 8px; border: 1px solid #ddd;">{{ $organization->colonia }}</td>
                </tr>
                @endif
                
                @if($organization->municipio)
                <tr>
                    <td style="padding: 8px; border: 1px solid #ddd; background: #f8f9fa;"><strong>Municipio</strong></td>
                    <td style="padding: 8px; border: 1px solid #ddd;">{{ $organization->municipio }}</td>
                </tr>
                @endif
                
                @if($organization->estado)
                <tr>
                    <td style="padding: 8px; border: 1px solid #ddd; background: #f8f9fa;"><strong>Estado</strong></td>
                    <td style="padding: 8px; border: 1px solid #ddd;">{{ $organization->estado }}</td>
                </tr>
                @endif
            </table>
            @endif

            @if($organization->contacto_nombre || $organization->contacto_puesto || $organization->contacto_email || $organization->contacto_telefono)
            <h4 style="font-size: 11pt; color: #1e40af; margin: 12px 0 8px 0;">Contacto</h4>
            <table style="width: 100%; border-collapse: collapse; margin-bottom: 15px;">
                @if($organization->contacto_nombre)
                <tr>
                    <td style="width: 30%; padding: 8px; border: 1px solid #ddd; background: #f8f9fa;"><strong>Nombre</strong></td>
                    <td style="width: 70%; padding: 8px; border: 1px solid #ddd;">{{ $organization->contacto_nombre }}</td>
                </tr>
                @endif
                
                @if($organization->contacto_puesto)
                <tr>
                    <td style="padding: 8px; border: 1px solid #ddd; background: #f8f9fa;"><strong>Puesto</strong></td>
                    <td style="padding: 8px; border: 1px solid #ddd;">{{ $organization->contacto_puesto }}</td>
                </tr>
                @endif
                
                @if($organization->contacto_email)
                <tr>
                    <td style="padding: 8px; border: 1px solid #ddd; background: #f8f9fa;"><strong>Correo Electrónico</strong></td>
                    <td style="padding: 8px; border: 1px solid #ddd;">{{ $organization->contacto_email }}</td>
                </tr>
                @endif
                
                @if($organization->contacto_telefono)
                <tr>
                    <td style="padding: 8px; border: 1px solid #ddd; background: #f8f9fa;"><strong>Teléfono</strong></td>
                    <td style="padding: 8px; border: 1px solid #ddd;">{{ $organization->contacto_telefono }}</td>
                </tr>
                @endif
            </table>
            @endif

            @if($organization->contacto_nombre_2 || $organization->contacto_puesto_2 || $organization->contacto_email_2 || $organization->contacto_telefono_2)
            <h4 style="font-size: 11pt; color: #1e40af; margin: 12px 0 8px 0;">Contacto</h4>
            <table style="width: 100%; border-collapse: collapse; margin-bottom: 15px;">
                @if($organization->contacto_nombre_2)
                <tr>
                    <td style="width: 30%; padding: 8px; border: 1px solid #ddd; background: #f8f9fa;"><strong>Nombre</strong></td>
                    <td style="width: 70%; padding: 8px; border: 1px solid #ddd;">{{ $organization->contacto_nombre_2 }}</td>
                </tr>
                @endif
                
                @if($organization->contacto_puesto_2)
                <tr>
                    <td style="padding: 8px; border: 1px solid #ddd; background: #f8f9fa;"><strong>Puesto</strong></td>
                    <td style="padding: 8px; border: 1px solid #ddd;">{{ $organization->contacto_puesto_2 }}</td>
                </tr>
                @endif
                
                @if($organization->contacto_email_2)
                <tr>
                    <td style="padding: 8px; border: 1px solid #ddd; background: #f8f9fa;"><strong>Correo Electrónico</strong></td>
                    <td style="padding: 8px; border: 1px solid #ddd;">{{ $organization->contacto_email_2 }}</td>
                </tr>
                @endif
                
                @if($organization->contacto_telefono_2)
                <tr>
                    <td style="padding: 8px; border: 1px solid #ddd; background: #f8f9fa;"><strong>Teléfono</strong></td>
                    <td style="padding: 8px; border: 1px solid #ddd;">{{ $organization->contacto_telefono_2 }}</td>
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
            $totalParticipants = $executiveData['analisis_cuantitativo_final']['total'] ?? 0;
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
    </div>

    <!-- II. Análisis Cuantitativo de los Factores de Riesgo Psicosocial - Calificación Final -->
    <div class="section">
        <h3 class="section-title">II. Análisis Cuantitativo de los Factores de Riesgo Psicosocial - Calificación Final</h3>
        
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

    <!-- III. Análisis Cuantitativo de Actos de Violencia Laboral -->
    <div class="section">
        <h3 class="section-title">III. Análisis Cuantitativo de Actos de Violencia Laboral</h3>
        
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

    <!-- IV. Evaluación del Entorno Organizacional -->
    <div class="section">
        <h3 class="section-title">IV. Evaluación del Entorno Organizacional</h3>
        
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
        <!-- V. Análisis Cuantitativo de los Factores de Riesgo Psicosocial por Dimensión -->
    <div class="section" style="page-break-before: always;">
        <h3 class="section-title">V. Análisis Cuantitativo de los Factores de Riesgo Psicosocial por Dimensión</h3>
        
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
        <!-- VI. Análisis Cualitativo de los Factores de Riesgo Psicosocial -->
    <div class="section" style="page-break-before: always;">
        <h3 class="section-title">VI. Análisis Cualitativo de los Factores de Riesgo Psicosocial</h3>
        
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
        <!-- VII. Identificación de los Trabajadores con Factores de Riesgo Psicosocial -->
    <div class="section" style="page-break-before: always;">
        <h3 class="section-title">VII. Identificación de los Trabajadores con Factores de Riesgo Psicosocial</h3>
        
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

    <!-- VIII. Acontecimientos Traumáticos Severos -->
    <div class="section">
        <h3 class="section-title">VIII. Identificación de Trabajadores Sujetos a Acontecimientos Traumáticos Severos</h3>
        
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
            <!-- IX. Identificación de Trabajadores Sujetos a Actos de Violencia Laboral -->
    <div class="section" style="page-break-before: always;">
        <h3 class="section-title">IX. Identificación de Trabajadores Sujetos a Actos de Violencia Laboral</h3>
        
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

    <!-- X. Identificación por Calificación Final -->
    <div class="section" style="page-break-before: always;">
        <h3 class="section-title">X. Identificación de Trabajadores por Calificación Final y Factor de Riesgo</h3>
        
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

    <!-- XI. Análisis de Dominios -->
    <div class="section">
        <h3 class="section-title">XI. Análisis Cuantitativo de los Dominios - Calificación Final</h3>
        
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
        <!-- XII. Identificación de Trabajadores por Categoría de Riesgo -->
    <div class="section" style="page-break-before: always;">
        <h3 class="section-title">XII. Identificación de Trabajadores por Categoría de Riesgo</h3>
        
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
                                    '#06b6d4',
                                    '#22c55e',
                                    '#facc15',
                                    '#f97316',
                                    '#ef4444'
                                ],
                                borderColor: [
                                    '#06b6d4',
                                    '#22c55e',
                                    '#facc15',
                                    '#f97316',
                                    '#ef4444'
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
