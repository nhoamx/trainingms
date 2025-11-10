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

        @php
            $porAreas = $executiveData['analisis_cuantitativo_final']['por_areas'] ?? [];
            $porPuestos = $executiveData['analisis_cuantitativo_final']['por_puestos'] ?? [];
            
            // Calcular total y atención (Medio + Alto + Muy Alto) para cada área/puesto
            $calcularAtencion = function($distribution) {
                return ($distribution['Medio'] ?? 0) + ($distribution['Alto'] ?? 0) + ($distribution['Muy Alto'] ?? 0);
            };
            
            $calcularTotal = function($distribution) {
                return array_sum($distribution);
            };
        @endphp

        @if(!empty($porAreas) || !empty($porPuestos))
        <h4 class="section-subtitle" style="margin-top: 20px;">Distribución por Área y Puesto</h4>
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-top: 10px;">
            <!-- Tabla por Área -->
            <div>
                <table style="font-size: 8pt;">
                    <thead>
                        <tr>
                            <th colspan="3" style="background-color: #1e40af; color: white;">Área</th>
                        </tr>
                        <tr style="background-color: #dbeafe;">
                            <th style="width: 50%;">Área</th>
                            <th style="width: 25%;">Total</th>
                            <th style="width: 25%;">Atención</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($porAreas as $area => $distribution)
                        @php
                            $total = $calcularTotal($distribution);
                            $atencion = $calcularAtencion($distribution);
                        @endphp
                        <tr>
                            <td style="text-align: left; padding-left: 8px;">{{ $area }}</td>
                            <td>{{ $total }}</td>
                            <td style="{{ $atencion > 0 ? 'background-color: #fee2e2; font-weight: bold;' : '' }}">{{ $atencion }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Tabla por Puesto -->
            <div>
                <table style="font-size: 8pt;">
                    <thead>
                        <tr>
                            <th colspan="3" style="background-color: #1e40af; color: white;">Puesto</th>
                        </tr>
                        <tr style="background-color: #dbeafe;">
                            <th style="width: 50%;">Puesto</th>
                            <th style="width: 25%;">Total</th>
                            <th style="width: 25%;">Atención</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($porPuestos as $puesto => $distribution)
                        @php
                            $total = $calcularTotal($distribution);
                            $atencion = $calcularAtencion($distribution);
                        @endphp
                        <tr>
                            <td style="text-align: left; padding-left: 8px;">{{ $puesto }}</td>
                            <td>{{ $total }}</td>
                            <td style="{{ $atencion > 0 ? 'background-color: #fee2e2; font-weight: bold;' : '' }}">{{ $atencion }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif
    </div>

    <!-- III. Análisis Cuantitativo de Actos de Violencia Laboral -->
    <div class="section" style="page-break-before: always;">
        <h3 class="section-title">III. Análisis Cuantitativo de Actos de Violencia Laboral</h3>
        
        <div style="margin-bottom: 20px; text-align: justify; line-height: 1.6;">
            <p style="margin-bottom: 12px;">
                La Organización Mundial de la Salud (OMS) define el acoso laboral o <em>mobbing</em> como el comportamiento agresivo de uno o más 
                miembros de un equipo de trabajo hacia un individuo de dicho grupo, con el objetivo de producir miedo, desprecio o depresión en 
                ese trabajador, hasta que renuncie o sea despedido.
            </p>
            
            <p style="margin-bottom: 12px;">
                La violencia laboral, se establece de conformidad con lo siguiente:
            </p>
            
            <p style="margin-bottom: 8px;">
                <strong>1) Acoso, acoso psicológico:</strong> Aquellos actos que dañan la estabilidad psicológica, la personalidad, la dignidad o integridad del 
                trabajador. Consiste en acciones de intimidación sistemática y persistente, tales como: descrédito, insultos, humillaciones, 
                devaluación, marginación, indiferencia, comparaciones destructivas, rechazo, restricción a la autodeterminación y amenazas, las 
                cuales llevan al trabajador a la depresión, al aislamiento, a la pérdida de su autoestima. Para efectos de esta Norma no se considera 
                el acoso sexual;
            </p>
            
            <p style="margin-bottom: 8px;">
                <strong>2) Hostigamiento:</strong> El ejercicio de poder en una relación de subordinación real de la víctima frente al agresor en el ámbito laboral, que 
                se expresa en conductas verbales, físicas o ambas, y
            </p>
            
            <p style="margin-bottom: 20px;">
                <strong>3) Malos tratos:</strong> Aquellos actos consistentes en insultos, burlas, humillaciones y/o ridiculizaciones del trabajador, realizados de 
                manera continua y persistente (más de una vez y/o en diferentes ocasiones).
            </p>
        </div>

        <div class="summary-box" style="margin-bottom: 20px;">
            <p><strong>Total de Participantes:</strong> {{ $executiveData['analisis_violencia_laboral']['total'] }}</p>
        </div>

        <div style="margin-bottom: 20px;">
            <h4 class="section-subtitle">Distribución por Nivel de Riesgo (Preguntas 57-64)</h4>
            <div style="display: flex; justify-content: center; margin: 20px 0;">
                <div style="width: 500px; height: 350px; position: relative;">
                    <canvas id="chart-violencia-laboral" style="width: 100% !important; height: 100% !important;"></canvas>
                </div>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Nivel de Riesgo</th>
                    <th>Calificación Total del Dominio</th>
                    <th>Cantidad</th>
                    <th>Porcentaje</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $ranges = [
                        'Nulo' => 'Nulo o despreciable: ≤6',
                        'Bajo' => 'Bajo: ≥7 y ≤9',
                        'Medio' => 'Medio: ≥10 y ≤12',
                        'Alto' => 'Alto: ≥13 y ≤15',
                        'Muy Alto' => 'Muy Alto: ≥16',
                    ];
                @endphp
                @foreach(['Nulo', 'Bajo', 'Medio', 'Alto', 'Muy Alto'] as $level)
                <tr>
                    <td><span class="risk-badge risk-{{ strtolower(str_replace(' ', '-', $level)) }}">{{ $level }}</span></td>
                    <td>{{ $ranges[$level] }}</td>
                    <td>{{ $executiveData['analisis_violencia_laboral']['distribution'][$level] }}</td>
                    <td>{{ $executiveData['analisis_violencia_laboral']['percentages'][$level] }}%</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <div style="margin-top: 20px; padding: 15px; background-color: #f9fafb; border-left: 4px solid #1e40af;">
            <h5 style="margin: 0 0 10px 0; font-size: 12px; color: #1e40af;">Preguntas realizadas en los cuestionarios que dan origen a los datos mostrados:</h5>
            <table style="font-size: 9px;">
                <thead>
                    <tr style="background-color: #dbeafe;">
                        <th style="width: 50px; text-align: center;">Pregunta</th>
                        <th style="text-align: left;">Texto</th>
                        <th style="text-align: center; width: 50px;">Nulo</th>
                        <th style="text-align: center; width: 50px;">Bajo</th>
                        <th style="text-align: center; width: 50px;">Medio</th>
                        <th style="text-align: center; width: 50px;">Alto</th>
                        <th style="text-align: center; width: 60px;">Muy Alto</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $violenciaQuestions = config('referencia_iii.general');
                        $questionStats = $executiveData['analisis_violencia_laboral']['question_stats'] ?? [];
                    @endphp
                    @for($q = 57; $q <= 64; $q++)
                    <tr>
                        <td style="text-align: center;">{{ $q }}</td>
                        <td style="text-align: left; padding-left: 8px;">{{ $violenciaQuestions[$q] ?? '' }}</td>
                        <td style="text-align: center;">{{ $questionStats[$q]['Nulo'] ?? 0 }}</td>
                        <td style="text-align: center;">{{ $questionStats[$q]['Bajo'] ?? 0 }}</td>
                        <td style="text-align: center;">{{ $questionStats[$q]['Medio'] ?? 0 }}</td>
                        <td style="text-align: center;">{{ $questionStats[$q]['Alto'] ?? 0 }}</td>
                        <td style="text-align: center;">{{ $questionStats[$q]['Muy Alto'] ?? 0 }}</td>
                    </tr>
                    @endfor
                </tbody>
            </table>
        </div>
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
        
        @php
            $dimData = $executiveData['analisis_dimensiones'] ?? [];
            $riskClass = function($level){ return 'risk-'.strtolower(str_replace(' ', '-', $level)); };
            $rows = [];
            foreach($dimData as $dimensionName => $items){
                foreach($items as $item){
                    $rows[] = [
                        'dimension' => $dimensionName,
                        'num' => $item['item_numero'],
                        'text' => $item['item_text'],
                        'avg' => $item['average_score'],
                        'level' => $item['risk_level'] ?? 'Nulo',
                        'count' => $item['count'] ?? null,
                    ];
                }
            }
        @endphp

        <table>
            <thead>
                <tr>
                    <th style="width: 8%">No.</th>
                    <th style="width: 28%">Dimensión</th>
                    <th style="width: 44%">Pregunta</th>
                    <th style="width: 10%">Promedio</th>
                    <th style="width: 10%">Nivel</th>
                </tr>
            </thead>
            <tbody>
                @foreach($rows as $r)
                <tr>
                    <td>{{ $r['num'] }}</td>
                    <td style="text-align: left; padding-left: 10px;">{{ $r['dimension'] }}</td>
                    <td style="text-align: left; padding-left: 10px;">{{ $r['text'] }}</td>
                    <td>{{ $r['avg'] }}</td>
                    <td><span class="risk-badge {{ $riskClass($r['level']) }}">{{ $r['level'] }}</span></td>
                </tr>
                @endforeach
            </tbody>
        </table>
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
                    executiveData: {!! json_encode($executiveData, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) !!}
                }
            },
            mounted() {
                this.renderCalificacionFinalChart();
                this.renderViolenciaLaboralChart();
            },
            methods: {
                renderCalificacionFinalChart() {
                    const ctx = document.getElementById('chart-calificacion-final');
                    const distribution = this.executiveData.analisis_cuantitativo_final.distribution;
                    
                    new Chart(ctx, {
                        type: 'pie',
                        data: {
                            labels: ['Nulo', 'Bajo', 'Medio', 'Alto', 'Muy Alto'],
                            datasets: [{
                                label: 'Trabajadores',
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
                                borderColor: '#ffffff',
                                borderWidth: 2
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: true,
                            plugins: {
                                legend: {
                                    display: true,
                                    position: 'right',
                                    labels: {
                                        font: {
                                            size: 11
                                        },
                                        padding: 15,
                                        generateLabels: (chart) => {
                                            const data = chart.data;
                                            const total = data.datasets[0].data.reduce((a, b) => a + b, 0);
                                            return data.labels.map((label, i) => {
                                                const value = data.datasets[0].data[i];
                                                const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                                                return {
                                                    text: `${label}: ${value} (${percentage}%)`,
                                                    fillStyle: data.datasets[0].backgroundColor[i],
                                                    hidden: false,
                                                    index: i
                                                };
                                            });
                                        }
                                    }
                                },
                                title: {
                                    display: true,
                                    text: 'Distribución de Calificación Final',
                                    font: {
                                        size: 14,
                                        weight: 'bold'
                                    }
                                },
                                tooltip: {
                                    callbacks: {
                                        label: function(context) {
                                            const label = context.label || '';
                                            const value = context.parsed || 0;
                                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                            const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                                            return `${label}: ${value} (${percentage}%)`;
                                        }
                                    }
                                }
                            }
                        }
                    });
                },
                renderViolenciaLaboralChart() {
                    const ctx = document.getElementById('chart-violencia-laboral');
                    if (!ctx) {
                        console.error('Canvas element chart-violencia-laboral not found');
                        return;
                    }
                    
                    const distribution = this.executiveData.analisis_violencia_laboral.distribution;
                    
                    new Chart(ctx, {
                        type: 'pie',
                        data: {
                            labels: ['Nulo', 'Bajo', 'Medio', 'Alto', 'Muy Alto'],
                            datasets: [{
                                label: 'Trabajadores',
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
                                borderColor: '#ffffff',
                                borderWidth: 2
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: true,
                            plugins: {
                                legend: {
                                    display: true,
                                    position: 'right',
                                    labels: {
                                        font: {
                                            size: 11
                                        },
                                        padding: 15,
                                        generateLabels: (chart) => {
                                            const data = chart.data;
                                            const total = data.datasets[0].data.reduce((a, b) => a + b, 0);
                                            return data.labels.map((label, i) => {
                                                const value = data.datasets[0].data[i];
                                                const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                                                return {
                                                    text: `${label}: ${value} (${percentage}%)`,
                                                    fillStyle: data.datasets[0].backgroundColor[i],
                                                    hidden: false,
                                                    index: i
                                                };
                                            });
                                        }
                                    }
                                },
                                title: {
                                    display: false
                                },
                                tooltip: {
                                    callbacks: {
                                        label: function(context) {
                                            const label = context.label || '';
                                            const value = context.parsed || 0;
                                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                            const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                                            return `${label}: ${value} (${percentage}%)`;
                                        }
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
