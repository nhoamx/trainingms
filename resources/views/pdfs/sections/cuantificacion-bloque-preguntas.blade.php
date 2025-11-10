{{-- 5.5.6 CUANTIFICACIÓN POR BLOQUE DE PREGUNTAS --}}
@php
    $blocks = $diagnosticData['blocks'] ?? [];
    $generalScore = $diagnosticData['general_score'] ?? [];
    $formatPercent = function($obtained, $max) { return $max > 0 ? round(($obtained / $max) * 100, 1) : 0; };
@endphp

<div class="section">
    <h3 class="section-title">5.5.6 CUANTIFICACIÓN POR BLOQUE DE PREGUNTAS</h3>

    {{-- Explicación general --}}
    <div class="section-description" style="text-align:justify; margin-bottom: 15px;">
        <p style="margin-bottom:8px;">
            La puntuación de cada uno de los Cuestionarios se calcula de la manera siguiente:<br>
            <strong>Puntuación obtenida</strong> = sumatoria de los puntos de cada respuesta (por cuestionario).
        </p>
        <p style="margin-bottom:8px;">
            La ponderación (puntos) de cada respuesta, se extrae de la <strong>Tabla 5</strong> de la Guía de Referencia III (NOM-035-STPS-2018). 
            La puntuación máxima de cada respuesta equivale a <strong>4</strong> y la mínima a <strong>0</strong>.
        </p>
        <p style="margin-bottom:8px;">
            La puntuación máxima por cuestionario, se obtiene de la manera siguiente:<br>
            <strong>A)</strong> 64 preguntas deben ser contestadas por todos los participantes (<em>64  4 = 256 puntos</em>).<br>
            <strong>B)</strong> 4 preguntas están relacionadas con la atención a clientes y usuarios (<em>4  4 = 16 puntos</em>).<br>
            <strong>C)</strong> 4 preguntas están relacionadas con las actitudes de las personas que supervisa (<em>4  4 = 16 puntos</em>).
        </p>
        <p>
            <strong>Puntuación obtenida general</strong> = sumatoria de los puntos de todos los cuestionarios de la muestra.<br>
            <strong>Puntuación máxima general</strong> = (A)  participantes + (B)  personal que atiende usuarios + (C)  número de jefes.
        </p>
    </div>

    {{-- Puntuación General --}}
    @if(isset($generalScore['general']))
    <div style="margin: 20px 0;">
        <h4 style="font-size:11pt;color:#1e40af;margin:12px 0 10px;font-weight:bold;text-align:center;">Puntuación General</h4>
        <div style="display:flex; justify-content:center;">
            <div class="general-pie-container" style="width:350px;height:350px;">
                <canvas id="generalScoreChart" style="width:100%;height:100%;"></canvas>
                <div style="text-align:center; margin-top:10px; font-size:9pt;">
                    <strong>Población: {{ $generalScore['general']['participants'] }}</strong>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- A, B, C Components --}}
    @if(!empty($generalScore))
    {{-- A Component (full width) --}}
    @if(isset($generalScore['a']) && $generalScore['a']['max'] > 0)
        @php 
            $data = $generalScore['a']; 
            $remaining = max(0, $data['max'] - $data['obtained']);
        @endphp
        <div style="margin: 20px 0;">
            <div class="abc-item-full">
                <h5>A) {{ $data['name'] }}</h5>
                <div style="display:flex; gap:20px;">
                    <div class="abc-chart-wrapper-full"><canvas id="componentChart_a"></canvas></div>
                    <table class="abc-table-full">
                        <tbody>
                            <tr><td class="label">Puntuación máxima:</td><td>{{ $data['max'] }}</td></tr>
                            <tr><td class="label">Puntuación obtenida:</td><td><strong>{{ $data['obtained'] }}</strong></td></tr>
                            <tr><td class="label">% Obtenido:</td><td>{{ $formatPercent($data['obtained'], $data['max']) }}%</td></tr>
                            <tr><td class="label">% No obtenido:</td><td>{{ $formatPercent($remaining, $data['max']) }}%</td></tr>
                            <tr><td class="label">Población:</td><td>{{ $data['participants'] }}</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif
    
    {{-- B and C Components (2 columns) --}}
    <div class="abc-grid-two" style="margin: 20px 0;">
        @foreach(['b', 'c'] as $key)
            @if(isset($generalScore[$key]) && $generalScore[$key]['max'] > 0)
                @php 
                    $data = $generalScore[$key]; 
                    $remaining = max(0, $data['max'] - $data['obtained']);
                    $label = strtoupper($key);
                @endphp
                <div class="abc-item">
                    <h5>{{ $label }}) {{ $data['name'] }}</h5>
                    <div class="abc-chart-wrapper"><canvas id="componentChart_{{ $key }}"></canvas></div>
                    <table class="abc-table">
                        <tbody>
                            <tr><td class="label">Puntuación máxima:</td><td>{{ $data['max'] }}</td></tr>
                            <tr><td class="label">Puntuación obtenida:</td><td><strong>{{ $data['obtained'] }}</strong></td></tr>
                            <tr><td class="label">% Obtenido:</td><td>{{ $formatPercent($data['obtained'], $data['max']) }}%</td></tr>
                            <tr><td class="label">% No obtenido:</td><td>{{ $formatPercent($remaining, $data['max']) }}%</td></tr>
                            <tr><td class="label">Población:</td><td>{{ $data['participants'] }}</td></tr>
                        </tbody>
                    </table>
                </div>
            @endif
        @endforeach
    </div>
    @endif

    {{-- Segunda Parte: Bloques 01 al 04 --}}
    <h4 style="font-size:10pt;color:#1e40af;margin:24px 0 10px;font-weight:bold;page-break-before:always;">Segunda Parte</h4>
    <ul style="margin:0 0 12px 20px; font-size:9pt;">
        <li><strong>Bloque 01.</strong> Se debe considerar las condiciones ambientales de su centro de trabajo. Preguntas (01, 02, 03, 04 y 05)</li>
        <li><strong>Bloque 02.</strong> Se debe pensar en la cantidad y ritmo de trabajo que tiene. Preguntas (06, 07 y 08)</li>
        <li><strong>Bloque 03.</strong> Las preguntas están relacionadas con el esfuerzo mental que le exige su trabajo. (09, 10, 11 y 12)</li>
        <li><strong>Bloque 04.</strong> Las preguntas están relacionadas con las actividades que realiza en su trabajo y las responsabilidades que tiene. (13, 14, 15 y 16)</li>
    </ul>
    <div class="blocks-grid-two">
        @foreach(['01','02','03','04'] as $blockNo)
            @if(isset($blocks[$blockNo]))
                @php $data = $blocks[$blockNo]; $remaining = max(0, $data['max'] - $data['obtained']); @endphp
                <div class="block-item-two">
                    <h5>{{ $data['name'] }}</h5>
                    <div class="block-chart-wrapper-two"><canvas id="blockChart_{{ $blockNo }}"></canvas></div>
                    <table class="block-table-two">
                        <tbody>
                            <tr><td class="label">Puntuación máxima:</td><td>{{ $data['max'] }}</td></tr>
                            <tr><td class="label">Puntuación obtenida:</td><td><strong>{{ $data['obtained'] }}</strong></td></tr>
                            <tr><td class="label">% Obtenido:</td><td>{{ $formatPercent($data['obtained'], $data['max']) }}%</td></tr>
                            <tr><td class="label">% No obtenido:</td><td>{{ $formatPercent($remaining, $data['max']) }}%</td></tr>
                        </tbody>
                    </table>
                    <p class="block-population">Población: {{ $diagnosticData['total_participants'] ?? 0 }}</p>
                </div>
            @endif
        @endforeach
    </div>

    {{-- Tercera Parte: Bloques 05 al 08 --}}
    <h4 style="font-size:10pt;color:#1e40af;margin:24px 0 10px;font-weight:bold;">Tercera Parte</h4>
    <ul style="margin:0 0 12px 20px; font-size:9pt;">
        <li><strong>Bloque 05.</strong> Las preguntas están relacionadas con su jornada de trabajo. (17, 18, 19, 20, 21 y 22)</li>
        <li><strong>Bloque 06.</strong> Las preguntas están relacionadas con las decisiones que puede tomar en su trabajo. (23, 24, 25, 26, 27 y 28)</li>
        <li><strong>Bloque 07.</strong> Las preguntas están relacionadas con cualquier tipo de cambio que ocurra en su trabajo (considerando los últimos cambios realizados). (29 y 30)</li>
        <li><strong>Bloque 08.</strong> Las preguntas están relacionadas con la capacitación e información que se le proporciona sobre su trabajo. (31, 32, 33, 34, 35 y 36)</li>
    </ul>
    <div class="blocks-grid-two">
        @foreach(['05','06','07','08'] as $blockNo)
            @if(isset($blocks[$blockNo]))
                @php $data = $blocks[$blockNo]; $remaining = max(0, $data['max'] - $data['obtained']); @endphp
                <div class="block-item-two">
                    <h5>{{ $data['name'] }}</h5>
                    <div class="block-chart-wrapper-two"><canvas id="blockChart_{{ $blockNo }}"></canvas></div>
                    <table class="block-table-two">
                        <tbody>
                            <tr><td class="label">Puntuación máxima:</td><td>{{ $data['max'] }}</td></tr>
                            <tr><td class="label">Puntuación obtenida:</td><td><strong>{{ $data['obtained'] }}</strong></td></tr>
                            <tr><td class="label">% Obtenido:</td><td>{{ $formatPercent($data['obtained'], $data['max']) }}%</td></tr>
                            <tr><td class="label">% No obtenido:</td><td>{{ $formatPercent($remaining, $data['max']) }}%</td></tr>
                        </tbody>
                    </table>
                    <p class="block-population">Población: {{ $diagnosticData['total_participants'] ?? 0 }}</p>
                </div>
            @endif
        @endforeach
    </div>

    {{-- Cuarta Parte: Bloques 09 al 12 --}}
    <h4 style="font-size:10pt;color:#1e40af;margin:24px 0 10px;font-weight:bold;">Cuarta Parte</h4>
    <ul style="margin:0 0 12px 20px; font-size:9pt;">
        <li><strong>Bloque 09.</strong> Las preguntas están relacionadas con el o los jefes con quien tiene contacto. (37, 38, 39, 40 y 41)</li>
        <li><strong>Bloque 10.</strong> Las preguntas se refieren a las relaciones con sus compañeros. (42, 43, 44, 45 y 46)</li>
        <li><strong>Bloque 11.</strong> Las preguntas están relacionadas con la información que recibe sobre su rendimiento en el trabajo, el reconocimiento, el sentido de pertenencia y la estabilidad que le ofrece su trabajo. (47, 48, 49, 50, 51, 52, 53, 54, 55 y 56)</li>
        <li><strong>Bloque 12.</strong> Las preguntas están relacionadas con actos de violencia laboral (malos tratos, acoso, hostigamiento, acoso psicológico). (57, 58, 59, 60, 61, 62, 63 y 64)</li>
    </ul>
    <div class="blocks-grid-two">
        @foreach(['09','10','11','12'] as $blockNo)
            @if(isset($blocks[$blockNo]))
                @php $data = $blocks[$blockNo]; $remaining = max(0, $data['max'] - $data['obtained']); @endphp
                <div class="block-item-two">
                    <h5>{{ $data['name'] }}</h5>
                    <div class="block-chart-wrapper-two"><canvas id="blockChart_{{ $blockNo }}"></canvas></div>
                    <table class="block-table-two">
                        <tbody>
                            <tr><td class="label">Puntuación máxima:</td><td>{{ $data['max'] }}</td></tr>
                            <tr><td class="label">Puntuación obtenida:</td><td><strong>{{ $data['obtained'] }}</strong></td></tr>
                            <tr><td class="label">% Obtenido:</td><td>{{ $formatPercent($data['obtained'], $data['max']) }}%</td></tr>
                            <tr><td class="label">% No obtenido:</td><td>{{ $formatPercent($remaining, $data['max']) }}%</td></tr>
                        </tbody>
                    </table>
                    <p class="block-population">Población: {{ $diagnosticData['total_participants'] ?? 0 }}</p>
                </div>
            @endif
        @endforeach
    </div>
</div>

<style>
    .general-pie-container { width: 350px; height: 350px; margin: 0 auto; }
    
    /* A component (full width) */
    .abc-item-full { background: #fff; border: 1px solid #e5e7eb; border-radius: 4px; padding: 15px; }
    .abc-item-full h5 { margin: 0 0 12px; font-size: 10pt; text-align: center; color: #1e40af; font-weight: bold; }
    .abc-chart-wrapper-full { width: 300px; height: 300px; flex-shrink: 0; }
    .abc-table-full { width: 100%; font-size: 9pt; border-collapse: collapse; }
    .abc-table-full td { padding: 5px 8px; border-top: 1px solid #e5e7eb; }
    .abc-table-full td.label { text-align: left; color: #374151; }
    .abc-table-full td:last-child { text-align: right; font-weight: 500; }
    
    /* B and C components (2 columns) */
    .abc-grid-two { display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px; }
    .abc-item { background: #fff; border: 1px solid #e5e7eb; border-radius: 4px; padding: 12px; }
    .abc-item h5 { margin: 0 0 8px; font-size: 9pt; text-align: center; color: #1e40af; font-weight: bold; }
    .abc-chart-wrapper { width: 100%; height: 250px; margin-bottom: 8px; }
    .abc-table { width: 100%; font-size: 8pt; border-collapse: collapse; }
    .abc-table td { padding: 3px 5px; border-top: 1px solid #e5e7eb; }
    .abc-table td.label { text-align: left; color: #374151; }
    .abc-table td:last-child { text-align: right; font-weight: 500; }
    
    .blocks-grid-two { display: grid; grid-template-columns: repeat(2, 1fr); gap: 18px; margin-top: 12px; }
    .block-item-two { background: #fff; border: 1px solid #e5e7eb; border-radius: 4px; padding: 12px; page-break-inside: avoid; }
    .block-item-two h5 { margin: 0 0 8px; font-size: 9pt; text-align: center; color: #1e40af; font-weight: bold; }
    .block-chart-wrapper-two { width: 100%; height: 200px; margin-bottom: 8px; }
    .block-table-two { width: 100%; font-size: 8pt; border-collapse: collapse; }
    .block-table-two td { padding: 3px 5px; border-top: 1px solid #e5e7eb; }
    .block-table-two td.label { text-align: left; color: #374151; }
    .block-table-two td:last-child { text-align: right; font-weight: 500; }
    .block-population { font-size: 8pt; text-align: right; margin: 5px 0 0; color: #374151; }
</style>
