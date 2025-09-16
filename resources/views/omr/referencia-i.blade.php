@extends('omr.layout')

@section('title', 'Guía de Referencia I')



@section('content')
<!-- Marcadores de referencia en las esquinas -->
<div class="alignment-marker marker-top-left"></div>
<div class="alignment-marker marker-top-right"></div>
<div class="alignment-marker marker-bottom-left"></div>
<div class="alignment-marker marker-bottom-right"></div>

<style>
    .folio-instructions-row { display: flex; gap: 8mm; margin-bottom: 6mm; align-items: flex-start; }
    .folio-section { border: 1px solid black; padding: 1mm; padding-top: 2mm; background: #f8f8f8; position: relative; min-width: 80mm; max-width: 100mm; flex: 1; }
    .folio-header { display: flex; gap: 1mm; margin-bottom: 2mm; align-items: center; }
    .folio-digit-column { width: 8mm; text-align: center; font-weight: bold; font-size: 7px; }
    .folio-position-header { flex: 1; text-align: center; font-size: 6px; font-weight: bold; border: 1px solid black; height: 4mm; display: flex; align-items: center; justify-content: center; background: white; }
    .folio-row { display: flex; align-items: center; margin-bottom: 1.2mm; font-size: 6px; min-height: 4mm; gap: 1mm; }
    .folio-digit-number { font-weight: bold; width: 8mm; text-align: center; flex-shrink: 0; font-size: 7px; }
    .folio-bubbles-row { display: flex; gap: 1mm; align-items: center; flex: 1; justify-content: space-around; }
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
        <p>• Las siguientes preguntas están relacionadas con las situaciones que ha experimentado durante o con motivo del trabajo en las últimas 4 semanas.</p>
        <p>• Para responder las preguntas marque completamente con tinta azul o negra el círculo de la opción que mejor describa su situación:</p>
        <p style="margin-left: 5mm;"><strong>SÍ</strong> = Si experimentó la situación que se pregunta</p>
        <p style="margin-left: 5mm;"><strong>NO</strong> = Si NO experimentó la situación que se pregunta</p>
        <p>• Es importante que conteste todas las preguntas.</p>
    </div>
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
@endsection