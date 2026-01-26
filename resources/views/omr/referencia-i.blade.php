@extends('omr.layout')

@section('title', 'Guía de Referencia I')

@section('guide-title', 'GUÍA DE REFERENCIA I - CUESTIONARIO PARA IDENTIFICAR ACONTECIMIENTOS TRAUMÁTICOS SEVEROS')

@section('nom-header')
    <h2>NOM-035-STPS-2018</h2>
    <p></p>
@endsection

@section('content')
<style>
    .folio-instructions-row { 
        display: flex; 
        gap: 4mm; 
        margin-top: 3mm;
        margin-bottom: 3mm; 
        align-items: flex-start; 
    }
    .folio-section { 
        border: 2px solid black; 
        padding: 2mm; 
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
        font-size: 10px; 
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
        font-size: 10px; 
        min-height: 3mm; 
    }
    .folio-digit-number { 
        font-weight: bold; 
        width: 6mm; 
        text-align: center; 
        flex-shrink: 0; 
        font-size: 10px; 
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
        font-size: 7.5px;
        line-height: 1.3;
    }
    .bubble-small { 
        width: 4.5mm; 
        height: 4.5mm; 
        border: 1.5px solid black; 
        border-radius: 50%; 
        flex-shrink: 0; 
    }
    .folio-instructions-row {
        margin-bottom: 0mm;
        padding-bottom: 0mm;
    }
    .content-section {
        margin-top: 0mm;
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
        font-size: 12px;
    }
    .question-text {
        flex: 1;
        margin-right: 3mm;
        font-size: 12px;
        line-height: 1.2;
    }
    .option-group {
        display: flex;
        align-items: center;
        gap: 1mm;
        margin: 0 1.5mm;
    }
    .option-label {
        font-size: 12px;
        font-weight: bold;
    }
    .bubble {
        width: 5mm;
        height: 5mm;
        border: 1.5px solid black;
        border-radius: 50%;
        flex-shrink: 0;
    }
    .section-title {
        font-weight: bold;
        margin-bottom: 2mm;
        font-size: 9px;
        text-align: center;
    }
</style>

<div>

    @section('date-row')
        <div style="display: flex; align-items: center; justify-content: space-between; gap: 5mm; width: 100%;">
            <!-- Izquierda: XYZ -->
            <div style="flex-shrink: 0; font-weight: bold; font-size: 12px;">
                TMS-GR1-S1 S2 S3
            </div>
            
            <!-- Centro: Nombre -->
            <div style="flex: 1; display: flex; align-items: center; gap: 2mm;">
                <span style="font-weight: bold; font-size: 12px;">NOMBRE:</span>
                <div style="flex: 1; border-bottom: 2px solid black; height: 6mm;"></div>
            </div>
            
            <!-- Derecha: Fecha -->
            <div style="display: flex; gap: 3mm; align-items: center; flex-shrink: 0;">
                <div style="display: flex; align-items: center; gap: 1mm;">
                    <span style="font-size: 10px; font-weight: bold;">Día:</span>
                    <div style="width: 15mm; border-bottom: 2px solid black; height: 4mm;"></div>
                </div>
                <div style="display: flex; align-items: center; gap: 1mm;">
                    <span style="font-size: 10px; font-weight: bold;">Mes:</span>
                    <div style="width: 15mm; border-bottom: 2px solid black; height: 4mm;"></div>
                </div>
                <div style="display: flex; align-items: center; gap: 1mm;">
                    <span style="font-size: 10px; font-weight: bold;">Año:</span>
                    <div style="width: 15mm; border-bottom: 2px solid black; height: 4mm;"></div>
                </div>
            </div>
        </div>
    @endsection

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
                            // For Referencia I, don't fill bubbles in last 4 positions (person code)
                            // Only fill template code (0-1) and organization code (2-4)
                            $shouldFill = $i < 5 && $folioDigit == $digit;
                            $isSelected = $shouldFill;
                        @endphp
                        <div class="bubble-small {{ $isSelected ? 'bubble-filled' : '' }}"></div>
                    @endfor
                </div>
            </div>
        @endfor
        </div>
        <div class="instructions">
            <h3 style="font-weight: bold; margin-bottom: 1.5mm; font-size: 14px;">INDICACIONES:</h3>
            <p style="font-size: 14px; margin-bottom: 0.8mm;">1. Las siguientes preguntas están relacionadas con las situaciones que ha experimentado durante o con motivo del trabajo en el ultimo mes.</p>
            <p style="font-size: 14px; margin-bottom: 0.8mm;">2. Utiliza pluma negra, punta mediana.</p>
            <p style="font-size: 14px; margin-bottom: 0.8mm;">3. Para responder las preguntas marque completamente con tinta azul o negra el círculo de la opción que mejor describa su situación:</p>
            <p style="margin-left: 3mm; font-size: 14px; margin-bottom: 0.8mm;"><strong>SÍ</strong> = Si experimentó la situación que se pregunta</p>
            <p style="margin-left: 3mm; font-size: 14px; margin-bottom: 0.8mm;"><strong>NO</strong> = Si NO experimentó la situación que se pregunta</p>
        </div>
    </div>

    <div class="content-section">
        
        @foreach($questions as $index => $question)
            @php
                $questionNumber = $index + 1;
                $displayNumber = $questionNumber;
                
                // Resetear numeración al comenzar cada sección
                if ($questionNumber >= 1 && $questionNumber <= 2) {
                    $displayNumber = $questionNumber; // Sección II: 1, 2
                } elseif ($questionNumber >= 3 && $questionNumber <= 9) {
                    $displayNumber = $questionNumber - 2; // Sección III: 1, 2, 3, 4, 5, 6, 7
                } else {
                    $displayNumber = $questionNumber - 9; // Sección IV: 1, 2, 3...
                }
            @endphp
            
            {{-- Sección II: Preguntas 1 y 2 --}}
            @if($questionNumber === 1)
                <div class="section-title" style="margin-top: 3mm; margin-bottom: 2mm; font-size: 11px;">
                    II. Recuerdos persistentes sobre el acontecimiento (durante el último mes)
                </div>
            @endif
            
            {{-- Sección III: Preguntas 3 a 9 --}}
            @if($questionNumber === 3)
                <div class="section-title" style="margin-top: 3mm; margin-bottom: 2mm; font-size: 11px;">
                    III. Esfuerzo por evitar circunstancias parecidas o asociadas al acontecimiento (durante el último mes)
                </div>
            @endif
            
            {{-- Sección IV: Preguntas 10 en adelante --}}
            @if($questionNumber === 10)
                <div class="section-title" style="margin-top: 3mm; margin-bottom: 2mm; font-size: 11px;">
                    IV. Afectaciones (durante el último mes)
                </div>
            @endif
            
            <div class="question-row">
                <div class="question-number">{{ $displayNumber }}.</div>
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