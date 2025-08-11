@extends('reports.pdf.base')

@section('title', 'Reporte de Dimensiones')

@section('content')
<div class="section">
    <div class="section-title">Distribución de Riesgo por Dimensión</div>
    
    @if(isset($distribution) && !empty($distribution))
        <table class="data-table">
            <thead>
                <tr>
                    <th>Dimensión</th>
                    <th>Nulo</th>
                    <th>Bajo</th>
                    <th>Medio</th>
                    <th>Alto</th>
                    <th>Muy Alto</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($distribution as $dimension)
                <tr>
                    <td><strong>{{ $dimension['name'] }}</strong></td>
                    <td>{{ $dimension['risk_levels']['Nulo'] ?? 0 }}</td>
                    <td>{{ $dimension['risk_levels']['Bajo'] ?? 0 }}</td>
                    <td>{{ $dimension['risk_levels']['Medio'] ?? 0 }}</td>
                    <td>{{ $dimension['risk_levels']['Alto'] ?? 0 }}</td>
                    <td>{{ $dimension['risk_levels']['Muy Alto'] ?? 0 }}</td>
                    <td>{{ array_sum($dimension['risk_levels'] ?? []) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        
        <div style="margin-top: 20px;">
            @foreach($distribution as $dimension)
                @php
                    $total = array_sum($dimension['risk_levels'] ?? []);
                @endphp
                @if($total > 0)
                <div class="chart-container">
                    <div class="chart-title">{{ $dimension['name'] }}</div>
                    
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Nivel de Riesgo</th>
                                <th>Cantidad de Personas</th>
                                <th>Porcentaje</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($dimension['risk_levels'] as $level => $count)
                            <tr>
                                <td>{{ ucfirst($level) }}</td>
                                <td>{{ $count }}</td>
                                <td>{{ $total > 0 ? number_format(($count / $total) * 100, 1) : 0 }}%</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                    
                    <div style="margin-top: 15px;">
                        <strong>Distribución Visual:</strong>
                        @foreach($dimension['risk_levels'] as $level => $count)
                            @if($count > 0)
                                @php
                                    $width = ($count / $total) * 100;
                                    $levelClass = strtolower(str_replace(' ', '-', $level));
                                @endphp
                                <div class="qualification-bar {{ $levelClass }}" style="width: {{ $width }}%;">
                                    <span class="qualification-label">{{ ucfirst($level) }}: {{ $count }}</span>
                                </div>
                            @endif
                        @endforeach
                    </div>
                </div>
                @endif
            @endforeach
        </div>
    @else
        <p>No hay datos de dimensiones disponibles para mostrar.</p>
    @endif
</div>

<div class="section">
    <div class="section-title">Resumen Estadístico</div>
    
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-number">{{ isset($distribution) ? count($distribution) : 0 }}</div>
            <div class="stat-label">Dimensiones Evaluadas</div>
        </div>
        <div class="stat-card">
            @php
                $totalPeople = 0;
                if(isset($distribution)) {
                    foreach($distribution as $dimension) {
                        $totalPeople += array_sum($dimension['risk_levels'] ?? []);
                    }
                }
            @endphp
            <div class="stat-number">{{ $totalPeople }}</div>
            <div class="stat-label">Total de Personas</div>
        </div>
        <div class="stat-card">
            @php
                $riskyDimensionsCount = 0;
                if(isset($distribution)) {
                    foreach($distribution as $dimension) {
                        $high = $dimension['risk_levels']['Alto'] ?? 0;
                        $veryHigh = $dimension['risk_levels']['Muy Alto'] ?? 0;
                        if(($high + $veryHigh) > 0) $riskyDimensionsCount++;
                    }
                }
            @endphp
            <div class="stat-number">{{ $riskyDimensionsCount }}</div>
            <div class="stat-label">Dimensiones con Riesgo Alto/Muy Alto</div>
        </div>
        <div class="stat-card">
            @php
                $averageRiskPeople = 0;
                if(isset($distribution)) {
                    foreach($distribution as $dimension) {
                        $averageRiskPeople += ($dimension['risk_levels']['Alto'] ?? 0) + ($dimension['risk_levels']['Muy Alto'] ?? 0);
                    }
                }
            @endphp
            <div class="stat-number">{{ $averageRiskPeople }}</div>
            <div class="stat-label">Personas en Riesgo Alto/Muy Alto</div>
        </div>
    </div>
</div>
@endsection