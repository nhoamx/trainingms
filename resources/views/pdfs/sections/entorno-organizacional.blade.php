@php
    // Extract organizational environment domains from diagnosticData
    // The data is now in format: domains['DomainName'] = ['Nulo' => count, 'Bajo' => count, ...]
    $relevantDomains = [
        'Carga de trabajo',
        'Falta de control sobre el trabajo',
        'Jornada de trabajo',
        'Liderazgo',
        'Reconocimiento del desempeño',
        'Insuficiente sentido de pertenencia e inestabilidad'
    ];
    
    $allRelevantDomainsData = [];
    
    foreach ($relevantDomains as $domainName) {
        if (isset($diagnosticData['domains'][$domainName])) {
            $levels = $diagnosticData['domains'][$domainName];
            
            // Calculate "Atención" (Medio + Alto + Muy Alto)
            $levels['Atención'] = ($levels['Medio'] ?? 0) + ($levels['Alto'] ?? 0) + ($levels['Muy Alto'] ?? 0);
            
            $allRelevantDomainsData[$domainName] = $levels;
        }
    }
@endphp

<div class="section">
    <h2 class="section-title">5.4 ENTORNO ORGANIZACIONAL</h2>
    
    <div class="section-content">
        <p style="margin-bottom: 12px;">
            Para determinar el nivel de riesgo, se consideró: el sentido de pertenencia de los trabajadores al centro de trabajo; la formación para la adecuada realización de las tareas encomendadas; la definición precisa de responsabilidades para los trabajadores; la participación proactiva y comunicación entre el patrón, sus representantes y los trabajadores; la distribución adecuada de cargas de trabajo, con jornadas de trabajo regulares, y la evaluación y el reconocimiento del desempeño, obteniendo la siguiente distribución:
        </p>

        <table>
            <thead>
                <tr>
                    <th style="text-align: left; width: 30%;">Descripción</th>
                    <th style="width: 12%;">Nulo</th>
                    <th style="width: 12%;">Bajo</th>
                    <th style="width: 12%;">Medio</th>
                    <th style="width: 12%;">Alto</th>
                    <th style="width: 10%;">Muy Alto</th>
                    <th style="width: 12%;">Atención</th>
                </tr>
            </thead>
            <tbody>
                @foreach($allRelevantDomainsData as $domainName => $levels)
                <tr>
                    <td style="text-align: left;">{{ $domainName }}</td>
                    <td>{{ $levels['Nulo'] }}</td>
                    <td>{{ $levels['Bajo'] }}</td>
                    <td>{{ $levels['Medio'] }}</td>
                    <td>{{ $levels['Alto'] }}</td>
                    <td>{{ $levels['Muy Alto'] }}</td>
                    <td><strong>{{ $levels['Atención'] }}</strong></td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <p style="margin-top: 15px; font-size: 9pt; font-style: italic; color: #666;">
            ** Las gráficas de cada dominio se encuentran en la sección V.5.3 - Cuantificación por Dominio.
        </p>
    </div>
</div>

<style>
    .section-content {
        font-size: 9.5pt;
        line-height: 1.6;
        color: #333;
    }

    .section-content p {
        text-align: justify;
    }

    .section-content table {
        font-size: 9pt;
    }
</style>
