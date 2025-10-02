@extends('omr.layout')

@section('title', 'Guía de Referencia I')

@section('guide-title', 'GUÍA DE REFERENCIA I - CUESTIONARIO PARA IDENTIFICAR ACONTECIMIENTOS TRAUMÁTICOS SEVEROS')

@section('content')
<style>
    .folio-instructions-row { 
        display: flex; 
        gap: 4mm; 
        margin-bottom: 3mm; 
        align-items: flex-start; 
    }
    .folio-section { 
        border: 2px solid black; 
        padding: 2mm; 
        background: #f8f8f8; 
        position: relative; 
        min-width: 55mm; 
        max-width: 70mm; 
        flex: 1; 
    }
    .folio-header { 
        display: flex; 
        gap: 1mm; 
        margin-bottom: 1.5mm; 
        align-items: center; 
    }
    .folio-digit-column { 
        width: 6mm; 
        text-align: center; 
        font-weight: bold; 
        font-size: 6px; 
    }
    .folio-position-header { 
        flex: 1; 
        text-align: center; 
        font-size: 5px; 
        font-weight: bold; 
        border: 1px solid black; 
        height: 3.5mm; 
        display: flex; 
        align-items: center; 
        justify-content: center; 
        background: white; 
    }
    .folio-row { 
        display: flex; 
        align-items: center; 
        gap: 1mm; 
        margin-bottom: 1mm; 
        font-size: 5px; 
        min-height: 3mm; 
    }
    .folio-digit-number { 
        font-weight: bold; 
        width: 6mm; 
        text-align: center; 
        flex-shrink: 0; 
        font-size: 6px; 
    }
    .folio-bubbles-row { 
        display: flex; 
        gap: 1mm; 
        align-items: center; 
        flex: 1; 
        justify-content: space-between; 
    }
    .instructions { 
        flex: 2; 
        min-width: 0; 
        font-size: 6.5px;
        line-height: 1.3;
    }
    .bubble-small { 
        width: 2.5mm; 
        height: 2.5mm; 
        border: 1px solid black; 
        border-radius: 50%; 
        flex-shrink: 0; 
    }
    .folio-instructions-row {
        margin-bottom: 3mm;
        padding-bottom: 2mm;
        border-bottom: 1.5px solid black;
    }
    .content-section {
        margin-top: 2mm;
    }
    .question-row {
        display: flex;
        align-items: center;
        margin-bottom: 2mm;
        min-height: 4mm;
    }
    .question-number {
        width: 6mm;
        font-weight: bold;
        flex-shrink: 0;
        font-size: 6px;
    }
    .question-text {
        flex: 1;
        margin-right: 3mm;
        font-size: 6px;
        line-height: 1.2;
    }
    .option-group {
        display: flex;
        align-items: center;
        gap: 1mm;
        margin: 0 1.5mm;
    }
    .option-label {
        font-size: 6px;
        font-weight: bold;
    }
    .bubble {
        width: 3mm;
        height: 3mm;
        border: 1px solid black;
        border-radius: 50%;
        flex-shrink: 0;
    }
    .section-title {
        font-weight: bold;
        margin-bottom: 2mm;
        font-size: 8px;
        text-align: center;
    }
</style>

<div>
    <div class="folio-instructions-row">
        <div class="folio-section">
        <!-- Header con espacios para escribir los dígitos -->
        <div class="folio-header">
            <div class="folio-digit-column"></div>
            @for($i = 0; $i < 9; $i++)
                <div class="folio-position-header">
                    {{ isset($folio) && strlen($folio) > $i ? $folio[$i] : '' }}
                </div>
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
            <h3 style="font-weight: bold; margin-bottom: 1.5mm; font-size: 7px;">INSTRUCCIONES:</h3>
            <p style="font-size: 6px; margin-bottom: 0.8mm;">• Las siguientes preguntas están relacionadas con las situaciones que ha experimentado durante o con motivo del trabajo en las últimas 4 semanas.</p>
            <p style="font-size: 6px; margin-bottom: 0.8mm;">• Para responder las preguntas marque completamente con tinta azul o negra el círculo de la opción que mejor describa su situación:</p>
            <p style="margin-left: 3mm; font-size: 6px; margin-bottom: 0.8mm;"><strong>SÍ</strong> = Si experimentó la situación que se pregunta</p>
            <p style="margin-left: 3mm; font-size: 6px; margin-bottom: 0.8mm;"><strong>NO</strong> = Si NO experimentó la situación que se pregunta</p>
            <p style="font-size: 6px;">• Es importante que conteste todas las preguntas.</p>
        </div>
    </div>

    <div class="content-section">
        <div class="section-title">PREGUNTAS ({{ $totalQuestions }} total)</div>
        
        @foreach($questions as $index => $question)
            <div class="question-row">
                <div class="question-number">{{ $index + 1 }}.</div>
                <div class="question-text">
                    {{ $question }}
                </div>
                <div style="display: flex; align-items: center; gap: 1.5mm; flex-shrink: 0;">
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
    </div>
</div>
@endsection