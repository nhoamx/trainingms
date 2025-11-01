{{-- Section 5.5.4: Cuantificación por Dimensión --}}
<div class="section">
    <h3 class="section-title">5.5.4 CUANTIFICACIÓN POR DIMENSIÓN</h3>
    
    <p class="section-description">
        Con referencia en la <strong>Tabla 6 de la Guía de Referencia III (NOM-035-STPS-2018)</strong> que agrupa 
        los ítems por categoría, dominio y dimensión, se obtuvo la <strong>Calificación de cada Dimensión</strong>, 
        sumando el puntaje de cada uno de los ítems que la integran. Para identificar los niveles de riesgo de cada 
        Dimensión se tomó como base, el nivel de riesgo del Dominio al que pertenece, y se calculó en la parte 
        proporcional de cada Dimensión de Dominio, obteniendo la siguiente distribución de niveles de riesgo:
    </p>

    @if(isset($diagnosticData['dimensions']) && count($diagnosticData['dimensions']) > 0)
        @php
            // Group dimensions by category/domain for better organization
            $dimensionsByCategory = [];
            
            // Define descriptions for categories and domains
            $categoryDescriptions = [
                1 => 'Condiciones del lugar de trabajo que pueden afectar la salud del trabajador (condiciones peligrosas, inseguras o deficientes).',
                2 => 'Características de las tareas que realiza el trabajador (carga de trabajo, ritmo, contradicciones en las demandas).',
                3 => 'Aspectos relacionados con la jornada laboral, turnos, rotaciones, duración y distribución del tiempo de trabajo.',
                4 => 'Aspectos relacionados con el tipo de liderazgo ejercido y las relaciones interpersonales en el trabajo.',
                5 => 'Características del entorno organizacional que pueden afectar el desempeño y bienestar del trabajador.'
            ];
            
            $domainDescriptions = [
                1 => 'Se refiere a las condiciones peligrosas e inseguras, condiciones deficientes e insalubres y trabajos peligrosos, propias del lugar o espacio físico de trabajo.',
                2 => 'Considera las cargas cuantitativas, los ritmos de trabajo acelerado, la carga mental, las cargas psicológicas emocionales, las cargas de alta responsabilidad y las cargas contradictorias o inconsistentes que se le exigen al trabajador.',
                3 => 'Incluye la falta de control y autonomía sobre el trabajo, limitada o nula posibilidad de desarrollo, insuficiente participación y manejo del cambio, y limitada o inexistente capacitación.',
                4 => 'Se refiere a las jornadas de trabajo que exceden lo establecido en la Ley Federal del Trabajo.',
                5 => 'Contempla la influencia del trabajo fuera del centro laboral y la influencia de las responsabilidades familiares.',
                6 => 'Incluye la escasa claridad de funciones y las características del liderazgo (negativo, ausente o deficiente).',
                7 => 'Evalúa las relaciones sociales en el trabajo, deficiente relación con los colaboradores que supervisa y deficiente relación con superiores jerárquicos.',
                8 => 'Acoso, acoso psicológico, hostigamiento y malos tratos en el trabajo.',
                9 => 'Se refiere a la escasa o nula retroalimentación del desempeño y al escaso o nulo reconocimiento y compensación.',
                10 => 'Incluye el limitado sentido de pertenencia e inestabilidad laboral.'
            ];
            
            // Define the mapping of dimensions to categories and domains
            $dimensionMapping = [
                // Categoría 1: Ambiente de trabajo
                'Condiciones peligrosas e inseguras' => ['cat' => 1, 'cat_name' => 'Ambiente de trabajo', 'dom' => 1, 'dom_name' => 'Condiciones en el ambiente de trabajo'],
                'Condiciones deficientes e insalubres' => ['cat' => 1, 'cat_name' => 'Ambiente de trabajo', 'dom' => 1, 'dom_name' => 'Condiciones en el ambiente de trabajo'],
                'Trabajos peligrosos' => ['cat' => 1, 'cat_name' => 'Ambiente de trabajo', 'dom' => 1, 'dom_name' => 'Condiciones en el ambiente de trabajo'],
                
                // Categoría 2: Factores propios de la actividad
                'Cargas cuantitativas' => ['cat' => 2, 'cat_name' => 'Factores propios de la actividad', 'dom' => 2, 'dom_name' => 'Carga de trabajo'],
                'Ritmos de trabajo acelerado' => ['cat' => 2, 'cat_name' => 'Factores propios de la actividad', 'dom' => 2, 'dom_name' => 'Carga de trabajo'],
                'Carga mental' => ['cat' => 2, 'cat_name' => 'Factores propios de la actividad', 'dom' => 2, 'dom_name' => 'Carga de trabajo'],
                'Cargas psicológicas emocionales' => ['cat' => 2, 'cat_name' => 'Factores propios de la actividad', 'dom' => 2, 'dom_name' => 'Carga de trabajo'],
                'Cargas de alta responsabilidad' => ['cat' => 2, 'cat_name' => 'Factores propios de la actividad', 'dom' => 2, 'dom_name' => 'Carga de trabajo'],
                'Cargas contradictorias o inconsistentes' => ['cat' => 2, 'cat_name' => 'Factores propios de la actividad', 'dom' => 2, 'dom_name' => 'Carga de trabajo'],
                
                'Falta de control y autonomía sobre el trabajo' => ['cat' => 2, 'cat_name' => 'Factores propios de la actividad', 'dom' => 3, 'dom_name' => 'Falta de control sobre el trabajo'],
                'Limitada o nula posibilidad de desarrollo' => ['cat' => 2, 'cat_name' => 'Factores propios de la actividad', 'dom' => 3, 'dom_name' => 'Falta de control sobre el trabajo'],
                'Insuficiente participación y manejo del cambio' => ['cat' => 2, 'cat_name' => 'Factores propios de la actividad', 'dom' => 3, 'dom_name' => 'Falta de control sobre el trabajo'],
                'Limitada o inexistente capacitación' => ['cat' => 2, 'cat_name' => 'Factores propios de la actividad', 'dom' => 3, 'dom_name' => 'Falta de control sobre el trabajo'],
                
                // Categoría 3: Organización del tiempo de trabajo
                'Jornadas de trabajo extensas' => ['cat' => 3, 'cat_name' => 'Organización del tiempo de trabajo', 'dom' => 4, 'dom_name' => 'Jornada de trabajo'],
                'Influencia del trabajo fuera del centro de trabajo' => ['cat' => 3, 'cat_name' => 'Organización del tiempo de trabajo', 'dom' => 5, 'dom_name' => 'Interferencia en la relación trabajo-familia'],
                'Influencia de las responsabilidades familiares' => ['cat' => 3, 'cat_name' => 'Organización del tiempo de trabajo', 'dom' => 5, 'dom_name' => 'Interferencia en la relación trabajo-familia'],
                
                // Categoría 4: Liderazgo y relaciones en el trabajo
                'Escasa claridad de funciones' => ['cat' => 4, 'cat_name' => 'Liderazgo y relaciones en el trabajo', 'dom' => 6, 'dom_name' => 'Liderazgo'],
                'Características del liderazgo' => ['cat' => 4, 'cat_name' => 'Liderazgo y relaciones en el trabajo', 'dom' => 6, 'dom_name' => 'Liderazgo'],
                'Relaciones sociales en el trabajo' => ['cat' => 4, 'cat_name' => 'Liderazgo y relaciones en el trabajo', 'dom' => 7, 'dom_name' => 'Relaciones en el trabajo'],
                'Deficiente relación con los colaboradores' => ['cat' => 4, 'cat_name' => 'Liderazgo y relaciones en el trabajo', 'dom' => 7, 'dom_name' => 'Relaciones en el trabajo'],
                'Violencia laboral' => ['cat' => 4, 'cat_name' => 'Liderazgo y relaciones en el trabajo', 'dom' => 8, 'dom_name' => 'Violencia'],
                
                // Categoría 5: Entorno organizacional
                'Escasa o nula retroalimentación del desempeño' => ['cat' => 5, 'cat_name' => 'Entorno organizacional', 'dom' => 9, 'dom_name' => 'Reconocimiento del desempeño'],
                'Escaso o nulo reconocimiento y compensación' => ['cat' => 5, 'cat_name' => 'Entorno organizacional', 'dom' => 9, 'dom_name' => 'Reconocimiento del desempeño'],
                'Limitado sentido de pertenencia' => ['cat' => 5, 'cat_name' => 'Entorno organizacional', 'dom' => 10, 'dom_name' => 'Insuficiente sentido de pertenencia e inestabilidad'],
                'Inestabilidad laboral' => ['cat' => 5, 'cat_name' => 'Entorno organizacional', 'dom' => 10, 'dom_name' => 'Insuficiente sentido de pertenencia e inestabilidad'],
            ];
            
            foreach ($diagnosticData['dimensions'] as $dimensionName => $levels) {
                $mapping = $dimensionMapping[$dimensionName] ?? ['cat' => 0, 'cat_name' => 'Otras dimensiones', 'dom' => 0, 'dom_name' => ''];
                $catKey = "Cat {$mapping['cat']}: {$mapping['cat_name']}";
                $domKey = "Dom {$mapping['dom']}: {$mapping['dom_name']}";
                
                if (!isset($dimensionsByCategory[$catKey])) {
                    $dimensionsByCategory[$catKey] = [];
                }
                if (!isset($dimensionsByCategory[$catKey][$domKey])) {
                    $dimensionsByCategory[$catKey][$domKey] = [];
                }
                
                $dimensionsByCategory[$catKey][$domKey][] = [
                    'name' => $dimensionName,
                    'levels' => $levels,
                    'cat' => $mapping['cat'],
                    'dom' => $mapping['dom']
                ];
            }
        @endphp

        @foreach($dimensionsByCategory as $categoryName => $domains)
        @php
            // Extract category number from "Cat X: Name" format
            preg_match('/Cat (\d+):/', $categoryName, $matches);
            $catNumber = isset($matches[1]) ? (int)$matches[1] : 0;
        @endphp
        <h4 style="font-size: 11pt; color: #1e40af; margin-top: 20px; margin-bottom: 8px; border-bottom: 2px solid #2563eb; padding-bottom: 5px;">
            {{ $categoryName }}
        </h4>
        @if(isset($categoryDescriptions[$catNumber]))
        <p style="font-size: 8.5pt; color: #4b5563; margin: 5px 0 15px 0; font-style: italic;">
            {{ $categoryDescriptions[$catNumber] }}
        </p>
        @endif
        
        @foreach($domains as $domainName => $dimensions)
            @php
                // Extract domain number from "Dom X: Name" format
                preg_match('/Dom (\d+):/', $domainName, $domMatches);
                $domNumber = isset($domMatches[1]) ? (int)$domMatches[1] : 0;
            @endphp
            <h5 style="font-size: 10pt; color: #4f46e5; margin-top: 15px; margin-bottom: 8px;">
                {{ $domainName }}
            </h5>
            @if(isset($domainDescriptions[$domNumber]))
            <p style="font-size: 8pt; color: #6b7280; margin: 5px 0 10px 0; font-style: italic;">
                {{ $domainDescriptions[$domNumber] }}
            </p>
            @endif
            
            {{-- Tabla de resumen de incidencias --}}
            <table style="margin-bottom: 15px;">
                <thead>
                    <tr>
                        <th style="text-align: left; width: 35%;">Dimensión</th>
                        <th style="width: 11%;">Nulo</th>
                        <th style="width: 11%;">Bajo</th>
                        <th style="width: 11%;">Medio</th>
                        <th style="width: 11%;">Alto</th>
                        <th style="width: 10%;">Muy Alto</th>
                        <th style="width: 11%;">Atender</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($dimensions as $dimension)
                    @php
                        $atender = ($dimension['levels']['Medio'] ?? 0) + ($dimension['levels']['Alto'] ?? 0) + ($dimension['levels']['Muy Alto'] ?? 0);
                    @endphp
                    <tr>
                        <td style="text-align: left;">{{ $dimension['name'] }}</td>
                        <td>{{ $dimension['levels']['Nulo'] ?? 0 }}</td>
                        <td>{{ $dimension['levels']['Bajo'] ?? 0 }}</td>
                        <td>{{ $dimension['levels']['Medio'] ?? 0 }}</td>
                        <td>{{ $dimension['levels']['Alto'] ?? 0 }}</td>
                        <td>{{ $dimension['levels']['Muy Alto'] ?? 0 }}</td>
                        <td><strong>{{ $atender }}</strong></td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            
            {{-- Gráficas en grid 2 columnas --}}
            <div class="charts-grid" style="margin-bottom: 20px;">
                @foreach($dimensions as $dimension)
                @php
                    $canvasId = 'dimensionChart_' . str_replace(' ', '_', $dimension['name']);
                    $total = array_sum($dimension['levels']);
                @endphp
                <div class="chart-item">
                    <h4 style="font-size: 8.5pt;">{{ $dimension['name'] }}</h4>
                    <div class="chart-item-canvas">
                        <canvas id="{{ $canvasId }}"></canvas>
                    </div>
                    <table style="margin-top: 10px; font-size: 7pt;">
                        <thead>
                            <tr>
                                <th style="font-size: 7pt;">Nivel</th>
                                <th style="font-size: 7pt;">N°</th>
                                <th style="font-size: 7pt;">%</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach(['Nulo', 'Bajo', 'Medio', 'Alto', 'Muy Alto'] as $level)
                            @php
                                $count = $dimension['levels'][$level] ?? 0;
                                $percentage = $total > 0 ? round(($count / $total) * 100, 1) : 0;
                                $riskClass = 'risk-' . strtolower(str_replace(' ', '-', $level));
                            @endphp
                            <tr>
                                <td class="{{ $riskClass }}" style="text-align: left; font-weight: bold; font-size: 7pt;">{{ $level }}</td>
                                <td style="font-size: 7pt;">{{ $count }}</td>
                                <td style="font-size: 7pt;">{{ $percentage }}%</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @endforeach
            </div>
        @endforeach

        @if(!$loop->last)
        <div class="page-break"></div>
        @endif
        @endforeach

    @else
    <p class="section-description" style="color: #666; font-style: italic;">
        No hay datos de dimensiones disponibles para mostrar.
    </p>
    @endif

    <p class="section-description" style="margin-top: 15px;">
        <strong>Importancia del análisis por dimensiones:</strong> Este nivel de detalle permite a la organización 
        diseñar intervenciones específicas y focalizadas. Mientras que las categorías y dominios ofrecen una visión 
        general, las dimensiones señalan exactamente qué aspectos requieren atención prioritaria según los resultados 
        obtenidos en la evaluación.
    </p>
</div>
