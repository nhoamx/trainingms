{{-- 5.5.6 CUANTIFICACIÓN POR BLOQUE DE PREGUNTAS --}}
@php
        $blocks = $diagnosticData['blocks'] ?? [];
        $formatPercent = function(int $obtained, int $max) { return $max > 0 ? round(($obtained / $max) * 100, 1) : 0; };
@endphp

<div class="section">
        <h3 class="section-title">5.5.6 CUANTIFICACIÓN POR BLOQUE DE PREGUNTAS</h3>

        {{-- Primera Parte: explicación general (texto provisto) --}}
        <h4 style="font-size:10pt;color:#1e40af;margin:12px 0 6px;font-weight:bold;">Primera Parte: Metodología de Puntuación</h4>
        <div class="section-description" style="text-align:justify;">
                <p>
                        La puntuación de cada uno de los Cuestionarios se calcula de la manera siguiente: <strong>Puntuación obtenida</strong> = sumatoria de los
                        puntos de cada respuesta (por cuestionario). La ponderación (puntos) de cada respuesta, se extrae de la <strong>Tabla 5</strong> de la Guía de
                        Referencia III (NOM-035-STPS-2018). La puntuación máxima de cada respuesta equivale a <strong>4</strong> y la mínima a <strong>0</strong>.
                </p>
                <p>
                        La puntuación máxima por cuestionario, se obtiene de la manera siguiente:<br>
                        A) 64 preguntas deben ser contestadas por todos los participantes (<em>64 × 4 = 256 puntos</em>).<br>
                        B) 4 preguntas están relacionadas con la atención a clientes y usuarios (<em>4 × 4 = 16 puntos</em>).<br>
                        C) 4 preguntas están relacionadas con las actitudes de las personas que supervisa (<em>4 × 4 = 16 puntos</em>).
                </p>
                <p>
                        <strong>Puntuación obtenida general</strong> = sumatoria de los puntos de todos los cuestionarios de la muestra.<br>
                        <strong>Puntuación máxima general</strong> = (A) × participantes + (B) × personal que atiende usuarios + (C) × número de jefes.
                </p>
        </div>

        {{-- Segunda Parte: Bloques 01 al 04 --}}
        <h4 style="font-size:10pt;color:#1e40af;margin:18px 0 8px;font-weight:bold;">Segunda Parte: Bloques 01 al 04</h4>
        <p class="section-description" style="text-align:justify;">Condiciones ambientales, cantidad y ritmo de trabajo, esfuerzo mental y actividades/responsabilidades.</p>
        <div class="blocks-grid">
                @foreach(['01','02','03','04'] as $blockNo)
                        @if(isset($blocks[$blockNo]))
                                @php $data = $blocks[$blockNo]; $remaining = max(0, $data['max'] - $data['obtained']); @endphp
                                <div class="block-item">
                                        <h5>{{ 'Bloque '.$blockNo }}<br><small style="font-weight:normal;">{{ $data['name'] }}</small></h5>
                                        <div class="block-chart-wrapper"><canvas id="blockChart_{{ $blockNo }}"></canvas></div>
                                        <table class="block-table">
                                                <thead><tr><th>Indicador</th><th>Puntos</th><th>%</th></tr></thead>
                                                <tbody>
                                                        <tr><td class="obt">Obtenido</td><td>{{ $data['obtained'] }}</td><td>{{ $formatPercent($data['obtained'],$data['max']) }}%</td></tr>
                                                        <tr><td class="falt">No obtenido</td><td>{{ $remaining }}</td><td>{{ $formatPercent($remaining,$data['max']) }}%</td></tr>
                                                        <tr><td class="tot">Máximo</td><td>{{ $data['max'] }}</td><td>100%</td></tr>
                                                </tbody>
                                        </table>
                                        <p class="block-questions">Preguntas: {{ implode(', ', $data['questions']) }}</p>
                                </div>
                        @endif
                @endforeach
        </div>

        {{-- Tercera Parte: Bloques 05 al 08 --}}
        <h4 style="font-size:10pt;color:#1e40af;margin:24px 0 8px;font-weight:bold;">Tercera Parte: Bloques 05 al 08</h4>
        <p class="section-description" style="text-align:justify;">Jornada de trabajo, decisiones, cambios en el trabajo, capacitación e información.</p>
        <div class="blocks-grid">
                @foreach(['05','06','07','08'] as $blockNo)
                        @if(isset($blocks[$blockNo]))
                                @php $data = $blocks[$blockNo]; $remaining = max(0, $data['max'] - $data['obtained']); @endphp
                                <div class="block-item">
                                        <h5>{{ 'Bloque '.$blockNo }}<br><small style="font-weight:normal;">{{ $data['name'] }}</small></h5>
                                        <div class="block-chart-wrapper"><canvas id="blockChart_{{ $blockNo }}"></canvas></div>
                                        <table class="block-table">
                                                <thead><tr><th>Indicador</th><th>Puntos</th><th>%</th></tr></thead>
                                                <tbody>
                                                        <tr><td class="obt">Obtenido</td><td>{{ $data['obtained'] }}</td><td>{{ $formatPercent($data['obtained'],$data['max']) }}%</td></tr>
                                                        <tr><td class="falt">No obtenido</td><td>{{ $remaining }}</td><td>{{ $formatPercent($remaining,$data['max']) }}%</td></tr>
                                                        <tr><td class="tot">Máximo</td><td>{{ $data['max'] }}</td><td>100%</td></tr>
                                                </tbody>
                                        </table>
                                        <p class="block-questions">Preguntas: {{ implode(', ', $data['questions']) }}</p>
                                </div>
                        @endif
                @endforeach
        </div>

        {{-- Cuarta Parte: Bloques 09 al 12 --}}
        <h4 style="font-size:10pt;color:#1e40af;margin:24px 0 8px;font-weight:bold;">Cuarta Parte: Bloques 09 al 12</h4>
        <p class="section-description" style="text-align:justify;">Relación con jefes y compañeros, información y reconocimiento, pertenencia, estabilidad y violencia laboral.</p>
        <div class="blocks-grid">
                @foreach(['09','10','11','12'] as $blockNo)
                        @if(isset($blocks[$blockNo]))
                                @php $data = $blocks[$blockNo]; $remaining = max(0, $data['max'] - $data['obtained']); @endphp
                                <div class="block-item">
                                        <h5>{{ 'Bloque '.$blockNo }}<br><small style="font-weight:normal;">{{ $data['name'] }}</small></h5>
                                        <div class="block-chart-wrapper"><canvas id="blockChart_{{ $blockNo }}"></canvas></div>
                                        <table class="block-table">
                                                <thead><tr><th>Indicador</th><th>Puntos</th><th>%</th></tr></thead>
                                                <tbody>
                                                        <tr><td class="obt">Obtenido</td><td>{{ $data['obtained'] }}</td><td>{{ $formatPercent($data['obtained'],$data['max']) }}%</td></tr>
                                                        <tr><td class="falt">No obtenido</td><td>{{ $remaining }}</td><td>{{ $formatPercent($remaining,$data['max']) }}%</td></tr>
                                                        <tr><td class="tot">Máximo</td><td>{{ $data['max'] }}</td><td>100%</td></tr>
                                                </tbody>
                                        </table>
                                        <p class="block-questions">Preguntas: {{ implode(', ', $data['questions']) }}</p>
                                </div>
                        @endif
                @endforeach
        </div>

        @if(empty($blocks))
                <p style="color:#666;font-style:italic;margin-top:12px;">No hay datos disponibles para la cuantificación por bloque.</p>
        @endif
</div>

<style>
        .blocks-grid { display:grid; grid-template-columns:repeat(2,1fr); gap:18px; margin-top:12px; }
        .block-item { background:#fff; border:1px solid #e5e7eb; border-radius:4px; padding:10px; page-break-inside:avoid; }
        .block-item h5 { margin:0 0 6px; font-size:9pt; text-align:center; color:#1e40af; font-weight:bold; }
        .block-chart-wrapper { width:100%; height:160px; }
        .block-table { width:100%; margin-top:8px; border-collapse:collapse; font-size:8pt; }
        .block-table th { background:#f3f4f6; font-weight:bold; padding:4px; }
        .block-table td { padding:4px; text-align:center; border-top:1px solid #e5e7eb; }
        .block-table td.obt { color:#065f46; font-weight:bold; }
        .block-table td.falt { color:#047857; }
        .block-table td.tot { color:#374151; }
        .block-questions { font-size:7.5pt; margin-top:6px; color:#374151; }
</style>