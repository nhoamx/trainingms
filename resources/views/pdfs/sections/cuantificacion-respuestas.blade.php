{{-- Section 5.5.5: Cuantificación por Opciones de Respuesta --}}
<div class="section">
    <h3 class="section-title">5.5.5 Cuantificación por Opciones de Respuesta</h3>
    
    <p class="section-description">
        <strong>Interpretación:</strong> Esta sección presenta la frecuencia de respuestas para cada una de las 72 preguntas 
        del cuestionario de Referencia III de la NOM-035-STPS-2018. Las opciones de respuesta son: 
        <strong>Siempre (SI)</strong>, <strong>Casi siempre (CS)</strong>, <strong>Algunas veces (AV)</strong>, 
        <strong>Casi nunca (CN)</strong> y <strong>Nunca (NU)</strong>.
    </p>

    @if(isset($diagnosticData['response_frequencies']) && count($diagnosticData['response_frequencies']) > 0)
        <table>
            <thead>
                <tr>
                    <th style="width: 5%;">No.</th>
                    <th style="width: 45%;">Pregunta</th>
                    <th style="width: 10%;">SI</th>
                    <th style="width: 10%;">CS</th>
                    <th style="width: 10%;">AV</th>
                    <th style="width: 10%;">CN</th>
                    <th style="width: 10%;">NU</th>
                </tr>
            </thead>
            <tbody>
                @foreach($diagnosticData['response_frequencies'] as $questionNumber => $responses)
                @php
                    $questionText = $diagnosticData['question_texts'][$questionNumber] ?? "Pregunta {$questionNumber}";
                    $total = array_sum($responses);
                @endphp
                <tr>
                    <td style="text-align: center; font-weight: bold;">{{ $questionNumber }}</td>
                    <td style="text-align: left; font-size: 8pt;">{{ $questionText }}</td>
                    <td>{{ $responses['SI'] ?? 0 }}<br><span style="font-size: 7pt; color: #666;">({{ $total > 0 ? round(($responses['SI'] ?? 0) / $total * 100, 1) : 0 }}%)</span></td>
                    <td>{{ $responses['CS'] ?? 0 }}<br><span style="font-size: 7pt; color: #666;">({{ $total > 0 ? round(($responses['CS'] ?? 0) / $total * 100, 1) : 0 }}%)</span></td>
                    <td>{{ $responses['AV'] ?? 0 }}<br><span style="font-size: 7pt; color: #666;">({{ $total > 0 ? round(($responses['AV'] ?? 0) / $total * 100, 1) : 0 }}%)</span></td>
                    <td>{{ $responses['CN'] ?? 0 }}<br><span style="font-size: 7pt; color: #666;">({{ $total > 0 ? round(($responses['CN'] ?? 0) / $total * 100, 1) : 0 }}%)</span></td>
                    <td>{{ $responses['NU'] ?? 0 }}<br><span style="font-size: 7pt; color: #666;">({{ $total > 0 ? round(($responses['NU'] ?? 0) / $total * 100, 1) : 0 }}%)</span></td>
                </tr>
                @if($loop->iteration % 20 == 0 && !$loop->last)
                </tbody>
        </table>
        <div class="page-break"></div>
        <table>
            <thead>
                <tr>
                    <th style="width: 5%;">No.</th>
                    <th style="width: 45%;">Pregunta</th>
                    <th style="width: 10%;">SI</th>
                    <th style="width: 10%;">CS</th>
                    <th style="width: 10%;">AV</th>
                    <th style="width: 10%;">CN</th>
                    <th style="width: 10%;">NU</th>
                </tr>
            </thead>
            <tbody>
                @endif
                @endforeach
            </tbody>
        </table>
    @else
    <p class="section-description" style="color: #666; font-style: italic;">
        No hay datos de frecuencias de respuesta disponibles para mostrar.
    </p>
    @endif

    <div style="margin-top: 15px; background: #eff6ff; padding: 12px; border-left: 4px solid #2563eb; border-radius: 5px;">
        <p class="section-description" style="margin: 0;">
            <strong>Interpretación de las opciones de respuesta:</strong><br>
            • <strong>Siempre (SI)</strong>: El factor de riesgo está presente todo el tiempo<br>
            • <strong>Casi siempre (CS)</strong>: El factor de riesgo está presente la mayor parte del tiempo<br>
            • <strong>Algunas veces (AV)</strong>: El factor de riesgo se presenta ocasionalmente<br>
            • <strong>Casi nunca (CN)</strong>: El factor de riesgo raramente se presenta<br>
            • <strong>Nunca (NU)</strong>: El factor de riesgo no se presenta
        </p>
    </div>

    <p class="section-description" style="margin-top: 15px;">
        <strong>Nota metodológica:</strong> Las respuestas "Siempre" y "Casi siempre" indican mayor presencia del 
        factor de riesgo evaluado y por lo tanto representan las áreas de mayor atención. Esta información permite 
        identificar qué aspectos específicos requieren intervención prioritaria en el programa de prevención de 
        riesgos psicosociales.
    </p>
</div>
