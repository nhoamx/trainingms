@extends('omr.layout')

@section('title', 'Guía de Referencia III')

@section('guide-title', 'GUÍA DE REFERENCIA III - CUESTIONARIO PARA IDENTIFICAR LOS FACTORES DE RIESGO PSICOSOCIAL')

@section('content')
<div class="instructions">
    <h3 style="font-weight: bold; margin-bottom: 3mm;">INSTRUCCIONES:</h3>
    <p>• Las siguientes preguntas están relacionadas con las condiciones de su lugar, ambiente, contenido, organización y carga de trabajo.</p>
    <p>• Para responder marque completamente con tinta azul o negra el círculo de la opción que mejor describa su centro de trabajo:</p>
    <div style="margin: 2mm 0; font-size: 10px;">
        <p><strong>A) Siempre</strong> - Si la situación que se pregunta se presenta en su trabajo de manera permanente.</p>
        <p><strong>B) Casi siempre</strong> - Si la situación se presenta frecuentemente.</p>
        <p><strong>C) Algunas veces</strong> - Si la situación se presenta ocasionalmente.</p>
        <p><strong>D) Casi nunca</strong> - Si la situación se presenta pocas veces en su trabajo.</p>
        <p><strong>E) Nunca</strong> - Si la situación nunca se presenta en su trabajo.</p>
    </div>
    <p>• Es importante que conteste todas las preguntas.</p>
</div>

<div class="section-title">PREGUNTAS GENERALES ({{ $totalGeneralQuestions }} preguntas)</div>

<div class="questions-grid">
@foreach($generalQuestions as $number => $question)
    <div class="question-row" style="margin-bottom: 3mm; page-break-inside: avoid;">
        <div class="question-number">{{ sprintf('%02d', $number) }}.</div>
        <div class="answer-options">
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
            <div class="option-group">
                <span class="option-label">D</span>
                <div class="bubble"></div>
            </div>
            <div class="option-group">
                <span class="option-label">E</span>
                <div class="bubble"></div>
            </div>
        </div>
    </div>
@endforeach
</div>

<!-- Preguntas condicionales -->
@if(count($conditionalSections) > 0)
<div style="page-break-before: always;"></div>
<div class="section-title">PREGUNTAS CONDICIONALES</div>

<div class="instructions">
    <p><strong>Las siguientes preguntas solo deben responderse si se cumplen las condiciones específicas.</strong></p>
</div>

@foreach($conditionalSections as $sectionKey => $section)
    <div style="margin-bottom: 8mm; border: 1px solid black; padding: 3mm;">
        <h4 style="font-weight: bold; margin-bottom: 3mm; background: #e0e0e0; padding: 2mm; text-align: center;">
            CONDICIÓN: {{ $section['condition'] }}
        </h4>
        
        <div style="margin-bottom: 3mm; padding: 2mm; background: #f8f8f8; border: 1px solid #ccc;">
            <p style="font-size: 10px;"><strong>Marque SÍ o NO según se cumpla la condición:</strong></p>
            <div style="display: flex; gap: 5mm; margin-top: 2mm;">
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

        <p style="font-size: 9px; margin-bottom: 3mm;"><strong>Si marcó SÍ, conteste las siguientes preguntas:</strong></p>
        
        @foreach($section['questions'] as $number => $question)
            <div class="question-row" style="margin-bottom: 2mm;">
                <div class="question-number">{{ sprintf('%02d', $number) }}.</div>
                <div class="answer-options">
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
                    <div class="option-group">
                        <span class="option-label">D</span>
                        <div class="bubble"></div>
                    </div>
                    <div class="option-group">
                        <span class="option-label">E</span>
                        <div class="bubble"></div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endforeach
@endif

<!-- Acontecimientos traumáticos -->
<div style="page-break-before: always;"></div>
<div class="section-title">ACONTECIMIENTOS TRAUMÁTICOS SEVEROS</div>

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