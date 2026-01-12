@php
    // Extract Violence domain data from diagnosticData
    // The data is now in format: domains['Violencia'] = ['Nulo' => count, 'Bajo' => count, ...]
    $violenceDistribution = $diagnosticData['domains']['Violencia'] ?? [
        'Nulo' => 0,
        'Bajo' => 0,
        'Medio' => 0,
        'Alto' => 0,
        'Muy Alto' => 0,
    ];
    
    $totalParticipants = $diagnosticData['total_participants'] ?? 0;
@endphp

<div class="section">
    <h2 class="section-title">5.3 VIOLENCIA LABORAL</h2>
    
    <div class="section-content">
        <p style="margin-bottom: 12px;">
            Aquellos actos de hostigamiento, acoso o malos tratos en contra del trabajador, que pueden dañar su integridad o salud, se identificaron mediante las preguntas 57, 58, 59, 60, 61, 62, 63 y 64, del C72, Cuestionario para identificar los factores de riesgo psicosocial y evaluar el entorno organizacional en los centros de trabajo, NOM-035-STPS-2018 Guía de Referencia III. La distribución en los niveles de riesgo es la siguiente:
        </p>

        <h3 style="font-size: 10.5pt; color: #1e40af; margin: 15px 0 10px 0; font-weight: bold;">
            Distribución en los niveles de riesgo (Dominio: Violencia)
        </h3>

        <table>
            <thead>
                <tr>
                    <th style="text-align: left; width: 30%;">Calificación Total del Dominio</th>
                    <th style="width: 14%;">Nulo<br>(Cdom&lt;7)</th>
                    <th style="width: 14%;">Bajo<br>(6&lt;=Cdom&lt;10)</th>
                    <th style="width: 14%;">Medio<br>(9&lt;=Cdom&lt;13)</th>
                    <th style="width: 14%;">Alto<br>(12&lt;=Cdom&lt;16)</th>
                    <th style="width: 14%;">Muy Alto<br>(Cdom&gt;=16)</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td style="text-align: left; font-weight: bold;">Violencia</td>
                    <td><strong>{{ $violenceDistribution['Nulo'] }}</strong></td>
                    <td><strong>{{ $violenceDistribution['Bajo'] }}</strong></td>
                    <td><strong>{{ $violenceDistribution['Medio'] }}</strong></td>
                    <td><strong>{{ $violenceDistribution['Alto'] }}</strong></td>
                    <td><strong>{{ $violenceDistribution['Muy Alto'] }}</strong></td>
                </tr>
            </tbody>
        </table>

        @if($totalParticipants > 0)
        <div style="margin: 20px 0;">
            <h3 style="font-size: 10.5pt; color: #1e40af; margin-bottom: 10px; font-weight: bold;">
                Gráfico de Distribución: Total de Participantes {{ $totalParticipants }}
            </h3>
            <p style="font-size: 9pt; margin-bottom: 15px; color: #666;">
                El siguiente gráfico visualiza la distribución porcentual de los {{ $totalParticipants }} participantes según su nivel de riesgo en el dominio "Violencia".
            </p>
            
            <div class="chart-container" style="height: 350px; position: relative; margin: 20px auto; max-width: 600px;">
                <canvas id="violencePieChart"></canvas>
            </div>
        </div>
        @endif

        <h3 style="font-size: 10.5pt; color: #1e40af; margin: 20px 0 10px 0; font-weight: bold;">
            Preguntas realizadas en los cuestionarios que dan origen a los datos mostrados
        </h3>

        <table>
            <thead>
                <tr>
                    <th style="text-align: left; width: 60%;">Pregunta</th>
                    <th style="width: 8%;">SI</th>
                    <th style="width: 8%;">CS</th>
                    <th style="width: 8%;">AV</th>
                    <th style="width: 8%;">CN</th>
                    <th style="width: 8%;">NU</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $violenceQuestions = [
                        57 => '57. En mi trabajo debo brindar servicio a clientes o usuarios',
                        58 => '58. Atiendo clientes o usuarios muy enojados',
                        59 => '59. Mi trabajo me exige atender personas muy necesitadas de ayuda o enfermas',
                        60 => '60. Para hacer mi trabajo debo demostrar sentimientos distintos a los míos',
                        61 => '61. Mi trabajo me exige atender situaciones de violencia',
                        62 => '62. Comunican tarde los asuntos de trabajo',
                        63 => '63. Dificultan el logro de los resultados de mi trabajo',
                        64 => '64. Cooperación y trabajo en equipo',
                    ];
                @endphp
                @foreach($violenceQuestions as $qNum => $qText)
                @php
                    $responses = $diagnosticData['response_frequencies'][$qNum] ?? ['SI' => 0, 'CS' => 0, 'AV' => 0, 'CN' => 0, 'NU' => 0];
                @endphp
                <tr>
                    <td style="text-align: left;">{{ $qText }}</td>
                    <td>{{ $responses['SI'] }}</td>
                    <td>{{ $responses['CS'] }}</td>
                    <td>{{ $responses['AV'] }}</td>
                    <td>{{ $responses['CN'] }}</td>
                    <td>{{ $responses['NU'] }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <p style="margin-top: 15px; font-size: 9pt; font-style: italic; color: #666;">
            Nota: SI = Siempre, CS = Casi siempre, AV = Algunas veces, CN = Casi nunca, NU = Nunca. 
            Los datos completos de las 72 preguntas se encuentran en la sección 5.5.5.
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

    .section-content h3 {
        font-weight: bold;
    }

    .section-content table {
        font-size: 9pt;
    }
</style>
