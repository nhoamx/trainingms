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
        font-size: 11px; /* closer to question header size */
        margin-bottom: 1.5mm;
        border-bottom: 1px solid black;
        padding-bottom: 0.5mm;
        background: none; /* remove gray background */
    }
    .demographic-item {
        display: flex;
        align-items: center;
        margin-bottom: 1.2mm;
        font-size: 9px; /* slightly larger for readability */
        min-height: 4mm; /* accommodate larger bubble */
    }
    .demographic-label {
        flex: 1;
        font-size: 9px;
    }
    .demographic-bubble {
        width: 4.5mm; /* match question bubbles */
        height: 4.5mm;
        border: 1.5px solid black;
        border-radius: 50%;
        flex-shrink: 0;
        margin-left: 1.2mm;
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
    
    <!-- Demographics Columns (Center + Right) -->
    <div class="demographics-container">
        <!-- Center Column -->
        <div class="demographics-column">
            <!-- Género -->
            <div class="demographic-section">
                <div class="demographic-title">GÉNERO</div>
                <div class="demographic-item">
                    <span class="demographic-label">Masculino</span>
                    <div class="demographic-bubble"></div>
                </div>
                <div class="demographic-item">
                    <span class="demographic-label">Femenino</span>
                    <div class="demographic-bubble"></div>
                </div>
            </div>

            <!-- Turno -->
            <div class="demographic-section">
                <div class="demographic-title">TURNO</div>
                <div class="demographic-item">
                    <span class="demographic-label">1° Turno</span>
                    <div class="demographic-bubble"></div>
                </div>
                <div class="demographic-item">
                    <span class="demographic-label">2° Turno</span>
                    <div class="demographic-bubble"></div>
                </div>
                <div class="demographic-item">
                    <span class="demographic-label">3° Turno</span>
                    <div class="demographic-bubble"></div>
                </div>
            </div>

            <!-- Tipo de Contratación -->
            <div class="demographic-section">
                <div class="demographic-title">TIPO DE CONTRATACIÓN</div>
                <div class="demographic-item">
                    <span class="demographic-label">Salary</span>
                    <div class="demographic-bubble"></div>
                </div>
                <div class="demographic-item">
                    <span class="demographic-label">Hourly</span>
                    <div class="demographic-bubble"></div>
                </div>
            </div>

            <!-- Departamento (19 opciones) -->
            <div class="demographic-section">
                <div class="demographic-title">DEPARTAMENTO</div>
                @for($i = 1; $i <= 19; $i++)
                    <div class="demographic-item">
                        <span class="demographic-label">Dept {{ $i }}</span>
                        <div class="demographic-bubble"></div>
                    </div>
                @endfor
            </div>
        </div>

        <!-- Right Column -->
        <div class="demographics-column">
            <!-- Línea (17 opciones) -->
            <div class="demographic-section">
                <div class="demographic-title">LÍNEA</div>
                @for($i = 1; $i <= 17; $i++)
                    <div class="demographic-item">
                        <span class="demographic-label">Línea {{ $i }}</span>
                        <div class="demographic-bubble"></div>
                    </div>
                @endfor
            </div>

            <!-- Puesto (13 opciones) -->
            <div class="demographic-section">
                <div class="demographic-title">PUESTO</div>
                @for($i = 1; $i <= 13; $i++)
                    <div class="demographic-item">
                        <span class="demographic-label">Puesto {{ $i }}</span>
                        <div class="demographic-bubble"></div>
                    </div>
                @endfor
            </div>

            <!-- Supervisor (5 opciones) -->
            <div class="demographic-section">
                <div class="demographic-title">SUPERVISOR</div>
                @for($i = 1; $i <= 5; $i++)
                    <div class="demographic-item">
                        <span class="demographic-label">Supervisor {{ $i }}</span>
                        <div class="demographic-bubble"></div>
                    </div>
                @endfor
            </div>
        </div>
    </div>
</div>

@endsection
