@extends('reports.pdf.base')

@section('title', 'Reporte Demográfico')

@section('content')
<div class="section">
    <div class="section-title">Distribuciones Demográficas</div>
    
    @if(!empty($distributions))
        @foreach($distributions as $distribution)
        <div class="chart-container">
            <div class="chart-title">{{ $distribution['label'] }}</div>
            
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Categoría</th>
                        <th>Cantidad de Personas</th>
                        <th>Porcentaje</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $total = collect($distribution['data'])->sum('count');
                    @endphp
                    @foreach($distribution['data'] as $item)
                    <tr>
                        <td>{{ $item['label'] }}</td>
                        <td>{{ $item['count'] }}</td>
                        <td>{{ $total > 0 ? number_format(($item['count'] / $total) * 100, 1) : 0 }}%</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            
            @if($total > 0)
            <div style="margin-top: 15px;">
                <strong>Distribución Visual:</strong>
                @foreach($distribution['data'] as $index => $item)
                    @if($item['count'] > 0)
                        @php
                            $width = ($item['count'] / $total) * 100;
                            $colors = ['#2563eb', '#059669', '#d97706', '#dc2626', '#7c3aed', '#e11d48'];
                            $color = $colors[$index % count($colors)];
                        @endphp
                        <div style="background-color: {{ $color }}; height: 20px; width: {{ $width }}%; border-radius: 3px; margin: 2px 0; position: relative; overflow: hidden;">
                            <span style="position: absolute; left: 5px; top: 50%; transform: translateY(-50%); font-size: 10px; font-weight: bold; color: white;">
                                {{ $item['label'] }}: {{ $item['count'] }}
                            </span>
                        </div>
                    @endif
                @endforeach
            </div>
            @endif
        </div>
        @endforeach
    @else
        <p>No hay datos demográficos disponibles para mostrar.</p>
    @endif
</div>

<div class="section page-break">
    <div class="section-title">Resumen Demográfico Consolidado</div>
    
    @if(!empty($distributions))
        <table class="data-table">
            <thead>
                <tr>
                    <th>Campo Demográfico</th>
                    <th>Categorías Únicas</th>
                    <th>Total de Registros</th>
                    <th>Categoría Predominante</th>
                </tr>
            </thead>
            <tbody>
                @foreach($distributions as $distribution)
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
    @endif
</div>

<div class="section">
    <div class="section-title">Estadísticas Generales</div>
    
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-number">{{ count($distributions) }}</div>
            <div class="stat-label">Campos Demográficos Analizados</div>
        </div>
        <div class="stat-card">
            @php
                $totalResponses = 0;
                foreach($distributions as $distribution) {
                    $totalResponses += collect($distribution['data'])->sum('count');
                }
                $averageResponses = count($distributions) > 0 ? round($totalResponses / count($distributions)) : 0;
            @endphp
            <div class="stat-number">{{ $averageResponses }}</div>
            <div class="stat-label">Promedio de Respuestas por Campo</div>
        </div>
        <div class="stat-card">
            @php
                $totalCategories = 0;
                foreach($distributions as $distribution) {
                    $totalCategories += count($distribution['data']);
                }
            @endphp
            <div class="stat-number">{{ $totalCategories }}</div>
            <div class="stat-label">Total de Categorías Demográficas</div>
        </div>
        <div class="stat-card">
            @php
                $maxField = '';
                $maxCount = 0;
                foreach($distributions as $distribution) {
                    $fieldTotal = collect($distribution['data'])->sum('count');
                    if($fieldTotal > $maxCount) {
                        $maxCount = $fieldTotal;
                        $maxField = $distribution['label'];
                    }
                }
            @endphp
            <div class="stat-number">{{ $maxCount }}</div>
            <div class="stat-label">Mayor Participación: {{ $maxField }}</div>
        </div>
    </div>
</div>

@if(!empty($distributions))
<div class="section">
    <div class="section-title">Análisis por Campo Demográfico</div>
    
    @foreach($distributions as $distribution)
        @php
            $total = collect($distribution['data'])->sum('count');
            $diversity = count($distribution['data']);
            $topCategory = collect($distribution['data'])->sortByDesc('count')->first();
            $dominancePercentage = $total > 0 ? ($topCategory['count'] / $total) * 100 : 0;
        @endphp
        <div style="border: 1px solid #e5e7eb; border-radius: 5px; padding: 10px; margin-bottom: 10px;">
            <h4 style="margin: 0 0 10px 0; color: #2563eb;">{{ $distribution['label'] }}</h4>
            <p style="margin: 5px 0; font-size: 11px;">
                <strong>Diversidad:</strong> {{ $diversity }} categorías diferentes<br>
                <strong>Participación total:</strong> {{ $total }} personas<br>
                <strong>Categoría dominante:</strong> {{ $topCategory['label'] ?? 'N/A' }} 
                ({{ number_format($dominancePercentage, 1) }}% del total)
            </p>
        </div>
    @endforeach
</div>
@endif
@endsection