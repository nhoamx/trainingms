@extends('omr.layout')

@section('title', 'Escala Cisneros')


@section('content')
<!-- Marcadores de referencia en las esquinas -->
<div class="alignment-marker marker-top-left"></div>
<div class="alignment-marker marker-top-right"></div>
<div class="alignment-marker marker-bottom-left"></div>
<div class="alignment-marker marker-bottom-right"></div>

<style>
    .folio-instructions-row { display: flex; gap: 8mm; margin-bottom: 6mm; align-items: flex-start; }
    .folio-section { border: 1px solid black; padding: 1mm; padding-top: 3mm; padding-right: 2mm; background: #f8f8f8; position: relative; min-width: 80mm; max-width: 100mm; flex: 1; }
    .folio-header { display: flex; gap: 1mm; margin-bottom: 2mm; align-items: center; }
    .folio-digit-column { width: 8mm; text-align: center; font-weight: bold; font-size: 7px; }
    .folio-position-header { flex: 1; text-align: center; font-size: 6px; font-weight: bold; border: 1px solid black; height: 4mm; display: flex; align-items: center; justify-content: center; background: white; }
    .folio-row { display: flex; align-items: center; margin-bottom: 1.2mm; font-size: 6px; min-height: 4mm; }
    .folio-digit-number { font-weight: bold; width: 8mm; text-align: center; flex-shrink: 0; font-size: 7px; }
    .folio-bubbles-row { display: flex; align-items: center; flex: 1; justify-content: space-around; }
    .block-corner-marker { position: absolute; width: 2.5mm; height: 2.5mm; background: black; z-index: 2; }
    .block-corner-marker.top-left { top: 0; left: 0; }
    .block-corner-marker.top-right { top: 0; right: 0; }
    .block-corner-marker.bottom-left { bottom: 0; left: 0; }
    .block-corner-marker.bottom-right { bottom: 0; right: 0; }
    .instructions { flex: 2; min-width: 0; }
</style>
<div class="folio-instructions-row">
    <div class="folio-section">
        <!-- Marcadores de esquina -->
        <div class="block-corner-marker top-left"></div>
        <div class="block-corner-marker top-right"></div>
        <div class="block-corner-marker bottom-left"></div>
        <div class="block-corner-marker bottom-right"></div>
        <!-- Header con espacios para escribir -->
        <div class="folio-header">
            <div class="folio-digit-column"></div>
            @for($i = 0; $i < 9; $i++)
                <div class="folio-position-header"></div>
            @endfor
        </div>
        <!-- Filas de dígitos con burbujas -->
        @for($digit = 0; $digit <= 9; $digit++)
            <div class="folio-row">
                <div class="folio-digit-number">{{ $digit }}</div>
                <div class="folio-bubbles-row">
                    @for($i = 0; $i < 9; $i++)
                        @php
                            $folioDigit = isset($folio) && strlen($folio) > $i ? $folio[$i] : null;
                            $isSelected = $folioDigit == $digit;
                        @endphp
                        <div class="bubble {{ $isSelected ? 'bubble-filled' : '' }}"></div>
                    @endfor
                </div>
            </div>
        @endfor
    </div>
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
</div>

<div class="section-title">PREGUNTAS ({{ $totalQuestions }} total)</div>

@foreach($questions as $index => $question)
    <div style="margin-bottom: 6mm; border-bottom: 1px solid #ccc; padding-bottom: 3mm; page-break-inside: avoid;">
        <div style="font-weight: bold; margin-bottom: 2mm; font-size: 11px;">
            {{ $index }}. {{ $question }}
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


@endsection