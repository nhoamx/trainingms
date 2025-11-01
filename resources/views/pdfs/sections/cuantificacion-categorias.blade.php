{{-- Section 5.5.2: Cuantificación por Categoría --}}
<div class="section">
    <h3 class="section-title">5.5.2 CUANTIFICACIÓN POR CATEGORÍA</h3>
    
    <p class="section-description">
        Con referencia en la <strong>Tabla 6 de la Guía de Referencia III (NOM-035-STPS-2018)</strong> que agrupa 
        los ítems por categoría, dominio y dimensión, se obtuvo la <strong>Calificación de cada Categoría (Ccat)</strong>, 
        sumando el puntaje de cada uno de los ítems que la integran. (Puntos establecidos en la Tabla 5 de la 
        Guía de Referencia II de la Norma Oficial Mexicana NOM-035-STPS-2018). Las calificaciones obtenidas se 
        distribuyeron en los niveles de riesgo de la siguiente manera:
    </p>

    @if(isset($diagnosticData['categories']) && count($diagnosticData['categories']) > 0)
    
    <h4 style="font-size: 10pt; color: #1e40af; margin: 15px 0 10px 0; font-weight: bold;">
        Cuantificación
    </h4>
    
    <table>
        <thead>
            <tr>
                <th style="text-align: left; width: 35%;">Categoría</th>
                <th style="width: 11%;">Nulo</th>
                <th style="width: 11%;">Bajo</th>
                <th style="width: 11%;">Medio</th>
                <th style="width: 11%;">Alto</th>
                <th style="width: 10%;">Muy Alto</th>
                <th style="width: 11%;">Atención</th>
            </tr>
        </thead>
        <tbody>
            @foreach($diagnosticData['categories'] as $categoryName => $levels)
            @php
                $atencion = ($levels['Medio'] ?? 0) + ($levels['Alto'] ?? 0) + ($levels['Muy Alto'] ?? 0);
            @endphp
            <tr>
                <td style="text-align: left; font-weight: bold;">{{ $categoryName }}</td>
                <td>{{ $levels['Nulo'] ?? 0 }}</td>
                <td>{{ $levels['Bajo'] ?? 0 }}</td>
                <td>{{ $levels['Medio'] ?? 0 }}</td>
                <td>{{ $levels['Alto'] ?? 0 }}</td>
                <td>{{ $levels['Muy Alto'] ?? 0 }}</td>
                <td><strong>{{ $atencion }}</strong></td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <h4 style="font-size: 10pt; color: #1e40af; margin: 20px 0 10px 0; font-weight: bold;">
        Distribución de Niveles de Riesgo por Categoría
    </h4>
    
    <div class="charts-grid">
        @foreach($diagnosticData['categories'] as $categoryName => $levels)
        @php
            $canvasId = 'categoryChart_' . str_replace(' ', '_', $categoryName);
            $total = array_sum($levels);
        @endphp
        <div class="chart-item">
            <h4>{{ $categoryName }}</h4>
            <div class="chart-item-canvas">
                <canvas id="{{ $canvasId }}"></canvas>
            </div>
            <table style="margin-top: 10px; font-size: 8pt;">
                <thead>
                    <tr>
                        <th style="font-size: 8pt;">Nivel</th>
                        <th style="font-size: 8pt;">N°</th>
                        <th style="font-size: 8pt;">%</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach(['Nulo', 'Bajo', 'Medio', 'Alto', 'Muy Alto'] as $level)
                    @php
                        $count = $levels[$level] ?? 0;
                        $percentage = $total > 0 ? round(($count / $total) * 100, 1) : 0;
                        $riskClass = 'risk-' . strtolower(str_replace(' ', '-', $level));
                    @endphp
                    <tr>
                        <td class="{{ $riskClass }}" style="text-align: left; font-weight: bold; font-size: 8pt;">{{ $level }}</td>
                        <td style="font-size: 8pt;">{{ $count }}</td>
                        <td style="font-size: 8pt;">{{ $percentage }}%</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endforeach
    </div>

    <p class="section-description" style="margin-top: 15px;">
        <strong>Definiciones de las Categorías según NOM-035:</strong>
    </p>
    <ul style="font-size: 9pt; margin-left: 20px; margin-top: 5px;">
        <li><strong>Ambiente de trabajo:</strong> Condiciones del lugar de trabajo que pueden afectar la salud del trabajador 
        (condiciones peligrosas, inseguras o deficientes).</li>
        <li><strong>Factores propios de la actividad:</strong> Características de las tareas que realiza el trabajador 
        (carga de trabajo, ritmo, contradicciones en las demandas).</li>
        <li><strong>Organización del tiempo de trabajo:</strong> Aspectos relacionados con la jornada laboral, 
        turnos, rotaciones, duración y distribución del tiempo de trabajo.</li>
    </ul>
    
    @else
    <p class="section-description" style="color: #666; font-style: italic;">
        No hay datos de categorías disponibles para mostrar.
    </p>
    @endif
</div>
