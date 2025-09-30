@extends('omr.layout')

@section('title', 'Guía de Referencia I')

@section('guide-title', 'GUÍA DE REFERENCIA I - CUESTIONARIO PARA IDENTIFICAR ACONTECIMIENTOS TRAUMÁTICOS SEVEROS')

@section('content')
<!-- Marcadores de referencia en las esquinas -->
<div class="alignment-marker marker-top-left"></div>
<div class="alignment-marker marker-top-right"></div>
<div class="alignment-marker marker-bottom-left"></div>
<div class="alignment-marker marker-bottom-right"></div>

<style>
    .folio-instructions-row { 
        display: flex; 
        gap: 6mm; 
        margin-bottom: 6mm; 
        align-items: flex-start; 
    }
    .folio-section { 
        border: 2px solid black; 
        padding: 3mm; 
        background: #f8f8f8; 
        position: relative; 
        min-width: 60mm; 
        max-width: 80mm; 
        flex: 1; 
    }
    .folio-header { 
        display: flex; 
        gap: 1.5mm; 
        margin-bottom: 2mm; 
        align-items: center; 
    }
    .folio-digit-column { 
        width: 8mm; 
        text-align: center; 
        font-weight: bold; 
        font-size: 7px; 
    }
    .folio-position-header { 
        flex: 1; 
        text-align: center; 
        font-size: 6px; 
        font-weight: bold; 
        border: 1px solid black; 
        height: 4mm; 
        display: flex; 
        align-items: center; 
        justify-content: center; 
        background: white; 
    }
    .folio-row { 
        display: flex; 
        align-items: center; 
        gap: 1.5mm; 
        margin-bottom: 1.2mm; 
        font-size: 5px; 
        min-height: 3.5mm; 
    }
    .folio-digit-number { 
        font-weight: bold; 
        width: 8mm; 
        text-align: center; 
        flex-shrink: 0; 
        font-size: 7px; 
    }
    .folio-bubbles-row { 
        display: flex; 
        gap: 1.5mm; 
        align-items: center; 
        flex: 1; 
        justify-content: space-between; 
    }
    .instructions { 
        flex: 2; 
        min-width: 0; 
        font-size: 7px;
    }
    .bubble-small { 
        width: 2.5mm; 
        height: 2.5mm; 
        border: 1px solid black; 
        border-radius: 50%; 
        flex-shrink: 0; 
    }
    .folio-instructions-row {
        margin-bottom: 4mm;
        padding-bottom: 3mm;
        border-bottom: 2px solid black;
    }
    .content-section {
        margin-top: 4mm;
    }
</style>
<div class="folio-instructions-row">
    <div class="folio-section">
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
                        <div class="bubble-small {{ $isSelected ? 'bubble-filled' : '' }}"></div>
                    @endfor
                </div>
            </div>
        @endfor
    </div>
    <div class="instructions">
        <h3 style="font-weight: bold; margin-bottom: 3mm; font-size: 8px;">INSTRUCCIONES:</h3>
        <p style="font-size: 7px;">• Las siguientes preguntas están relacionadas con las situaciones que ha experimentado durante o con motivo del trabajo en las últimas 4 semanas.</p>
        <p style="font-size: 7px;">• Para responder las preguntas marque completamente con tinta azul o negra el círculo de la opción que mejor describa su situación:</p>
        <p style="margin-left: 5mm; font-size: 7px;"><strong>SÍ</strong> = Si experimentó la situación que se pregunta</p>
        <p style="margin-left: 5mm; font-size: 7px;"><strong>NO</strong> = Si NO experimentó la situación que se pregunta</p>
        <p style="font-size: 7px;">• Es importante que conteste todas las preguntas.</p>
    </div>
</div>

<div class="content-section">
    <div class="section-title">PREGUNTAS ({{ $totalQuestions }} total)</div>

@foreach($questions as $index => $question)
    <div class="question-row">
        <div class="question-number">{{ $index + 1 }}.</div>
        <div style="flex: 1; margin-right: 5mm; font-size: 10px; line-height: 1.2;">
            {{ $question }}
        </div>
        <div style="display: flex; align-items: center; gap: 3mm; flex-shrink: 0;">
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