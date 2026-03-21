@extends('omr.layout')

@section('title', 'Escala Cisneros')

@section('nom-header')
    <h2>NOM-035-STPS-2018</h2>
    <p></p>
@endsection

@section('guide-title', 'ESCALA CISNEROS - CUESTIONARIO SOBRE VIOLENCIA PSICOLÓGICA EN EL TRABAJO')

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
    .person-options-grid {
        display: grid;
        grid-template-columns: repeat(3, auto);
        gap: 0.5mm 4mm;
        margin: 1mm 0 1mm 4mm;
        font-weight: bold;
        font-size: 10px;
        justify-content: start;
    }
    .frequency-grid {
        display: grid;
        grid-template-columns: repeat(2, auto);
        gap: 0.5mm 4mm;
        margin: 1mm 0 1mm 4mm;
        font-weight: bold;
        font-size: 10px;
        justify-content: start;
    }
    .bubble-small {
        width: 4mm;
        height: 4mm;
        border: 1.5px solid black;
        border-radius: 50%;
        flex-shrink: 0;
    }
    .content-section {
        margin-top: 1.5mm;
    }
    .answer-sheet-layout {
        display: flex;
        gap: 2.4mm;
        width: 100%;
        align-items: flex-start;
    }
    .answers-column {
        width: calc(50% - 1.2mm);
        page-break-inside: avoid;
    }
    .column-header {
        display: flex;
        align-items: center;
        border: 1px solid black;
        padding: 0.8mm 0.6mm;
        margin-bottom: 1.2mm;
        font-size: 7.2px;
        font-weight: bold;
    }
    .header-number {
        width: 6mm;
        text-align: center;
        flex-shrink: 0;
        font-size: 8px;
    }
    .header-person {
        width: 18mm;
        text-align: center;
        flex-shrink: 0;
        font-size: 7.8px;
    }
    .header-person-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 0.5mm;
        margin-top: 0.4mm;
        margin-right: 1.5mm;
        font-size: 7.8px;
        font-weight: bold;
    }
    .header-frequency {
        flex: 1;
        text-align: center;
        min-width: 0;
        padding-left: .8mm;
        margin-left: 0.8mm;
    }
    .header-frequency-grid {
        display: grid;
        grid-template-columns: repeat(7, 1fr);
        gap: 0.4mm;
        margin-top: 0.4mm;
        font-size: 10px;
        font-weight: bold;
    }
    .answer-row {
        display: flex;
        align-items: center;
        margin-bottom: 1.2mm;
        min-height: 4.1mm;
    }
    .answer-number {
        width: 6mm;
        text-align: center;
        font-size: 12px;
        font-weight: bold;
        flex-shrink: 0;
    }
    .person-group {
        width: 18mm;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-shrink: 0;
        gap: 0.8mm;
    }
    .frequency-group {
        flex: 1;
        display: grid;
        grid-template-columns: repeat(7, 1fr);
        gap: 0.4mm;
        min-width: 0;
        border-left: 1px solid black;
        padding-left: 1mm;
        margin-left: 0.8mm;
    }
    .answer-bubble {
        width: 3.6mm;
        height: 3.6mm;
        border: 1.2px solid black;
        border-radius: 50%;
        margin: 0 auto;
        flex-shrink: 0;
    }
    .question-44 {
        border: 1px solid black;
        margin-top: 1.5mm;
        padding: 1.2mm;
    }
    .question-44-title {
        font-weight: bold;
        font-size: 12px;
        margin-bottom: 0.8mm;
        text-align: center;
    }
    .question-44-row {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 4mm;
    }
    .question-44-number {
        font-weight: bold;
        font-size: 12px;
        width: 8mm;
        text-align: center;
        flex-shrink: 0;
    }
    .question-44-options {
        display: flex;
        gap: 4mm;
        align-items: center;
    }
    .question-44-option {
        display: flex;
        align-items: center;
        gap: 1mm;
        font-size: 10px;
        font-weight: bold;
    }
</style>

@section('date-row')
    <div style="display: flex; align-items: center; justify-content: space-between; gap: 5mm; width: 100%;">
        <div style="flex-shrink: 0; font-weight: bold; font-size: 12px;">
            TMS-CIS-VPT
        </div>

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
        <div class="folio-header">
            <div class="folio-digit-column"></div>
            @for($i = 0; $i < 11; $i++)
                <div class="folio-position-header">
                    {{ $showPrefilledFolio && isset($folio) && strlen($folio) > $i ? $folio[$i] : '' }}
                </div>
            @endfor
        </div>

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
                <li>Seleccionar una opción por cada bloque de respuesta.</li>
                <li>Contestar con base en su experiencia de los últimos 6 meses.</li>
                <li>Tipo de persona involucrada:</li>
            </ol>
            <div class="person-options-grid">
                <span>A. Jefas</span>
                <span>B. Compañeros</span>
                <span>C. Subordinados</span>
            </div>
            <ol start="6">
                <li>Frecuencia de la situación:</li>
            </ol>
            <div class="frequency-grid">
                <span>0. Nunca</span>
                <span>1. Pocas veces al año o menos</span>
                <span>2. Una vez al mes o menos</span>
                <span>3. Algunas veces al mes</span>
                <span>4. Una vez a la semana</span>
                <span>5. Varias veces a la semana</span>
                <span>6. Todos los días</span>
            </div>
            <ol start="7">
                <li>Importante contestar todas las preguntas.</li>
                <li>Rellenar completamente el círculo.</li>
            </ol>
        </div>
    </div>
</div>

<div class="content-section">
    @php
        $questionNumbers = range(1, 43);
        $firstColumnCount = (int) ceil(count($questionNumbers) / 2);
        $columns = [
            array_slice($questionNumbers, 0, $firstColumnCount),
            array_slice($questionNumbers, $firstColumnCount),
        ];
    @endphp

    <div class="answer-sheet-layout">
        @foreach($columns as $columnIndex => $column)
            <div class="answers-column">
                <div class="column-header">
                    <div class="header-number"></div>
                    <div class="header-person">
                        <div class="header-person-grid">
                            <span>A</span>
                            <span>B</span>
                            <span>C</span>
                        </div>
                    </div>
                    <div class="header-frequency">
                        <div class="header-frequency-grid">
                            <span>0</span>
                            <span>1</span>
                            <span>2</span>
                            <span>3</span>
                            <span>4</span>
                            <span>5</span>
                            <span>6</span>
                        </div>
                    </div>
                </div>

                @foreach($column as $number)
                    <div class="answer-row">
                        <div class="answer-number">{{ $number }}</div>
                        <div class="person-group">
                            <div class="answer-bubble"></div>
                            <div class="answer-bubble"></div>
                            <div class="answer-bubble"></div>
                        </div>
                        <div class="frequency-group">
                            <div class="answer-bubble"></div>
                            <div class="answer-bubble"></div>
                            <div class="answer-bubble"></div>
                            <div class="answer-bubble"></div>
                            <div class="answer-bubble"></div>
                            <div class="answer-bubble"></div>
                            <div class="answer-bubble"></div>
                        </div>
                    </div>
                @endforeach

                @if($columnIndex === 1)
                    <div class="question-44">
                        <div class="question-44-title">PREGUNTA 44 (RESPUESTA SOLO SI / NO)</div>
                        <div class="question-44-row">
                            <div class="question-44-number">44</div>
                            <div class="question-44-options">
                                <div class="question-44-option">
                                    <span>SI</span>
                                    <div class="answer-bubble"></div>
                                </div>
                                <div class="question-44-option">
                                    <span>NO</span>
                                    <div class="answer-bubble"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        @endforeach
    </div>
</div>
@endsection
