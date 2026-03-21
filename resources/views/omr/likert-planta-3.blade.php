@extends('omr.layout')

@section('title', 'Clima laboral')

@section('nom-header')
    <div class="header-right-logo">
        <img src="{{ asset('crossover-logo.jpg') }}" alt="Crossover Solutions" />
    </div>
    <div class="header-center">
        @isset($logo)
            <img src="{{ $logo }}" alt="Logo de la organización" class="org-center-logo" />
        @endisset
        <div class="header-center-title">
            <div>ENCUESTA</div>
            <div>CLIMA LABORAL</div>
        </div>
    </div>
@endsection

@section('header-logo')
    <img src="{{ asset('training-logo.jpg') }}" alt="TRAINING & MS" class="org-logo" />
@endsection

@section('date-row')
    {{-- Ocultamos la fila de fecha global: la fecha irá dentro del bloque de instrucciones --}}
@endsection

@section('content')
<style>
    .main-container { 
    display: flex;
    gap: 4mm;
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
    .bubble-small { 
        width: 4mm; 
        height: 4mm; 
        border: 1.5px solid black; 
        border-radius: 50%; 
        flex-shrink: 0; 
    }
    .right-side-container {
        display: flex;
        flex-direction: column;
        gap: 3mm;
        flex: 1;
    }
    .right-columns { display: flex; gap: 3mm; }
    /* Header customizations for three logos */
    .header-right-logo {
        position: absolute;
        top: 0; right: 0; bottom: 0;
        width: 32mm;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 2mm 0;
    }
    .header-right-logo img { max-width: 30mm; max-height: 14mm; object-fit: contain; }
    .header-center {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 1mm;
    }
    .org-center-logo { max-width: 42mm; max-height: 15mm; object-fit: contain; }
    .header-center-title { font-size: 12px; font-weight: bold; line-height: 1.1; text-align: center; }
    .questions-column {
        width: 65mm;
        flex-shrink: 0;
    }
    .demographics-container {
        display: flex;
        gap: 3mm;
        flex: 1;
    }
    .demographics-column {
        flex: 1;
    }
    .instructions { 
        font-size: 12px;
        line-height: 1.3;
        width: 100%;
        position: relative;
    }
    .instructions-row {
        display: flex;
        align-items: flex-start;
        gap: 4mm;
        width: 100%;
    }
    .instructions-text { 
        width: 100%;
    }
    .instructions-date {
        display: flex;
        gap: 3mm;
        align-items: center;
        position: absolute;
        top: 0;
        right: 0;
    }
    .column-header {
        display: flex;
        justify-content: space-around;
        font-weight: bold;
        font-size: 7px;
        border: 1px solid black;
        padding: 1mm 0.5mm;
        margin-bottom: 2mm;
        text-align: left;
    }
    .column-header-label {
        flex: 1;
        text-align: center;
        font-weight: bold;
    }
    .column-header-options {
        display: flex;
        justify-content: space-around;
        flex: 4;
        gap: 7mm;
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
        gap: 1mm;
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
    .demographic-section {
        margin-bottom: 0mm;
        border: none; /* remove outer box border */
        padding: 0;   /* remove extra padding from layout defaults */
    }
    .demographic-title {
        font-weight: bold;
        font-size: 10px; /* match referencia-v section-title */
        margin-bottom: 1mm;
        border-bottom: 1px solid black;
        padding-bottom: 0.5mm;
        background: none; /* remove gray background */
    }
    .demographic-item {
        display: flex;
        align-items: center;
        margin-bottom: 1.2mm;
        font-size: 10px; /* match referencia-v option text */
        min-height: 4mm; /* accommodate larger bubble */
    }
    .demographic-label {
        flex: 1;
        font-size: 10px; /* match referencia-v */
    }
    .demographic-bubble {
        width: 4.5mm; /* match question bubbles */
        height: 4.5mm;
        border: 1.5px solid black;
        border-radius: 50%;
        flex-shrink: 0;
        margin-left: 1.2mm;
    }
    /* Numbered items with bubble on the left */
    .demographic-item-numbered {
        display: flex;
        align-items: center;
        margin-bottom: 1.2mm;
        font-size: 10px;
        min-height: 4mm;
    }
    .demographic-bubble-left {
        width: 4.5mm;
        height: 4.5mm;
        border: 1.5px solid black;
        border-radius: 50%;
        flex-shrink: 0;
        margin-right: 1.2mm;
    }
    .demographic-label-numbered {
        flex: 1;
        font-size: 10px;
    }
    .manual-fields-section {
        margin-top: 5mm;
        padding-top: 3mm;
    }
    .manual-field {
        margin-bottom: 3mm;
    }
    .manual-field-label {
        font-size: 8px;
        font-weight: bold;
        margin-bottom: 1mm;
        display: block;
    }
    .manual-field-line {
        width: 100%;
        border-bottom: 1px solid black;
        height: 4mm;
    }
    .manual-field-box {
        width: 100%;
        height: 10.2mm;
        border: 1px solid black;
    }
    .likert-key { 
        display: grid; 
        grid-template-columns: 1fr 1fr; 
        gap: 1mm 6mm; 
        margin: 1mm 0 1.5mm; 
        font-size: 9px;
        font-weight: bold;
    }
    /* Comments section */
    .comments-section {
        margin-top: 0;
        margin-left: 2mm;
    }
    .comments-title {
        display: flex;
        align-items: center;
        gap: 2mm;
        font-weight: bold;
        font-size: 10px;
        margin-bottom: 2mm;
    }
    .comments-underline {
        border-bottom: 2px solid #2c8aa7; /* subtle blue like the reference */
        width: 100%;
        height: 0;
    }
    .comments-box {
        width: 100%;
        height: 15mm; /* ample space for brief comments */
        border: 1px solid black;
    }
    /* Bottom-right format code label (below alignment markers) */
    .watermark {
        position: absolute;
        right: 22mm;   /* move left to avoid guide markers (bottom-right marker spans ~5-13mm from the right) */
        bottom: 2mm;   /* keep it below the bottom markers at 5mm */
        font-size: 24px; /* larger text as requested */
        font-weight: bold;
        color: #4c9cd2; /* requested blue */
        letter-spacing: 1px;
        z-index: 2;
    }
</style>

@php
    $showPrefilledFolio = $showPrefilledFolio ?? true;
@endphp

<!-- Bottom-right format code label -->
<div class="watermark">ECL-002</div>

<div class="main-container">
    <!-- Left Side: Folio + Questions -->
    <div style="display: flex; flex-direction: column; gap: 3mm;">
        <!-- Folio Section -->
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

        <!-- Questions Column -->
        <div class="questions-column">
            <div class="column-header">
                <div class="column-header-label"></div>
                <div class="column-header-options">
                    <div class="option-label">A</div>
                    <div class="option-label">B</div>
                    <div class="option-label">C</div>
                    <div class="option-label">D</div>
                </div>
            </div>
            
            @for($i = 1; $i <= 23; $i++)
                <div class="question-row-vertical">
                    <div class="question-number-vertical">{{ $i }}</div>
                    <div class="answers-vertical">
                        <div class="bubble-tiny"></div>
                        <div class="bubble-tiny"></div>
                        <div class="bubble-tiny"></div>
                        <div class="bubble-tiny"></div>
                    </div>
                </div>
            @endfor
        </div>
    </div>
    
    <!-- Right side: Instructions + Two demographic columns -->
    <div class="right-side-container">
        <!-- Instructions block -->
        <div class="instructions">
            <div class="instructions-row">
                <div class="instructions-text">
                    <p><strong>Instrucciones:</strong></p>
                    <ul style="margin-left: 4mm; margin-top: 1mm;">
                        <li>Seleccionar una opción</li>
                    </ul>
                    <div class="likert-key">
                        <div>A = Totalmente de acuerdo</div>
                        <div>B = De acuerdo</div>
                        <div>C = Desacuerdo</div>
                        <div>D = Totalmente desacuerdo</div>
                    </div>
                    <ul style="margin-left: 4mm;">
                        <li>Rellenar completamente el círculo.</li>
                        <li>Contestar objetivamente con sinceridad su percepción actual, tomando en cuenta el departamento y actividades que realiza.</li>
                    </ul>
                </div>
                <div class="instructions-date">
                    <div class="date-field">
                        <span class="date-field-label">DÍA</span>
                        <div class="date-field-box"></div>
                    </div>
                    <div class="date-field">
                        <span class="date-field-label">MES</span>
                        <div class="date-field-box"></div>
                    </div>
                    <div class="date-field">
                        <span class="date-field-label">AÑO</span>
                        <div class="date-field-box"></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="right-columns">
            <!-- Column 2: Género + Contratación + Manual Fields + Puestos -->
            <div class="demographics-column">
                <div class="demographic-section">
                    <div class="demographic-title">GÉNERO</div>
                    <div class="demographic-item">
                        <span class="demographic-label">MASCULINO</span>
                        <div class="demographic-bubble"></div>
                    </div>
                    <div class="demographic-item">
                        <span class="demographic-label">FEMENINO</span>
                        <div class="demographic-bubble"></div>
                    </div>
                </div>

                <div class="demographic-section">
                    <div class="demographic-title">CONTRATACIÓN</div>
                    <div class="demographic-item">
                        <span class="demographic-label">SINDICALIZADO</span>
                        <div class="demographic-bubble"></div>
                    </div>
                    <div class="demographic-item">
                        <span class="demographic-label">SALARY</span>
                        <div class="demographic-bubble"></div>
                    </div>
                </div>

                <!-- Puestos Section -->
                <div class="demographic-section" style="margin-top: 1mm;">
                    <div class="demographic-title">PUESTOS</div>
                    @foreach($positions as $index => $position)
                        <div class="demographic-item-numbered">
                            <div class="demographic-bubble-left"></div>
                            <span class="demographic-label-numbered">{{ $index + 1 }}. {{ strtoupper($position['name']) }}</span>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Column 3: Turno + Línea (field) + Manual Fields + Áreas -->
            <div class="demographics-column">
                <div class="demographic-section">
                    <div class="demographic-title">TURNO</div>
                    <div class="demographic-item">
                        <span class="demographic-label">TURNO A: 7:00 a.m. a 4:36 p.m.</span>
                        <div class="demographic-bubble"></div>
                    </div>
                    <div class="demographic-item">
                        <span class="demographic-label">TURNO B: 7:15 p.m. a 3:39 a.m.</span>
                        <div class="demographic-bubble"></div>
                    </div>
                    <div class="demographic-item">
                        <span class="demographic-label">TURNO A CORTE 7:00 a.m. a 3:00 p.m.</span>
                        <div class="demographic-bubble"></div>
                    </div>
                    <div class="demographic-item">
                        <span class="demographic-label">TURNO B CORTE 3:00 p.m. a 10:30 p.m.</span>
                        <div class="demographic-bubble"></div>
                    </div>
                    <div class="demographic-item">
                        <span class="demographic-label">TURNO C CORTE 10:30 p.m. a 6:24 a.m.</span>
                        <div class="demographic-bubble"></div>
                    </div>
                </div>

                <!-- Línea as a dedicated write-in field (rectangle) -->
                <div class="demographic-section" style="margin-top: 1mm;">
                    <div class="demographic-title">LÍNEA</div>
                    <div class="manual-field-box"></div>
                </div>

                <!-- Áreas Section -->
                <div class="demographic-section" style="margin-top:7mm;">
                    <div class="demographic-title">ÁREAS</div>
                    @foreach($areas as $index => $area)
                        <div class="demographic-item-numbered">
                            <div class="demographic-bubble-left"></div>
                            <span class="demographic-label-numbered">{{ $index + 1 }}. {{ strtoupper($area['name']) }}</span>
                        </div>
                    @endforeach
                </div>

                <!-- Manual Fields Section -->
                <div class="demographic-section" style="margin-top: 5mm;">
                    <div class="manual-field" style="margin-bottom: 1.5mm;">
                        <span class="manual-field-label">7. GTE. DE PLANTA</span>
                        <div class="manual-field-line"></div>
                    </div>
                    <div class="manual-field" style="margin-bottom: 1.5mm;">
                        <span class="manual-field-label">8. GTE. DE PRODUCCIÓN</span>
                        <div class="manual-field-line"></div>
                    </div>
                    <div class="manual-field" style="margin-bottom: 1.5mm;">
                        <span class="manual-field-label">9. GTE. DE RH</span>
                        <div class="manual-field-line"></div>
                    </div>
                    <div class="manual-field" style="margin-bottom: 1.5mm;">
                        <span class="manual-field-label">10. SUPERVISOR</span>
                        <div class="manual-field-line"></div>
                    </div>
                </div>
            </div>
        </div> <!-- /right-columns -->
    </div>
</div>

<div class="comments-section">
    <div class="comments-title">
        <span>24. COMENTARIOS ADICIONALES (Breves)</span>
    </div>
    <div class="comments-box"></div>
    </div>
</div>

@endsection
