@extends('omr.layout')

@section('title', 'Clima laboral')

@section('nom-header')
<h2>CLIMA LABORAL</h2>
@endsection
@isset($logo)
    @section('header-logo')
        <img src="{{ $logo }}" alt="Logo de la organización" class="org-logo">
    @endsection
@endisset

@section('content')
<style>
    .main-container { 
    display: flex;
    gap: 4mm;
    align-items: flex-start;
    }
    .folio-section { 
        border: 2px solid black; 
        padding: 2mm; 
        position: relative; 
        width: 65mm;
        flex-shrink: 0;
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
        font-size: 5px; 
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
    .bubble-small { 
        width: 4.5mm; 
        height: 4.5mm; 
        border: 1.5px solid black; 
        border-radius: 50%; 
        flex-shrink: 0; 
    }
    .right-side-container {
        display: flex;
        gap: 3mm;
        flex: 1;
    }
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
        font-size: 7.5px;
        line-height: 1.3;
        margin-bottom: 3mm;
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
        flex: 4;
        gap: 1mm;
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
        margin-bottom: 3mm;
        border: none; /* remove outer box border */
        padding: 0;   /* remove extra padding from layout defaults */
    }
    .demographic-title {
        font-weight: bold;
        font-size: 10px; /* match referencia-v section-title */
        margin-bottom: 1.5mm;
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
</style>

<div class="main-container">
    <!-- Left Side: Folio + Questions -->
    <div style="display: flex; flex-direction: column; gap: 3mm;">
        <!-- Folio Section -->
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

        <!-- Questions Column -->
        <div class="questions-column">
            <div class="column-header">
                <div class="column-header-label">#</div>
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
    
    <!-- Demographics Section (2 separate columns) -->
    <!-- Column 2: Turno + Manual Fields + Puestos -->
    <div class="demographics-column">
        <div class="demographic-section">
            <div class="demographic-title">TURNO</div>
            <div class="demographic-item">
                <span class="demographic-label">Diurno</span>
                <div class="demographic-bubble"></div>
            </div>
            <div class="demographic-item">
                <span class="demographic-label">Nocturno</span>
                <div class="demographic-bubble"></div>
            </div>
        </div>

        <!-- Manual Fields Section - Column 2 -->
        <div class="manual-fields-section">
            <div class="manual-field">
                <label class="manual-field-label">GERENTE DE PLANTA:</label>
                <div class="manual-field-line"></div>
            </div>
            <div class="manual-field">
                <label class="manual-field-label">GERENTE DE PRODUCCIÓN:</label>
                <div class="manual-field-line"></div>
            </div>
            <div class="manual-field">
                <label class="manual-field-label">GERENTE DE RECURSOS HUMANOS:</label>
                <div class="manual-field-line"></div>
            </div>
        </div>

        <!-- Puestos Section -->
        <div class="demographic-section" style="margin-top: 5mm;">
            <div class="demographic-title">PUESTOS</div>
            @foreach($positions as $position)
                <div class="demographic-item">
                    <span class="demographic-label">{{ $position['name'] }}</span>
                    <div class="demographic-bubble"></div>
                </div>
            @endforeach
        </div>
    </div>

    <!-- Column 3: Tipo de Contratación + Manual Fields + Áreas -->
    <div class="demographics-column">
        <div class="demographic-section">
            <div class="demographic-title">TIPO DE CONTRATACIÓN</div>
            <div class="demographic-item">
                <span class="demographic-label">De confianza</span>
                <div class="demographic-bubble"></div>
            </div>
            <div class="demographic-item">
                <span class="demographic-label">Sindicalizado</span>
                <div class="demographic-bubble"></div>
            </div>
        </div>

        <!-- Manual Fields Section - Column 3 -->
        <div class="manual-fields-section">
            <div class="manual-field">
                <label class="manual-field-label">SUPERVISOR:</label>
                <div class="manual-field-line"></div>
            </div>
            <div class="manual-field">
                <label class="manual-field-label">LÍDER DE EQUIPO:</label>
                <div class="manual-field-line"></div>
            </div>
            <div class="manual-field">
                <label class="manual-field-label">LÍNEA:</label>
                <div class="manual-field-line"></div>
            </div>
        </div>

        <!-- Áreas Section -->
        <div class="demographic-section" style="margin-top: 5mm;">
            <div class="demographic-title">ÁREAS</div>
            @foreach($areas as $area)
                <div class="demographic-item">
                    <span class="demographic-label">{{ $area['name'] }}</span>
                    <div class="demographic-bubble"></div>
                </div>
            @endforeach
        </div>
    </div>
</div>

@endsection
