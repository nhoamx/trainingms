@extends('omr.layout')

@section('title', 'Guía de Referencia III')

@section('guide-title', 'GUÍA DE REFERENCIA III - CUESTIONARIO PARA IDENTIFICAR LOS FACTORES DE RIESGO PSICOSOCIAL')

@section('content')
<style>
    .folio-instructions-row { 
        display: flex; 
        gap: 4mm; 
        margin-bottom: 3mm;
        padding-bottom: 2mm;
        border-bottom: 1.5px solid black;
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
    .right-side-container {
        flex: 2;
        display: flex;
        flex-direction: column;
        gap: 2mm;
        min-width: 0;
    }
    .instructions { 
        font-size: 6.5px;
        line-height: 1.3;
    }
    .bubble-small { 
        width: 3.5mm; 
        height: 3.5mm; 
        border: 1px solid black; 
        border-radius: 50%; 
        flex-shrink: 0; 
    }
    .three-column-layout {
        display: flex;
        gap: 2mm;
        width: 100%;
        margin-top: 3mm;
    }
    .column {
        width: 32%;
        page-break-inside: avoid;
    }
    .column-header {
        display: flex;
        justify-content: space-around;
        font-weight: bold;
        font-size: 6px;
        background: #e8e8e8;
        border: 1px solid black;
        padding: 1mm 0.5mm;
        margin-bottom: 2mm;
        text-align: center;
    }
    .column-header-label {
        flex: 1;
        text-align: center;
        font-weight: bold;
    }
    .column-header-options {
        display: flex;
        justify-content: space-around;
        flex: 5;
        gap: 0.8mm;
    }
    .option-label {
        flex: 1;
        font-size: 6px;
        font-weight: bold;
    }
    .question-row-vertical {
        display: flex;
        align-items: center;
        margin-bottom: 1mm;
        font-size: 5.5px;
        min-height: 3.5mm;
    }
    .question-number-vertical {
        font-weight: bold;
        width: 7mm;
        text-align: center;
        flex-shrink: 0;
        font-size: 6px;
    }
    .answers-vertical {
        display: flex;
        gap: 0.8mm;
        align-items: center;
        flex: 1;
        justify-content: space-around;
    }
    .bubble-tiny {
        width: 3.5mm;
        height: 3.5mm;
        border: 1px solid black;
        border-radius: 50%;
    }
    
    /* CITSATS-s1 Section Styles */
    .citsats-section {
        width: 50%;
        padding: 2mm;
    }
    .citsats-title {
        font-weight: bold;
        font-size: 7px;
        margin-bottom: 2mm;
        text-align: left;
        margin-left: 2mm;
    }
    .citsats-question {
        display: flex;
        align-items: center;
        margin-bottom: 1.5mm;
        font-size: 5.5px;
    }
    .citsats-number {
        font-weight: bold;
        width: 7mm;
        text-align: center;
        font-size: 6px;
    }
    .citsats-options {
        display: flex;
        gap: 3mm;
        align-items: center;
    }
    .citsats-option {
        display: flex;
        align-items: center;
        gap: 1mm;
    }
    .citsats-option-label {
        font-size: 6px;
        font-weight: bold;
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
        
        <div class="right-side-container">
            <div class="instructions">
                <h3 style="font-weight: bold; margin-bottom: 3mm; font-size: 8px;">INSTRUCCIONES:</h3>
                <p style="font-size: 7px;">• Las siguientes preguntas están relacionadas con las actividades que realiza en su trabajo y las condiciones en que las hace.</p>
                <p style="font-size: 7px;">• Marque completamente con tinta azul o negra el círculo de la opción que mejor describa su situación:</p>
                <p style="margin-left: 5mm; font-size: 7px;"><strong>Siempre - Casi siempre - Algunas veces - Casi nunca - Nunca</strong></p>
                <p style="font-size: 7px;">• Es importante que conteste todas las preguntas.</p>
            </div>
            
            <!-- CITSATS-s1 Section: ocupando solo 1 de las 2 columnas del lado derecho -->
            <div class="citsats-section">
                <div class="citsats-title">CITSATS-s1</div>
                @for($i = 1; $i <= 6; $i++)
                    <div class="citsats-question">
                        <div class="citsats-number">{{ $i }}</div>
                        <div class="citsats-options">
                            <div class="citsats-option">
                                <span class="citsats-option-label">SÍ</span>
                                <div class="bubble-tiny"></div>
                            </div>
                            <div class="citsats-option">
                                <span class="citsats-option-label">NO</span>
                                <div class="bubble-tiny"></div>
                            </div>
                        </div>
                    </div>
                @endfor
            </div>
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
                <!-- Column Header with A, B, C, D, E labels -->
                <div class="column-header">
                    <div class="column-header-label">#</div>
                    <div class="column-header-options">
                        <div class="option-label">A</div>
                        <div class="option-label">B</div>
                        <div class="option-label">C</div>
                        <div class="option-label">D</div>
                        <div class="option-label">E</div>
                    </div>
                </div>
                
                @php 
                    $conditionalSectionsAdded = [];
                @endphp
                @foreach($columnQuestions as $number => $questionData)
                    
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

@endsection