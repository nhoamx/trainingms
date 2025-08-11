@extends('reports.pdf.base')

@section('title', 'Reporte de Dominios')

@section('content')
<div class="section">
    <div class="section-title">Calificaciones por Dominio</div>
    
    @foreach($qualifications as $domain)
    <div class="chart-container">
        <div class="chart-title">{{ $domain['name'] }}</div>
        
        <table class="data-table">
            <thead>
                <tr>
                    <th>Nivel de Riesgo</th>
                    <th>Cantidad de Personas</th>
                    <th>Porcentaje</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $total = array_sum($domain['qualifications']);
                @endphp
                @foreach($domain['qualifications'] as $level => $count)
                <tr>
                    <td>{{ ucfirst($level) }}</td>
                    <td>{{ $count }}</td>
                    <td>{{ $total > 0 ? number_format(($count / $total) * 100, 1) : 0 }}%</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        
        @if($total > 0)
        <div style="margin-top: 15px;">
            <strong>Distribución Visual:</strong>
            @foreach($domain['qualifications'] as $level => $count)
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
        @endif
    </div>
    @endforeach
</div>

@if(isset($distribution) && !empty($distribution))
<div class="section page-break">
    <div class="section-title">Distribución de Riesgo por Dominio</div>
    
    <table class="data-table">
        <thead>
            <tr>
                <th>Dominio</th>
                <th>Nulo</th>
                <th>Bajo</th>
                <th>Medio</th>
                <th>Alto</th>
                <th>Muy Alto</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($distribution as $domain)
            <tr>
                <td><strong>{{ $domain['name'] }}</strong></td>
                <td>{{ $domain['risk_levels']['Nulo'] ?? 0 }}</td>
                <td>{{ $domain['risk_levels']['Bajo'] ?? 0 }}</td>
                <td>{{ $domain['risk_levels']['Medio'] ?? 0 }}</td>
                <td>{{ $domain['risk_levels']['Alto'] ?? 0 }}</td>
                <td>{{ $domain['risk_levels']['Muy Alto'] ?? 0 }}</td>
                <td>{{ array_sum($domain['risk_levels'] ?? []) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif

<div class="section">
    <div class="section-title">Resumen Estadístico</div>
    
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-number">{{ count($qualifications) }}</div>
            <div class="stat-label">Dominios Evaluados</div>
        </div>
        <div class="stat-card">
            @php
                $totalPeople = 0;
                foreach($qualifications as $domain) {
                    $totalPeople += array_sum($domain['qualifications']);
                }
            @endphp
            <div class="stat-number">{{ $totalPeople }}</div>
            <div class="stat-label">Total de Personas</div>
        </div>
        <div class="stat-card">
            @php
                $riskyDomainsCount = 0;
                foreach($qualifications as $domain) {
                    $high = $domain['qualifications']['Alto'] ?? 0;
                    $veryHigh = $domain['qualifications']['Muy Alto'] ?? 0;
                    if(($high + $veryHigh) > 0) $riskyDomainsCount++;
                }
            @endphp
            <div class="stat-number">{{ $riskyDomainsCount }}</div>
            <div class="stat-label">Dominios con Riesgo Alto/Muy Alto</div>
        </div>
        <div class="stat-card">
            @php
                $averageRiskPeople = 0;
                foreach($qualifications as $domain) {
                    $averageRiskPeople += ($domain['qualifications']['Alto'] ?? 0) + ($domain['qualifications']['Muy Alto'] ?? 0);
                }
            @endphp
            <div class="stat-number">{{ $averageRiskPeople }}</div>
            <div class="stat-label">Personas en Riesgo Alto/Muy Alto</div>
        </div>
    </div>
</div>
@endsection