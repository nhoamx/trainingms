{{-- Section 5.5.1: Calificación Final --}}
<div class="section">
    <h3 class="section-title">5.5.1 Calificación Final</h3>
    
    <p class="section-description">
        <strong>Interpretación:</strong> La calificación final corresponde al nivel de riesgo psicosocial obtenido 
        por cada trabajador evaluado, considerando de manera integral todas las categorías, dominios y dimensiones 
        evaluadas según la Guía de referencia III de la NOM-035-STPS-2018.
    </p>

    {{-- Gráfica de Calificación Final --}}
    <div class="chart-container">
        <canvas id="finalRiskChart"></canvas>
    </div>

    {{-- Tabla de Calificación Final por Área --}}
    @if(isset($diagnosticData['final_risk_by_area']) && count($diagnosticData['final_risk_by_area']) > 0)
    <h4 style="font-size: 10pt; color: #1e40af; margin-top: 15px; margin-bottom: 10px;">
        Distribución de Calificación Final por Área
    </h4>
    <table>
        <thead>
            <tr>
                <th style="width: 25%;">Área</th>
                <th style="width: 10%;">Total</th>
                <th style="width: 10%;">Nulo</th>
                <th style="width: 10%;">Bajo</th>
                <th style="width: 10%;">Medio</th>
                <th style="width: 10%;">Alto</th>
                <th style="width: 10%;">Muy Alto</th>
                <th style="width: 15%;">% Atención</th>
            </tr>
        </thead>
        <tbody>
            @foreach($diagnosticData['final_risk_by_area'] as $area => $data)
            @php
                $total = $data['Nulo'] + $data['Bajo'] + $data['Medio'] + $data['Alto'] + $data['Muy Alto'];
                $atencion = $data['Medio'] + $data['Alto'] + $data['Muy Alto'];
                $porcentajeAtencion = $total > 0 ? round(($atencion / $total) * 100, 1) : 0;
            @endphp
            <tr>
                <td style="text-align: left; font-weight: bold;">{{ $area }}</td>
                <td><strong>{{ $total }}</strong></td>
                <td class="risk-nulo">{{ $data['Nulo'] }}</td>
                <td class="risk-bajo">{{ $data['Bajo'] }}</td>
                <td class="risk-medio">{{ $data['Medio'] }}</td>
                <td class="risk-alto">{{ $data['Alto'] }}</td>
                <td class="risk-muy-alto">{{ $data['Muy Alto'] }}</td>
                <td style="font-weight: bold;">{{ $porcentajeAtencion }}%</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    {{-- Tabla de Calificación Final por Puesto --}}
    @if(isset($diagnosticData['final_risk_by_puesto']) && count($diagnosticData['final_risk_by_puesto']) > 0)
    <h4 style="font-size: 10pt; color: #1e40af; margin-top: 15px; margin-bottom: 10px;">
        Distribución de Calificación Final por Puesto
    </h4>
    <table>
        <thead>
            <tr>
                <th style="width: 25%;">Puesto</th>
                <th style="width: 10%;">Total</th>
                <th style="width: 10%;">Nulo</th>
                <th style="width: 10%;">Bajo</th>
                <th style="width: 10%;">Medio</th>
                <th style="width: 10%;">Alto</th>
                <th style="width: 10%;">Muy Alto</th>
                <th style="width: 15%;">% Atención</th>
            </tr>
        </thead>
        <tbody>
            @foreach($diagnosticData['final_risk_by_puesto'] as $puesto => $data)
            @php
                $total = $data['Nulo'] + $data['Bajo'] + $data['Medio'] + $data['Alto'] + $data['Muy Alto'];
                $atencion = $data['Medio'] + $data['Alto'] + $data['Muy Alto'];
                $porcentajeAtencion = $total > 0 ? round(($atencion / $total) * 100, 1) : 0;
            @endphp
            <tr>
                <td style="text-align: left; font-weight: bold;">{{ $puesto }}</td>
                <td><strong>{{ $total }}</strong></td>
                <td class="risk-nulo">{{ $data['Nulo'] }}</td>
                <td class="risk-bajo">{{ $data['Bajo'] }}</td>
                <td class="risk-medio">{{ $data['Medio'] }}</td>
                <td class="risk-alto">{{ $data['Alto'] }}</td>
                <td class="risk-muy-alto">{{ $data['Muy Alto'] }}</td>
                <td style="font-weight: bold;">{{ $porcentajeAtencion }}%</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    <p class="section-description" style="margin-top: 15px;">
        <strong>Nota:</strong> El porcentaje de atención (% Atención) representa la proporción de trabajadores 
        que requieren intervención (niveles Medio, Alto y Muy Alto) respecto al total evaluado en cada área o puesto.
        Los trabajadores con calificación Nulo o Bajo no requieren intervención según la NOM-035-STPS-2018.
    </p>
</div>
