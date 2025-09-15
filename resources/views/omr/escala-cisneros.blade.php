@extends('omr.layout')

@section('title', 'Escala Cisneros')

@section('guide-title', 'ESCALA CISNEROS - CUESTIONARIO DE VIOLENCIA PSICOLÓGICA EN EL TRABAJO')

@section('content')
<div class="instructions">
    <h3 style="font-weight: bold; margin-bottom: 3mm;">INSTRUCCIONES:</h3>
    <p>• Las siguientes preguntas están relacionadas con situaciones de violencia psicológica en el trabajo.</p>
    <p>• Para cada pregunta, debe responder dos aspectos:</p>
    <div style="margin: 2mm 0 2mm 5mm; font-size: 10px;">
        <p><strong>1. Tipo de persona involucrada:</strong></p>
        <p style="margin-left: 5mm;"><strong>A</strong> = Jefas/jefes o personas supervisoras</p>
        <p style="margin-left: 5mm;"><strong>B</strong> = Personas compañeras de trabajo</p>
        <p style="margin-left: 5mm;"><strong>C</strong> = Personas subordinadas</p>
    </div>
    <div style="margin: 2mm 0 2mm 5mm; font-size: 10px;">
        <p><strong>2. Frecuencia de la situación:</strong></p>
        <p style="margin-left: 5mm;"><strong>0</strong> = Nunca</p>
        <p style="margin-left: 5mm;"><strong>1</strong> = Pocas veces al año o menos</p>
        <p style="margin-left: 5mm;"><strong>2</strong> = Una vez al mes o menos</p>
        <p style="margin-left: 5mm;"><strong>3</strong> = Algunas veces al mes</p>
        <p style="margin-left: 5mm;"><strong>4</strong> = Una vez a la semana</p>
        <p style="margin-left: 5mm;"><strong>5</strong> = Varias veces a la semana</p>
        <p style="margin-left: 5mm;"><strong>6</strong> = Todos los días</p>
    </div>
    <p>• Responda considerando su experiencia en los últimos 6 meses.</p>
    <p>• Es importante que conteste todas las preguntas.</p>
</div>

<div class="section-title">PREGUNTAS ({{ $totalQuestions }} total)</div>

@foreach($questions as $index => $question)
    <div style="margin-bottom: 6mm; border-bottom: 1px solid #ccc; padding-bottom: 3mm; page-break-inside: avoid;">
        <div style="font-weight: bold; margin-bottom: 2mm; font-size: 11px;">
            {{ $index + 1 }}. {{ $question }}
        </div>
        
        <!-- Tipo de persona -->
        <div style="margin-bottom: 2mm;">
            <span style="font-weight: bold; font-size: 10px; margin-right: 5mm;">Tipo de persona:</span>
            <div style="display: inline-flex; gap: 3mm;">
                <div class="option-group">
                    <span class="option-label">A</span>
                    <div class="bubble"></div>
                </div>
                <div class="option-group">
                    <span class="option-label">B</span>
                    <div class="bubble"></div>
                </div>
                <div class="option-group">
                    <span class="option-label">C</span>
                    <div class="bubble"></div>
                </div>
            </div>
        </div>
        
        <!-- Frecuencia -->
        <div>
            <span style="font-weight: bold; font-size: 10px; margin-right: 5mm;">Frecuencia:</span>
            <div style="display: inline-flex; gap: 3mm;">
                <div class="option-group">
                    <span class="option-label">0</span>
                    <div class="bubble"></div>
                </div>
                <div class="option-group">
                    <span class="option-label">1</span>
                    <div class="bubble"></div>
                </div>
                <div class="option-group">
                    <span class="option-label">2</span>
                    <div class="bubble"></div>
                </div>
                <div class="option-group">
                    <span class="option-label">3</span>
                    <div class="bubble"></div>
                </div>
                <div class="option-group">
                    <span class="option-label">4</span>
                    <div class="bubble"></div>
                </div>
                <div class="option-group">
                    <span class="option-label">5</span>
                    <div class="bubble"></div>
                </div>
                <div class="option-group">
                    <span class="option-label">6</span>
                    <div class="bubble"></div>
                </div>
            </div>
        </div>
    </div>
@endforeach

<!-- Información adicional sobre violencia -->
<div style="margin-top: 10mm; padding: 3mm; border: 1px solid black; background: #f0f0f0;">
    <h4 style="font-weight: bold; margin-bottom: 2mm; text-align: center;">INFORMACIÓN ADICIONAL SOBRE VIOLENCIA LABORAL</h4>
    <p style="font-size: 9px;">
        La violencia laboral incluye aquellas acciones de hostigamiento, acoso o malos tratos en contra del trabajador, 
        que puedan dañar su integridad o dignidad.
    </p>
    <p style="font-size: 9px; margin-top: 2mm;">
        Si experimentó alguna situación de violencia adicional no contemplada en este cuestionario, descríbala brevemente:
    </p>
    <div style="border: 1px solid black; margin-top: 2mm; min-height: 10mm; background: white;">
        <!-- Espacio para escribir -->
    </div>
</div>

<!-- Acontecimientos traumáticos relacionados -->
<div style="page-break-before: always;"></div>
<div class="section-title">ACONTECIMIENTOS TRAUMÁTICOS SEVEROS RELACIONADOS CON VIOLENCIA</div>

<div class="instructions">
    <h4 style="font-weight: bold; margin-bottom: 2mm;">¿Ha presenciado o sufrido alguna vez, durante o con motivo del trabajo un acontecimiento como los siguientes?</h4>
    <p>Marque <strong>SÍ</strong> o <strong>NO</strong> para cada situación:</p>
</div>

@php
$traumaticEvents = [
    'Accidente que tenga como consecuencia la muerte, la pérdida de un miembro o una lesión grave',
    'Asaltos',
    'Actos violentos que derivaron en lesiones graves',
    'Secuestro',
    'Amenazas',
    'Cualquier otro que ponga en riesgo su vida o salud, y/o la de otras personas'
];
@endphp

@foreach($traumaticEvents as $index => $event)
    <div class="question-row">
        <div class="question-number">{{ $index + 1 }}.</div>
        <div style="flex: 1; margin-right: 10mm; font-size: 10px; line-height: 1.2;">
            {{ $event }}
        </div>
        <div class="answer-options">
            <div class="option-group">
                <span class="option-label">SÍ</span>
                <div class="bubble"></div>
            </div>
            <div class="option-group">
                <span class="option-label">NO</span>
                <div class="bubble"></div>
            </div>
        </div>
    </div>
@endforeach

<!-- Escala de valores para interpretación -->
<div style="position: absolute; bottom: 35mm; left: 10mm; right: 10mm; border: 2px solid black; padding: 3mm; background: #f8f8f8;">
    <h4 style="font-weight: bold; margin-bottom: 2mm; text-align: center; font-size: 11px;">ESCALA DE INTERPRETACIÓN</h4>
    <div style="font-size: 9px; text-align: center;">
        <p><strong>Puntuación baja (0-30):</strong> Ausencia de violencia psicológica</p>
        <p><strong>Puntuación media (31-60):</strong> Violencia psicológica baja</p>
        <p><strong>Puntuación alta (61-90):</strong> Violencia psicológica media</p>
        <p><strong>Puntuación muy alta (91+):</strong> Violencia psicológica alta</p>
    </div>
</div>

<!-- Área de calificación -->
<div style="position: absolute; bottom: 20mm; right: 10mm; border: 2px solid black; padding: 3mm; background: #f8f8f8;">
    <h4 style="font-weight: bold; margin-bottom: 2mm; text-align: center; font-size: 10px;">PARA USO OFICIAL</h4>
    <div style="display: flex; gap: 3mm;">
        <div style="text-align: center;">
            <span style="font-size: 8px;">PUNTUACIÓN:</span>
            <div style="width: 15mm; height: 6mm; border: 1px solid black; margin-top: 1mm;"></div>
        </div>
        <div style="text-align: center;">
            <span style="font-size: 8px;">NIVEL:</span>
            <div style="width: 15mm; height: 6mm; border: 1px solid black; margin-top: 1mm;"></div>
        </div>
    </div>
</div>
@endsection