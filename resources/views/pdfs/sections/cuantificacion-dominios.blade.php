{{-- Section 5.5.3: Cuantificación por Dominio --}}
<div class="section">
    <h3 class="section-title">5.5.3 CUANTIFICACIÓN POR DOMINIO</h3>
    
    <p class="section-description">
        Con referencia en la <strong>Tabla 6 de la Guía de Referencia III (NOM-035-STPS-2018)</strong> que agrupa 
        los ítems por categoría, dominio y dimensión, se obtuvo la <strong>Calificación de cada Dominio (Cdom)</strong>, 
        sumando el puntaje de cada uno de los ítems que lo integran. (Puntos establecidos en la Tabla 5 de la 
        Guía de Referencia II de la Norma Oficial Mexicana NOM-035-STPS-2018), los niveles de riesgo se identificaron 
        de la siguiente manera:
    </p>

    @if(isset($diagnosticData['domains']) && count($diagnosticData['domains']) > 0)
    
    <h4 style="font-size: 10pt; color: #1e40af; margin: 15px 0 10px 0; font-weight: bold;">
        Resumen de Incidencias
    </h4>
    
    <table>
        <thead>
            <tr>
                <th style="text-align: left; width: 35%;">Dominio</th>
                <th style="width: 11%;">Nulo</th>
                <th style="width: 11%;">Bajo</th>
                <th style="width: 11%;">Medio</th>
                <th style="width: 11%;">Alto</th>
                <th style="width: 10%;">Muy Alto</th>
                <th style="width: 11%;">Atender</th>
            </tr>
        </thead>
        <tbody>
            @foreach($diagnosticData['domains'] as $domainName => $levels)
            @php
                $atender = ($levels['Medio'] ?? 0) + ($levels['Alto'] ?? 0) + ($levels['Muy Alto'] ?? 0);
            @endphp
            <tr>
                <td style="text-align: left; font-weight: bold;">{{ $domainName }}</td>
                <td>{{ $levels['Nulo'] ?? 0 }}</td>
                <td>{{ $levels['Bajo'] ?? 0 }}</td>
                <td>{{ $levels['Medio'] ?? 0 }}</td>
                <td>{{ $levels['Alto'] ?? 0 }}</td>
                <td>{{ $levels['Muy Alto'] ?? 0 }}</td>
                <td><strong>{{ $atender }}</strong></td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <h4 style="font-size: 10pt; color: #1e40af; margin: 20px 0 10px 0; font-weight: bold;">
        Distribución de Niveles de Riesgo por Dominio
    </h4>
    
    <div class="charts-grid">
        @foreach($diagnosticData['domains'] as $domainName => $levels)
        @php
            $canvasId = 'domainChart_' . str_replace(' ', '_', $domainName);
            $total = array_sum($levels);
        @endphp
        <div class="chart-item">
            <h4 style="font-size: 9pt;">{{ $domainName }}</h4>
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
    
    @else
    <p class="section-description" style="color: #666; font-style: italic;">
        No hay datos de dominios disponibles para mostrar.
    </p>
    @endif
</div>
