@extends('omr.layout')

@section('title', 'Guía de Referencia III')

@section('nom-header')
    <h2>NOM-035-STPS-2018</h2>
    <p></p>
@endsection

@section('guide-title', 'GUÍA DE REFERENCIA III - CUESTIONARIO PARA IDENTIFICAR LOS FACTORES DE RIESGO PSICOSOCIAL Y EVALUAR EL ENTORNO ORGANIZACIONAL EN LOS CENTROS DE TRABAJO')

@section('content')
<style>
    .folio-instructions-row { 
        display: flex; 
        gap: 6mm; 
        margin-top: 3mm;
        margin-bottom: 3mm;
        padding-bottom: 3mm;
        border-bottom: 1.5px solid #333;
        align-items: flex-start; 
    }
    .folio-section { 
        border: 2px solid black; 
        padding: 2mm 3mm; 
        position: relative; 
        width: 68mm;
        flex-shrink: 0;
    }
    .folio-title {
        font-weight: bold;
        font-size: 9px;
        text-align: center;
        margin-bottom: 1.5mm;
        letter-spacing: 2px;
        text-transform: uppercase;
    }
    .folio-header { 
        display: flex; 
        gap: 0.8mm; 
        margin-bottom: 1.5mm; 
        align-items: center; 
    }
    .folio-digit-column { 
        width: 5mm; 
        text-align: center; 
        font-weight: bold; 
        font-size: 6px; 
    }
    .folio-position-header { 
        flex: 1; 
        text-align: center; 
        font-size: 9px; 
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
        gap: 0.8mm; 
        margin-bottom: 0.8mm; 
        min-height: 3mm; 
    }
    .folio-digit-number { 
        font-weight: bold; 
        width: 5mm; 
        text-align: center; 
        flex-shrink: 0; 
        font-size: 9px; 
    }
    .folio-bubbles-row { 
        display: flex; 
        gap: 0.8mm; 
        align-items: center; 
        flex: 1; 
        justify-content: space-between; 
    }
    .right-side-container {
        flex: 1;
        display: flex;
        flex-direction: column;
        gap: 0;
        min-width: 0;
    }
    .instructions { 
        font-size: 10px;
        line-height: 1.4;
    }
    .instructions h3 {
        font-size: 11px;
        font-weight: bold;
        margin-bottom: 1.5mm;
        border-bottom: 1px solid #999;
        padding-bottom: 1mm;
    }
    .instructions ol {
        padding-left: 4mm;
        margin: 0;
    }
    .instructions ol li {
        margin-bottom: 0.8mm;
    }
    .options-grid {
        display: grid;
        grid-template-columns: repeat(3, auto);
        gap: 0.5mm 4mm;
        margin: 1mm 0 1mm 4mm;
        font-weight: bold;
        font-size: 10px;
        justify-content: start;
    }
    .options-grid span {
        white-space: nowrap;
    }
    .bubble-small { 
        width: 4mm; 
        height: 4mm; 
        border: 1.5px solid black; 
        border-radius: 50%; 
        flex-shrink: 0; 
    }
    .three-column-layout {
        display: flex;
        gap: 2mm;
        width: 100%;
        margin-top: 1mm;
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
        font-size: 11px;
        font-weight: bold;
    }
    .question-row-vertical {
        display: flex;
        align-items: center;
        margin-bottom: 1mm;
        font-size: 6.5px;
        min-height: 3.5mm;
    }
    .question-number-vertical {
        font-weight: bold;
        width: 7mm;
        text-align: center;
        flex-shrink: 0;
        font-size: 11px;
    }
    .answers-vertical {
        display: flex;
        gap: 0.8mm;
        align-items: center;
        flex: 1;
        justify-content: space-around;
    }
    .bubble-tiny {
        width: 4.5mm;
        height: 4.5mm;
        border: 1.5px solid black;
        border-radius: 50%;
    }
    
    /* CITSATS-s1 Section Styles */
    .citsats-section {
        width: 50%;
        padding: 2mm;
    }
    .citsats-title {
        font-weight: bold;
        font-size: 11px;
        margin-bottom: 2mm;
        text-align: left;
        margin-left: 2mm;
    }
    .citsats-question {
        display: flex;
        align-items: center;
        margin-bottom: 1.5mm;
        font-size: 6.5px;
    }
    .citsats-number {
        font-weight: bold;
        width: 7mm;
        text-align: center;
        font-size: 7px;
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
        font-size: 11px;
        font-weight: bold;
    }
</style>

@section('date-row')
        <div style="display: flex; align-items: center; justify-content: space-between; gap: 5mm; width: 100%;">
            <!-- Izquierda: XYZ -->
            <div style="flex-shrink: 0; font-weight: bold; font-size: 12px;">
                TMS-GR3-FRP-EOF
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

@php
    $showPrefilledFolio = $showPrefilledFolio ?? true;
@endphp

<div class="folio-instructions-row">
        <div class="folio-section">
            <div class="folio-title">Folio</div>
            <!-- Header con espacios para escribir los dígitos -->
            <div class="folio-header">
                <div class="folio-digit-column"></div>
                @for($i = 0; $i < 11; $i++)
                    <div class="folio-position-header">
                        {{ $showPrefilledFolio && isset($folio) && strlen($folio) > $i ? $folio[$i] : '' }}
                    </div>
                @endfor
            </div>
            <!-- Filas de dígitos con burbujas -->
            @for($digit = 0; $digit <= 9; $digit++)
                <div class="folio-row">
                    <div class="folio-digit-number">{{ $digit }}</div>
                    <div class="folio-bubbles-row">
                        @for($i = 0; $i < 11; $i++)
                            @php
                                $folioDigit = $showPrefilledFolio && isset($folio) && strlen($folio) > $i ? $folio[$i] : null;
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
                <h3>INDICACIONES</h3>
                <ol>
                    <li>Utilizar pluma negra, punta mediana.</li>
                    <li>No doblar la hoja.</li>
                    <li>Seleccionar solamente 1 opción en cada pregunta.</li>
                    <li>Opciones:</li>
                </ol>
                <div class="options-grid">
                    <span>A. Siempre</span>
                    <span>B. Casi siempre</span>
                    <span>C. Algunas veces</span>
                    <span>D. Casi nunca</span>
                    <span>E. Nunca</span>
                </div>
                <ol start="5">
                    <li>Importante contestar todas las preguntas.</li>
                    <li>Contestar objetivamente con sinceridad tu percepción de 2 meses a la fecha.</li>
                    <li>Rellenar completamente el círculo.</li>
                </ol>
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
        
        // Dividir en tres columnas por rango específico
        $column1 = [];
        $column2 = [];
        $column3 = [];
        
        foreach($allQuestions as $number => $questionData) {
            if($number >= 1 && $number <= 27) {
                $column1[$number] = $questionData;
            } elseif($number >= 28 && $number <= 54) {
                $column2[$number] = $questionData;
            } else {
                $column3[$number] = $questionData;
            }
        }
        
        $columns = [$column1, $column2, $column3];
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
                                        <span style="font-size: 12px; font-weight: bold;">SÍ</span>
                                        <div class="bubble-tiny"></div>
                                    </div>
                                    <div style="display: flex; align-items: center; gap: 1mm;">
                                        <span style="font-size: 12px; font-weight: bold;">NO</span>
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
                
                @if($columnIndex == 2)
                    <!-- CITSATS-s1 Section: 2 columnas dentro de la columna 3 -->
                    <div style="margin-top: 1mm; padding: 2mm; ">
                        <div style="font-weight: bold; font-size: 12px; margin-bottom: 2mm; padding-bottom: 1mm;">
                            TMS - GRI - ATS - S1
                        </div>
                        <div style="display: flex; gap: 3mm;">
                            <!-- Columna 1 -->
                            <div style="flex: 1;">
                                @for($i = 1; $i <= 3; $i++)
                                    <div style="display: flex; align-items: center; gap: 2mm; margin-bottom: 2mm; font-size: 11px;">
                                        <div style="font-weight: bold;">{{ $i }}</div>
                                        <div style="display: flex; gap: 3mm; align-items: center;">
                                            <div style="display: flex; align-items: center; gap: 1mm;">
                                                <span style="font-weight: bold;">SÍ</span>
                                                <div class="bubble-tiny"></div>
                                            </div>
                                            <div style="display: flex; align-items: center; gap: 1mm;">
                                                <span style="font-weight: bold;">NO</span>
                                                <div class="bubble-tiny"></div>
                                            </div>
                                        </div>
                                    </div>
                                @endfor
                            </div>
                            <!-- Columna 2 -->
                            <div style="flex: 1;">
                                @for($i = 4; $i <= 6; $i++)
                                    <div style="display: flex; align-items: center; gap: 2mm; margin-bottom: 2mm; font-size: 11px;">
                                        <div style="font-weight: bold;">{{ $i }}</div>
                                        <div style="display: flex; gap: 3mm; align-items: center;">
                                            <div style="display: flex; align-items: center; gap: 1mm;">
                                                <span style="font-weight: bold;">SÍ</span>
                                                <div class="bubble-tiny"></div>
                                            </div>
                                            <div style="display: flex; align-items: center; gap: 1mm;">
                                                <span style="font-weight: bold;">NO</span>
                                                <div class="bubble-tiny"></div>
                                            </div>
                                        </div>
                                    </div>
                                @endfor
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        @endforeach
    </div>

@endsection