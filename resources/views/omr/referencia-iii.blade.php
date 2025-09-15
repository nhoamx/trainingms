@extends('omr.layout')

@section('title', 'Guía de Referencia III')

@section('guide-title', 'GUÍA DE REFERENCIA III - CUESTIONARIO PARA IDENTIFICAR LOS FACTORES DE RIESGO PSICOSOCIAL')

@section('content')
<style>
    .page-container {
        position: relative;
        margin: 0 auto;
        padding: 10mm;
        background: white;
    }
    .three-column-layout {
        display: flex;
        gap: 3mm;
        width: 100%;
        margin-top: 5mm;
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
    /* Marcadores de alineación OMR */
    .reference-marker {
        width: 4mm;
        height: 4mm;
        background: black;
        position: absolute;
    }
    .reference-marker.top-left {
        top: 5mm;
        left: 5mm;
    }
    .reference-marker.top-right {
        top: 5mm;
        right: 5mm;
    }
    .reference-marker.bottom-left {
        bottom: 5mm;
        left: 5mm;
    }
    .reference-marker.bottom-right {
        bottom: 5mm;
        right: 5mm;
    }
    .block-separator {
        height: 4mm;
        position: relative;
        margin: 1mm 0;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .block-marker {
        width: 3mm;
        height: 3mm;
        background: black;
    }
    .instructions {
        margin-bottom: 3mm;
        font-size: 6px;
        text-align: center;
    }
    /* Marcadores de esquina para bloques */
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
    /* Estilos para folio usando patrón question-row-vertical */
    .folio-section {
        border: 1px solid black;
        padding: 1mm;
        padding-top: 2mm;
        margin-bottom: 3mm;
        background: #f8f8f8;
    }
    .folio-header {
        display: flex;
        gap: 1mm;
        margin-bottom: 2mm;
        align-items: center;
    }
    .folio-digit-column {
        width: 8mm; /* Mismo ancho que question-number-vertical */
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
        margin-bottom: 1.2mm; /* Mismo margin que question-row-vertical */
        font-size: 6px;
        min-height: 4mm; /* Mismo min-height que question-row-vertical */
        gap: 1mm;
    }
    .folio-digit-number {
        font-weight: bold;
        width: 8mm; /* Mismo ancho que question-number-vertical */
        text-align: center;
        flex-shrink: 0;
        font-size: 7px;
    }
    .folio-bubbles-row {
        display: flex;
        gap: 1mm;
        align-items: center;
        flex: 1;
        justify-content: space-around; /* Mismo justify que answers-vertical */
    }
    .date-section {
        display: flex;
        gap: 5mm;
        align-items: center;
        margin: 2mm 0;
        font-size: 6px;
    }
    .date-field {
        display: flex;
        align-items: center;
        gap: 1mm;
    }
    .date-line {
        border-bottom: 1px solid black;
        width: 15mm;
        height: 3mm;
    }
</style>

<div class="page-container">
    <!-- Marcadores de referencia en las esquinas -->
    <div class="reference-marker top-left"></div>
    <div class="reference-marker top-right"></div>
    <div class="reference-marker bottom-left"></div>
    <div class="reference-marker bottom-right"></div>

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
                @if($columnIndex == 0)
                    <!-- Sección de folio usando patrón question-row-vertical -->
                    <div class="folio-section" style="position: relative;">
                        <!-- Marcadores de esquina -->
                        <div class="block-corner-marker top-left"></div>
                        <div class="block-corner-marker top-right"></div>
                        <div class="block-corner-marker bottom-left"></div>
                        <div class="block-corner-marker bottom-right"></div>
                        <!-- Header con espacios para escribir -->
                        <div class="folio-header">
                            <div class="folio-digit-column"></div> <!-- Espacio vacío para alinear con números -->
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
                                        <div class="bubble-tiny"></div>
                                    @endfor
                                </div>
                            </div>
                        @endfor
                    </div>
                @endif
                
                <!-- Header inicial con opciones de respuesta -->
                {{-- <div class="column-header">
                    <span></span>
                    <span>A</span>
                    <span>B</span>
                    <span>C</span>
                    <span>D</span>
                    <span>E</span>
                </div> --}}
                
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