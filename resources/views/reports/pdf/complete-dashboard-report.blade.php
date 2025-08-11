@extends('reports.pdf.base')

@section('title', 'Reporte Completo del Dashboard')

@section('content')
<!-- Executive Summary -->
<div class="section">
    <div class="section-title">Resumen Ejecutivo</div>
    
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-number">{{ count($categoryQualifications) }}</div>
            <div class="stat-label">Categorías Evaluadas</div>
        </div>
        <div class="stat-card">
            <div class="stat-number">{{ count($domainQualifications) }}</div>
            <div class="stat-label">Dominios Evaluados</div>
        </div>
        <div class="stat-card">
            <div class="stat-number">{{ count($demographicDistributions) }}</div>
            <div class="stat-label">Campos Demográficos</div>
        </div>
        <div class="stat-card">
            @php
                $totalPeople = 0;
                foreach($categoryQualifications as $category) {
                    $categoryTotal = array_sum($category['qualifications']);
                    if($categoryTotal > $totalPeople) $totalPeople = $categoryTotal;
                }
            @endphp
            <div class="stat-number">{{ $totalPeople }}</div>
            <div class="stat-label">Total de Personas</div>
        </div>
    </div>
</div>

<!-- Categories Section -->
<div class="section page-break">
    <div class="section-title">1. Análisis por Categorías</div>
    
    <table class="data-table">
        <thead>
            <tr>
                <th>Categoría</th>
                <th>Nulo</th>
                <th>Bajo</th>
                <th>Medio</th>
                <th>Alto</th>
                <th>Muy Alto</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($categoryQualifications as $category)
            <tr>
                <td><strong>{{ $category['name'] }}</strong></td>
                <td>{{ $category['qualifications']['Nulo'] ?? 0 }}</td>
                <td>{{ $category['qualifications']['Bajo'] ?? 0 }}</td>
                <td>{{ $category['qualifications']['Medio'] ?? 0 }}</td>
                <td>{{ $category['qualifications']['Alto'] ?? 0 }}</td>
                <td>{{ $category['qualifications']['Muy Alto'] ?? 0 }}</td>
                <td>{{ array_sum($category['qualifications']) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    
    <div style="margin-top: 20px;">
        <h4>Categorías con Mayor Riesgo:</h4>
        @php
            $riskyCategoriesWithData = [];
            foreach($categoryQualifications as $category) {
                $highRisk = ($category['qualifications']['Alto'] ?? 0) + ($category['qualifications']['Muy Alto'] ?? 0);
                $total = array_sum($category['qualifications']);
                if($highRisk > 0 && $total > 0) {
                    $riskyCategoriesWithData[] = [
                        'name' => $category['name'],
                        'risk_count' => $highRisk,
                        'risk_percentage' => ($highRisk / $total) * 100
                    ];
                }
            }
            // Sort by risk percentage descending
            usort($riskyCategoriesWithData, function($a, $b) {
                return $b['risk_percentage'] <=> $a['risk_percentage'];
            });
        @endphp
        
        @if(!empty($riskyCategoriesWithData))
            <ul style="font-size: 11px;">
                @foreach(array_slice($riskyCategoriesWithData, 0, 5) as $category)
                <li>
                    <strong>{{ $category['name'] }}:</strong> 
                    {{ $category['risk_count'] }} personas en riesgo alto/muy alto 
                    ({{ number_format($category['risk_percentage'], 1) }}%)
                </li>
                @endforeach
            </ul>
        @else
            <p style="font-size: 11px;">No se identificaron categorías con riesgo alto o muy alto.</p>
        @endif
    </div>
</div>

<!-- Domains Section -->
<div class="section page-break">
    <div class="section-title">2. Análisis por Dominios</div>
    
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
            @foreach($domainQualifications as $domain)
            <tr>
                <td><strong>{{ $domain['name'] }}</strong></td>
                <td>{{ $domain['qualifications']['Nulo'] ?? 0 }}</td>
                <td>{{ $domain['qualifications']['Bajo'] ?? 0 }}</td>
                <td>{{ $domain['qualifications']['Medio'] ?? 0 }}</td>
                <td>{{ $domain['qualifications']['Alto'] ?? 0 }}</td>
                <td>{{ $domain['qualifications']['Muy Alto'] ?? 0 }}</td>
                <td>{{ array_sum($domain['qualifications']) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    
    <div style="margin-top: 20px;">
        <h4>Dominios con Mayor Riesgo:</h4>
        @php
            $riskyDomainsWithData = [];
            foreach($domainQualifications as $domain) {
                $highRisk = ($domain['qualifications']['Alto'] ?? 0) + ($domain['qualifications']['Muy Alto'] ?? 0);
                $total = array_sum($domain['qualifications']);
                if($highRisk > 0 && $total > 0) {
                    $riskyDomainsWithData[] = [
                        'name' => $domain['name'],
                        'risk_count' => $highRisk,
                        'risk_percentage' => ($highRisk / $total) * 100
                    ];
                }
            }
            // Sort by risk percentage descending
            usort($riskyDomainsWithData, function($a, $b) {
                return $b['risk_percentage'] <=> $a['risk_percentage'];
            });
        @endphp
        
        @if(!empty($riskyDomainsWithData))
            <ul style="font-size: 11px;">
                @foreach(array_slice($riskyDomainsWithData, 0, 5) as $domain)
                <li>
                    <strong>{{ $domain['name'] }}:</strong> 
                    {{ $domain['risk_count'] }} personas en riesgo alto/muy alto 
                    ({{ number_format($domain['risk_percentage'], 1) }}%)
                </li>
                @endforeach
            </ul>
        @else
            <p style="font-size: 11px;">No se identificaron dominios con riesgo alto o muy alto.</p>
        @endif
    </div>
</div>

<!-- Demographics Section -->
<div class="section page-break">
    <div class="section-title">3. Análisis Demográfico</div>
    
    @if(!empty($demographicDistributions))
        <table class="data-table">
            <thead>
                <tr>
                    <th>Campo Demográfico</th>
                    <th>Categorías</th>
                    <th>Total Participantes</th>
                    <th>Categoría Predominante</th>
                </tr>
            </thead>
            <tbody>
                @foreach($demographicDistributions as $distribution)
                    @php
                        $total = collect($distribution['data'])->sum('count');
                        $predominant = collect($distribution['data'])->sortByDesc('count')->first();
                        $uniqueCategories = count($distribution['data']);
                    @endphp
                    <tr>
                        <td><strong>{{ $distribution['label'] }}</strong></td>
                        <td>{{ $uniqueCategories }}</td>
                        <td>{{ $total }}</td>
                        <td>{{ $predominant['label'] ?? 'N/A' }} ({{ $predominant['count'] ?? 0 }})</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        
        <div style="margin-top: 20px;">
            <h4>Distribuciones Demográficas Principales:</h4>
            @foreach(array_slice($demographicDistributions, 0, 3) as $distribution)
                @php
                    $total = collect($distribution['data'])->sum('count');
                    $topThree = collect($distribution['data'])->sortByDesc('count')->take(3);
                @endphp
                <div style="margin-bottom: 15px; font-size: 11px;">
                    <strong>{{ $distribution['label'] }}:</strong>
                    <ul style="margin: 5px 0;">
                        @foreach($topThree as $item)
                        <li>{{ $item['label'] }}: {{ $item['count'] }} personas ({{ $total > 0 ? number_format(($item['count'] / $total) * 100, 1) : 0 }}%)</li>
                        @endforeach
                    </ul>
                </div>
            @endforeach
        </div>
    @else
        <p>No hay datos demográficos disponibles para mostrar.</p>
    @endif
</div>

<!-- Risk Analysis Summary -->
<div class="section page-break">
    <div class="section-title">4. Análisis de Riesgo Consolidado</div>
    
    @php
        $totalHighRiskPeople = 0;
        $totalMediumRiskPeople = 0;
        $totalLowRiskPeople = 0;
        $totalNoRiskPeople = 0;
        $totalEvaluatedPeople = 0;
        
        foreach($categoryQualifications as $category) {
            $totalHighRiskPeople += ($category['qualifications']['Alto'] ?? 0) + ($category['qualifications']['Muy Alto'] ?? 0);
            $totalMediumRiskPeople += $category['qualifications']['Medio'] ?? 0;
            $totalLowRiskPeople += $category['qualifications']['Bajo'] ?? 0;
            $totalNoRiskPeople += $category['qualifications']['Nulo'] ?? 0;
        }
        
        $totalEvaluatedPeople = $totalHighRiskPeople + $totalMediumRiskPeople + $totalLowRiskPeople + $totalNoRiskPeople;
    @endphp
    
    <div class="chart-container">
        <div class="chart-title">Distribución Global de Riesgo</div>
        
        <table class="data-table">
            <thead>
                <tr>
                    <th>Nivel de Riesgo</th>
                    <th>Cantidad de Evaluaciones</th>
                    <th>Porcentaje</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Sin Riesgo (Nulo)</td>
                    <td>{{ $totalNoRiskPeople }}</td>
                    <td>{{ $totalEvaluatedPeople > 0 ? number_format(($totalNoRiskPeople / $totalEvaluatedPeople) * 100, 1) : 0 }}%</td>
                </tr>
                <tr>
                    <td>Riesgo Bajo</td>
                    <td>{{ $totalLowRiskPeople }}</td>
                    <td>{{ $totalEvaluatedPeople > 0 ? number_format(($totalLowRiskPeople / $totalEvaluatedPeople) * 100, 1) : 0 }}%</td>
                </tr>
                <tr>
                    <td>Riesgo Medio</td>
                    <td>{{ $totalMediumRiskPeople }}</td>
                    <td>{{ $totalEvaluatedPeople > 0 ? number_format(($totalMediumRiskPeople / $totalEvaluatedPeople) * 100, 1) : 0 }}%</td>
                </tr>
                <tr style="background-color: #fef2f2;">
                    <td><strong>Riesgo Alto/Muy Alto</strong></td>
                    <td><strong>{{ $totalHighRiskPeople }}</strong></td>
                    <td><strong>{{ $totalEvaluatedPeople > 0 ? number_format(($totalHighRiskPeople / $totalEvaluatedPeople) * 100, 1) : 0 }}%</strong></td>
                </tr>
            </tbody>
        </table>
    </div>
    
    <div style="margin-top: 20px;">
        <h4>Recomendaciones Basadas en el Análisis:</h4>
        <ul style="font-size: 11px;">
            @if($totalHighRiskPeople > 0)
            <li><strong>Atención Prioritaria:</strong> {{ $totalHighRiskPeople }} evaluaciones requieren intervención inmediata por riesgo alto/muy alto.</li>
            @endif
            
            @if($totalMediumRiskPeople > 0)
            <li><strong>Seguimiento:</strong> {{ $totalMediumRiskPeople }} evaluaciones en riesgo medio requieren monitoreo y acciones preventivas.</li>
            @endif
            
            @if(!empty($riskyCategoriesWithData))
            <li><strong>Enfocar en:</strong> Las categorías {{ implode(', ', array_column(array_slice($riskyCategoriesWithData, 0, 3), 'name')) }} muestran los mayores niveles de riesgo.</li>
            @endif
            
            @if(!empty($riskyDomainsWithData))
            <li><strong>Dominios críticos:</strong> Los dominios {{ implode(', ', array_column(array_slice($riskyDomainsWithData, 0, 3), 'name')) }} requieren atención especial.</li>
            @endif
        </ul>
    </div>
</div>
@endsection