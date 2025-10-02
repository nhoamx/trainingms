@extends('omr.layout')

@section('title', 'Escala Cisneros')

@section('guide-title', 'ESCALA CISNEROS - CUESTIONARIO SOBRE VIOLENCIA PSICOLÓGICA EN EL TRABAJO')

@section('content')
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
        width: 3.5mm; 
        height: 3.5mm; 
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
        <h3 style="font-weight: bold; margin-bottom: 3mm; font-size: 8px;">INSTRUCCIONES:</h3>
        <p style="font-size: 7px;">• Las siguientes preguntas están relacionadas con situaciones de violencia psicológica en el trabajo.</p>
        <p style="font-size: 7px;">• Para cada pregunta, debe responder dos aspectos:</p>
        <div style="margin: 2mm 0 2mm 5mm; font-size: 7px;">
            <p><strong>1. Tipo de persona involucrada:</strong></p>
            <p style="margin-left: 5mm;"><strong>A</strong> = Jefas/jefes o personas supervisoras</p>
            <p style="margin-left: 5mm;"><strong>B</strong> = Personas compañeras de trabajo</p>
            <p style="margin-left: 5mm;"><strong>C</strong> = Personas subordinadas</p>
        </div>
        <div style="margin: 2mm 0 2mm 5mm; font-size: 7px;">
            <p><strong>2. Frecuencia de la situación:</strong></p>
            <p style="margin-left: 5mm;"><strong>0</strong> = Nunca</p>
            <p style="margin-left: 5mm;"><strong>1</strong> = Pocas veces al año o menos</p>
            <p style="margin-left: 5mm;"><strong>2</strong> = Una vez al mes o menos</p>
            <p style="margin-left: 5mm;"><strong>3</strong> = Algunas veces al mes</p>
            <p style="margin-left: 5mm;"><strong>4</strong> = Una vez a la semana</p>
            <p style="margin-left: 5mm;"><strong>5</strong> = Varias veces a la semana</p>
            <p style="margin-left: 5mm;"><strong>6</strong> = Todos los días</p>
        </div>
        <p style="font-size: 7px;">• Responda considerando su experiencia en los últimos 6 meses.</p>
        <p style="font-size: 7px;">• Es importante que conteste todas las preguntas.</p>
    </div>
</div>

<div class="content-section">
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
</div>


</div>

@endsection