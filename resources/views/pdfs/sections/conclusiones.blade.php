@php
    // Calculate final risk distribution percentages
    $totalParticipants = $diagnosticData['total_participants'] ?? 0;
    $finalRiskData = $diagnosticData['final_risk'] ?? [];
    
    $riskPercentages = [];
    foreach (['Muy Alto', 'Alto', 'Medio', 'Bajo', 'Nulo'] as $level) {
        $count = $finalRiskData[$level] ?? 0;
        $percentage = $totalParticipants > 0 ? ($count / $totalParticipants) * 100 : 0;
        $riskPercentages[$level] = [
            'count' => $count,
            'percentage' => number_format($percentage, 2),
        ];
    }
    
    // Calculate category risk distribution for Medio, Alto, Muy Alto
    $categoryRiskData = [];
    foreach ($diagnosticData['categories'] ?? [] as $categoryName => $riskLevels) {
        $medioAltoCount = ($riskLevels['Medio'] ?? 0) + ($riskLevels['Alto'] ?? 0) + ($riskLevels['Muy Alto'] ?? 0);
        $percentage = $totalParticipants > 0 ? ($medioAltoCount / $totalParticipants) * 100 : 0;
        $categoryRiskData[$categoryName] = [
            'count' => $medioAltoCount,
            'percentage' => number_format($percentage, 2),
        ];
    }
    
    // Calculate domain risk distribution
    $domainRiskData = [];
    foreach ($diagnosticData['domains'] ?? [] as $item) {
        $domainName = $item['dominio'] ?? '';
        $riskLevel = $item['nivel_riesgo'] ?? '';
        $count = $item['conteo'] ?? 0;
        
        if (!isset($domainRiskData[$domainName])) {
            $domainRiskData[$domainName] = ['Medio' => 0, 'Alto' => 0, 'Muy Alto' => 0];
        }
        
        if (in_array($riskLevel, ['Medio', 'Alto', 'Muy Alto'])) {
            $domainRiskData[$domainName][$riskLevel] += $count;
        }
    }
    
    $domainRiskFormatted = [];
    foreach ($domainRiskData as $domainName => $levels) {
        $total = array_sum($levels);
        $percentage = $totalParticipants > 0 ? ($total / $totalParticipants) * 100 : 0;
        $domainRiskFormatted[$domainName] = [
            'count' => $total,
            'percentage' => number_format($percentage, 2),
        ];
    }
    
    // Calculate traumatic events
    $traumaticCount = $traumaticEventsData['total_affected'] ?? 0;
    $traumaticGender = $traumaticEventsData['by_gender'] ?? [];
@endphp

<div class="section">
    <h2 class="section-title">VI.- CONCLUSIONES</h2>
    
    <div class="section-content">
        @if($traumaticCount > 0)
        <p style="margin-bottom: 12px;">
            De acuerdo a los cuestionarios aplicados a {{ $totalParticipants }} trabajadores, {{ $traumaticCount }} presenciaron y/o han sufrido un Acontecimiento Traumático Severo, ({{ $traumaticGender['Hombres'] ?? 0 }} hombres y {{ $traumaticGender['Mujeres'] ?? 0 }} mujeres), por lo cual, se requiere la aplicación de las 3 secciones restantes del cuestionario de Guía de Referencia I, las medidas de acción son de tercer nivel, las cuales consisten en realizar una valoración médica y documentarla en un expediente clínico. Dicha valoración debe ser realizada por un especialista, ya sea, psicólogo clínico-industrial, terapeuta, psiquiatra o médico.
        </p>
        @endif

        <p style="margin-bottom: 12px;">
            Al evaluar los resultados de cada cuestionario, y con base en la Calificación Final Total, se obtuvo la siguiente distribución de niveles de riesgo:
        </p>

        <ul style="list-style-type: none; padding-left: 20px; margin-bottom: 15px;">
            <li style="margin-bottom: 5px;">- Nivel de riesgo Muy Alto: {{ $riskPercentages['Muy Alto']['percentage'] }}% - {{ $riskPercentages['Muy Alto']['count'] }} Trabajadores</li>
            <li style="margin-bottom: 5px;">- Nivel de riesgo Alto: {{ $riskPercentages['Alto']['percentage'] }}% - {{ $riskPercentages['Alto']['count'] }} Trabajadores</li>
            <li style="margin-bottom: 5px;">- Nivel de riesgo Medio: {{ $riskPercentages['Medio']['percentage'] }}% - {{ $riskPercentages['Medio']['count'] }} Trabajadores</li>
            <li style="margin-bottom: 5px;">- Nivel de riesgo Bajo: {{ $riskPercentages['Bajo']['percentage'] }}% - {{ $riskPercentages['Bajo']['count'] }} Trabajadores</li>
            <li style="margin-bottom: 5px;">- Nivel de riesgo Nulo: {{ $riskPercentages['Nulo']['percentage'] }}% - {{ $riskPercentages['Nulo']['count'] }} Trabajadores</li>
        </ul>

        <p style="margin-bottom: 12px;">
            A partir de la Calificación Final, se identificaron los niveles de riesgo Nulo, Bajo, Medio, Alto y Muy Alto, para cada Categoría, Dominio y Dimensión. Mediante análisis de los resultados obtenidos, se deberán adoptar acciones necesarias para el control de los factores de riesgo psicosocial, a través de un Programa de intervención, para los niveles Medio, Alto y Muy Alto, en las Categorías y Dominios y para los niveles Alto y Muy Alto en las Dimensiones.
        </p>

        <p style="margin-bottom: 10px; font-weight: bold;">
            Mediante cuantificación de los niveles de riesgo, se obtuvieron los siguientes resultados:
        </p>

        <h3 style="font-size: 10.5pt; color: #1e40af; margin: 15px 0 8px 0; font-weight: bold;">
            Calificación de la Categoría, niveles de riesgo Medio, Alto y Muy Alto:
        </h3>

        <ul style="list-style-type: none; padding-left: 20px; margin-bottom: 15px;">
            @foreach($categoryRiskData as $categoryName => $data)
            <li style="margin-bottom: 5px;">- {{ $categoryName }}: {{ $data['percentage'] }}% - {{ $data['count'] }} Trabajadores</li>
            @endforeach
        </ul>

        <h3 style="font-size: 10.5pt; color: #1e40af; margin: 15px 0 8px 0; font-weight: bold;">
            Calificación del Dominio, niveles de riesgo Medio, Alto y Muy Alto:
        </h3>

        <ul style="list-style-type: none; padding-left: 20px; margin-bottom: 15px;">
            @foreach($domainRiskFormatted as $domainName => $data)
            <li style="margin-bottom: 5px;">- {{ $domainName }}: {{ $data['percentage'] }}% - {{ $data['count'] }} Trabajadores</li>
            @endforeach
        </ul>

        <h3 style="font-size: 10.5pt; color: #1e40af; margin: 15px 0 8px 0; font-weight: bold;">
            Calificación de la Dimensión, niveles de riesgo Alto y Muy Alto:
        </h3>

        <p style="margin-bottom: 12px;">
            El análisis hasta dimensiones se concluye al implementar un plan de acción de segundo nivel de control, involucrando a los grupos de trabajo de la organización. Se recomienda revisar el análisis detallado por dimensión en las secciones anteriores del informe para identificar las áreas prioritarias de intervención.
        </p>

        <p style="margin-bottom: 12px; font-style: italic; color: #666;">
            Nota: El análisis detallado por dimensión se encuentra disponible en la sección V.5 del presente informe, donde se pueden identificar las dimensiones específicas que requieren atención prioritaria basándose en los niveles Alto y Muy Alto de riesgo psicosocial.
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

    .section-content ul {
        margin-bottom: 10px;
    }

    .section-content li {
        text-align: justify;
    }
</style>
