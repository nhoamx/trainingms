@extends('omr.layout')

@section('title', 'Guía de Referencia I')

@section('guide-title', 'GUÍA DE REFERENCIA I - CUESTIONARIO PARA IDENTIFICAR LOS FACTORES DE RIESGO PSICOSOCIAL EN LOS CENTROS DE TRABAJO')

@section('content')
<div class="instructions">
    <h3 style="font-weight: bold; margin-bottom: 3mm;">INSTRUCCIONES:</h3>
    <p>• Las siguientes preguntas están relacionadas con las situaciones que ha experimentado durante o con motivo del trabajo en las últimas 4 semanas.</p>
    <p>• Para responder las preguntas marque completamente con tinta azul o negra el círculo de la opción que mejor describa su situación:</p>
    <p style="margin-left: 5mm;"><strong>SÍ</strong> = Si experimentó la situación que se pregunta</p>
    <p style="margin-left: 5mm;"><strong>NO</strong> = Si NO experimentó la situación que se pregunta</p>
    <p>• Es importante que conteste todas las preguntas.</p>
</div>

<div class="section-title">PREGUNTAS ({{ $totalQuestions }} total)</div>

@foreach($questions as $index => $question)
    <div class="question-row">
        <div class="question-number">{{ $index + 1 }}.</div>
        <div style="flex: 1; margin-right: 10mm; font-size: 10px; line-height: 1.2;">
            {{ $question }}
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

<div style="margin-top: 10mm; padding: 3mm; border: 1px solid black; background: #f0f0f0;">
    <h4 style="font-weight: bold; margin-bottom: 2mm; text-align: center;">INSTRUCCIONES ADICIONALES</h4>
    <p style="font-size: 9px;">
        Este cuestionario debe ser respondido únicamente por las personas que experimentaron acontecimientos traumáticos severos 
        durante o con motivo del trabajo según se identificó en el cuestionario de factores de riesgo psicosocial.
    </p>
    <p style="font-size: 9px; margin-top: 2mm;">
        Las respuestas que proporcione son confidenciales. Los resultados del cuestionario son para uso exclusivo 
        del patrón y serán utilizados para mejorar las condiciones de trabajo.
    </p>
</div>

<!-- Área para observaciones -->
<div style="margin-top: 8mm; border: 1px solid black; padding: 3mm; min-height: 20mm;">
    <h4 style="font-weight: bold; margin-bottom: 3mm;">OBSERVACIONES ADICIONALES:</h4>
    <div style="border-bottom: 1px solid #ccc; margin-bottom: 2mm; height: 3mm;"></div>
    <div style="border-bottom: 1px solid #ccc; margin-bottom: 2mm; height: 3mm;"></div>
    <div style="border-bottom: 1px solid #ccc; margin-bottom: 2mm; height: 3mm;"></div>
    <div style="border-bottom: 1px solid #ccc; margin-bottom: 2mm; height: 3mm;"></div>
</div>

<!-- Área de calificación -->
<div style="position: absolute; bottom: 20mm; right: 10mm; border: 2px solid black; padding: 3mm; background: #f8f8f8;">
    <h4 style="font-weight: bold; margin-bottom: 2mm; text-align: center; font-size: 10px;">PARA USO OFICIAL</h4>
    <div style="display: flex; gap: 3mm;">
        <div style="text-align: center;">
            <span style="font-size: 8px;">PUNTUACIÓN TOTAL:</span>
            <div style="width: 15mm; height: 6mm; border: 1px solid black; margin-top: 1mm;"></div>
        </div>
        <div style="text-align: center;">
            <span style="font-size: 8px;">NIVEL DE RIESGO:</span>
            <div style="width: 15mm; height: 6mm; border: 1px solid black; margin-top: 1mm;"></div>
        </div>
    </div>
</div>
@endsection