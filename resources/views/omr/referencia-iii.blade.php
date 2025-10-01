@extends('omr.layout')

@section('title', 'Guía de Referencia III')

@section('guide-title', 'GUÍA DE REFERENCIA III - CUESTIONARIO PARA IDENTIFICAR LOS FACTORES DE RIESGO PSICOSOCIAL')

@section('content')
<!-- Marcadores de referencia en las esquinas -->
<div class="alignment-marker marker-top-left"></div>
<div class="alignment-marker marker-top-right"></div>
<div class="alignment-marker marker-bottom-left"></div>
<div class="alignment-marker marker-bottom-right"></div>

<style>
    .page-container {
        position: relative;
        margin: 0 auto;
        background: white;
    }
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
    .three-column-layout {
        display: flex;
        gap: 3mm;
        width: 100%;
        margin-top: 4mm;
    }
    .column {
        width: 32%;
        page-break-inside: avoid;
    }
    .column-header {
        display: flex;
        justify-content: space-around;
        font-weight: bold;
        font-size: 7px;
        background: #f8f8f8;
        border: 1px solid black;
        padding: 1.5mm;
        margin-bottom: 2mm;
        text-align: center;
    }
    .question-row-vertical {
        display: flex;
        align-items: center;
        margin-bottom: 1.2mm;
        font-size: 6px;
        min-height: 4mm;
    }
    .question-number-vertical {
        font-weight: bold;
        width: 8mm;
        text-align: center;
        flex-shrink: 0;
        font-size: 7px;
    }
    .answers-vertical {
        display: flex;
        gap: 1mm;
        align-items: center;
        flex: 1;
        justify-content: space-around;
    }
    .bubble-tiny {
        width: 3mm;
        height: 3mm;
        border: 1.5px solid black;
        border-radius: 50%;
    }
    .block-separator {
        height: 4mm;
        position: relative;
        margin: 1mm 0;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .block-corner-marker {
        position: absolute;
        width: 2.5mm;
        height: 2.5mm;
        background: black;
        z-index: 2;
    }
    .block-corner-marker.top-left { top: 0; left: 0; }
    .block-corner-marker.top-right { top: 0; right: 0; }
    .block-corner-marker.bottom-left { bottom: 0; left: 0; }
    .block-corner-marker.bottom-right { bottom: 0; right: 0; }
</style>

<div class="page-container">
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
            <p style="font-size: 7px;">• Las siguientes preguntas están relacionadas con las actividades que realiza en su trabajo y las condiciones en que las hace.</p>
            <p style="font-size: 7px;">• Marque completamente con tinta azul o negra el círculo de la opción que mejor describa su situación:</p>
            <p style="margin-left: 5mm; font-size: 7px;"><strong>Siempre - Casi siempre - Algunas veces - Casi nunca - Nunca</strong></p>
            <p style="font-size: 7px;">• Es importante que conteste todas las preguntas.</p>
        </div>
    </div>

    @php
        // Combinar todas las preguntas en orden
        $allQuestions = [];
        
        // Agregar preguntas generales
        foreach($generalQuestions as $number => $question) {
            $allQuestions[$number] = ['type' => 'general', 'question' => $question];
        }
        
        // Agregar preguntas condicionales
        foreach($conditionalSections as $sectionKey => $section) {
            if(isset($section['questions'])) {
                foreach($section['questions'] as $number => $question) {
                    $allQuestions[$number] = ['type' => 'conditional', 'question' => $question, 'condition' => $section['condition'], 'section_key' => $sectionKey];
                }
            }
        }
        
        // Ordenar por número
        ksort($allQuestions);
        
        // Dividir en tres columnas
        $totalQuestions = count($allQuestions);
        $questionsPerColumn = ceil($totalQuestions / 3);
        $columns = array_chunk($allQuestions, $questionsPerColumn, true);
    @endphp

    <div class="three-column-layout">
        @foreach($columns as $columnIndex => $columnQuestions)
            <div class="column">
                @php 
                    $questionCount = 0; 
                    $conditionalSectionsAdded = [];
                @endphp
                @foreach($columnQuestions as $number => $questionData)
                    @php $questionCount++; @endphp
                    
                    <!-- Añadir header de bloque cada 10 preguntas -->
                    @if($questionCount % 10 == 1 && $questionCount > 1)
                        <!-- Separador a la misma altura en todas las columnas -->
                        <div class="block-separator" style="display: flex; align-items: center; position: relative;">
                            <!-- Marcadores de esquina del bloque -->
                            <div class="block-corner-marker top-left"></div>
                            <div class="block-corner-marker top-right"></div>
                            <div class="block-corner-marker bottom-left"></div>
                            <div class="block-corner-marker bottom-right"></div>
                        </div>
                    @endif
                    
                    <!-- Si es pregunta condicional (65-72), añadir SÍ/NO antes -->
                    @if($questionData['type'] == 'conditional' && !in_array($questionData['section_key'], $conditionalSectionsAdded))
                        @php $conditionalSectionsAdded[] = $questionData['section_key']; @endphp
                        <div style="margin: 2mm 0; padding: 1.5mm; border: 1px solid #999; font-size: 5px;">
                            <div style="display: flex; align-items: flex-start; justify-content: left; gap: 3mm;">
                                <div style="display: flex; gap: 4mm; align-items: center;">
                                    <div></div>
                                    <div style="display: flex; align-items: center; gap: 1mm;">
                                        <span style="font-size: 6px; font-weight: bold;">SÍ</span>
                                        <div class="bubble-tiny"></div>
                                    </div>
                                    <div style="display: flex; align-items: center; gap: 1mm;">
                                        <span style="font-size: 6px; font-weight: bold;">NO</span>
                                        <div class="bubble-tiny"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                    
                    <div class="question-row-vertical">
                        <div class="question-number-vertical">{{ $number }}</div>
                        <div class="answers-vertical">
                            <div class="bubble-tiny"></div>
                            <div class="bubble-tiny"></div>
                            <div class="bubble-tiny"></div>
                            <div class="bubble-tiny"></div>
                            <div class="bubble-tiny"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endforeach
    </div>

    
</div>
@endsection