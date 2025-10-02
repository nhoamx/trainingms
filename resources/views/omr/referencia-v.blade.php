@extends('omr.layout')

@section('title', 'Guía de Referencia V')

@section('guide-title', 'GUÍA DE REFERENCIA V - DATOS GENERALES DEL TRABAJADOR Y DEL CENTRO DE TRABAJO')

@section('content')
<style>
    .page-container { position: relative; margin: 0 auto; padding: 8mm; background: white; font-size: 6px; }
    .folio-instructions-row { display: flex; gap: 6mm; margin-bottom: 4mm; align-items: flex-start; }
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
    .instructions { flex: 2; min-width: 0; font-size: 7px; }
    
    .folio-instructions-row {
        margin-bottom: 4mm;
        padding-bottom: 3mm;
        border-bottom: 2px solid black;
    }
    
    .three-column-layout { display: flex; gap: 4mm; margin-top: 4mm; }
    .column { width: 32%; page-break-inside: avoid; }
    .section-title { font-weight: bold; font-size: 7px; margin-bottom: 2mm; text-align: center; background: #f0f0f0; padding: 1mm; border: 1px solid black; }
    .demographic-row { display: flex; align-items: center; margin-bottom: 1mm; min-height: 3.5mm; font-size: 6px; }
    .option-label { font-weight: bold; width: 6mm; text-align: center; flex-shrink: 0; font-size: 6px; margin-right: 1mm; }
    .option-text { flex: 1; font-size: 6px; margin-right: 1mm; }
    .bubble-small { width: 2.5mm; height: 2.5mm; border: 1px solid black; border-radius: 50%; flex-shrink: 0; }
    
    .age-section { display: flex; gap: 2mm; margin-bottom: 2mm; }
    .age-column {  }
    .age-header { font-weight: bold; text-align: center; font-size: 5px; margin-bottom: 1mm; border: 1px solid black; padding: 0.5mm; background: #f0f0f0; }
    .age-row { display: flex; align-items: center; margin-bottom: 0.8mm; }
    .age-number { width: 4mm; text-align: center; font-size: 5px; font-weight: bold; }
    .age-bubbles { display: flex; gap: 0.5mm; margin-left: 1mm; }
    
    .studies-section { margin-bottom: 2mm; }
    .studies-header { display: flex; gap: 1mm; margin-bottom: 1mm; }
    .studies-label { flex: 2; font-size: 6px; }
    .studies-col-header { flex: 1; text-align: center; font-size: 5px; font-weight: bold; border: 1px solid black; padding: 0.5mm; background: #f0f0f0; }
    .studies-row { display: flex; align-items: center; margin-bottom: 1mm; gap: 1mm; }
    .studies-text { flex: 2; font-size: 6px; }
    .studies-option { flex: 1; display: flex; justify-content: center; }
    
    .coding-section { margin-bottom: 2mm; }
    .coding-title { font-weight: bold; font-size: 6px; margin-bottom: 1mm; }
    .coding-subtitle { font-size: 5px; margin-bottom: 1mm; font-style: italic; }
    .coding-grid { display: grid; grid-template-columns: 6mm repeat(5, 1fr); gap: 1mm; margin-bottom: 1mm; }
    .coding-header { text-align: center; font-size: 5px; font-weight: bold; border: 1px solid black; padding: 0.5mm; background: #f0f0f0; }
    .coding-row-label { text-align: center; font-weight: bold; font-size: 5px; border: 1px solid black; padding: 0.5mm; background: #f8f8f8; }
    .coding-cell { display: flex; justify-content: center; align-items: center; }
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
            <h3 style="font-weight: bold; margin-bottom: 2mm; font-size: 8px;">INSTRUCCIONES:</h3>
            <p style="font-size: 7px;">• Las siguientes preguntas están relacionadas con sus datos generales, características sociodemográficas y las del centro de trabajo.</p>
            <p style="font-size: 7px;">• Para responder marque completamente con tinta azul o negra el círculo de la opción que corresponda a su situación.</p>
            <p style="font-size: 7px;">• Es importante que conteste todas las preguntas.</p>
        </div>
    </div>

    <div class="three-column-layout">
        <!-- COLUMNA 1 -->
        <div class="column">
            <!-- Sexo/Género -->
            <div class="section-title">SEXO/GÉNERO</div>
            @foreach($config['sexo'] as $option)
                <div class="demographic-row">
                    <div class="bubble-small"></div>
                    <div class="option-text">{{ $option }}</div>
                </div>
            @endforeach

            <!-- Edad -->
            <div class="section-title">EDAD</div>
            <div class="age-section">
                <div class="age-column">
                    @for($i = 0; $i <= 9; $i++)
                        <div class="age-row">
                            <div class="age-number">{{ $i }}</div>
                            <div class="age-bubbles">
                                <div class="bubble-small"></div>
                            </div>
                        </div>
                    @endfor
                </div>
                <div class="age-column">
                    @for($i = 0; $i <= 9; $i++)
                        <div class="age-row">
                            <div class="age-number">{{ $i }}</div>
                            <div class="age-bubbles">
                                <div class="bubble-small"></div>
                            </div>
                        </div>
                    @endfor
                </div>
            </div>

            <!-- Estado Civil -->
            <div class="section-title">ESTADO CIVIL</div>
            @foreach($config['estado_civil'] as $option)
                <div class="demographic-row">
                    <div class="bubble-small"></div>
                    <div class="option-text">{{ $option }}</div>
                </div>
            @endforeach

            <!-- Tipo de Personal -->
            <div class="section-title">TIPO DE PERSONAL</div>
            @foreach($config['datos_laborales']['tipo_personal'] as $option)
                <div class="demographic-row">
                    <div class="bubble-small"></div>
                    <div class="option-text">{{ $option }}</div>
                </div>
            @endforeach
        </div>

        <!-- COLUMNA 2 -->
        <div class="column">
            <!-- Último Nivel de Estudios -->
            <div class="section-title">ÚLTIMO NIVEL DE ESTUDIOS</div>
            <div class="studies-section">
                <div class="studies-header">
                    <div class="studies-label"></div>
                    <div class="studies-col-header">Terminada</div>
                    <div class="studies-col-header">Incompleta</div>
                </div>
                @foreach($config['nivel_estudios'] as $key => $value)
                    @if(is_array($value))
                        <div class="studies-row">
                            <div class="studies-text">{{ $key }}</div>
                            <div class="studies-option"><div class="bubble-small"></div></div>
                            <div class="studies-option"><div class="bubble-small"></div></div>
                        </div>
                    @else
                        <div class="studies-row">
                            <div class="studies-text">{{ $value }}</div>
                            <div class="studies-option"><div class="bubble-small"></div></div>
                            <div class="studies-option"></div>
                        </div>
                    @endif
                @endforeach
            </div>

            <!-- Tipo de Puesto -->
            <div class="section-title">TIPO DE PUESTO</div>
            @foreach($config['datos_laborales']['tipo_puesto'] as $option)
                <div class="demographic-row">
                    <div class="bubble-small"></div>
                    <div class="option-text">{{ $option }}</div>
                </div>
            @endforeach

            <!-- Tipo de Contratación -->
            <div class="section-title">TIPO DE CONTRATACIÓN</div>
            @foreach($config['datos_laborales']['tipo_contratacion'] as $option)
                <div class="demographic-row">
                    <div class="bubble-small"></div>
                    <div class="option-text">{{ $option }}</div>
                </div>
            @endforeach

            <!-- Tipo de Jornada -->
            <div class="section-title">TIPO DE JORNADA</div>
            @foreach($config['datos_laborales']['tipo_jornada'] as $option)
                <div class="demographic-row">
                    <div class="bubble-small"></div>
                    <div class="option-text">{{ $option }}</div>
                </div>
            @endforeach
        </div>

        <!-- COLUMNA 3 -->
        <div class="column">
            <!-- Realiza Rotación de Turnos -->
            <div class="section-title">REALIZA ROTACIÓN DE TURNOS</div>
            @foreach($config['datos_laborales']['rotacion_turnos'] as $option)
                <div class="demographic-row">
                    <div class="bubble-small"></div>
                    <div class="option-text">{{ $option }}</div>
                </div>
            @endforeach

            <!-- Tiempo en el Puesto Actual -->
            <div class="section-title">TIEMPO EN EL PUESTO ACTUAL</div>
            @foreach($config['datos_laborales']['experiencia']['tiempo_puesto_actual'] as $option)
                <div class="demographic-row">
                    <div class="bubble-small"></div>
                    <div class="option-text">{{ $option }}</div>
                </div>
            @endforeach

            <!-- Experiencia Vida Laboral -->
            <div class="section-title">EXPERIENCIA VIDA LABORAL</div>
            @foreach($config['datos_laborales']['experiencia']['tiempo_experiencia_laboral'] as $option)
                <div class="demographic-row">
                    <div class="bubble-small"></div>
                    <div class="option-text">{{ $option }}</div>
                </div>
            @endforeach

            <!-- Ocupación/Profesión/Puesto -->
            <div class="coding-section" style="margin-top: 3mm;">
                <div class="coding-title">OCUPACIÓN/PROFESIÓN/PUESTO</div>
                <div class="coding-subtitle">Marque la letra correspondiente</div>
                <div class="coding-grid">
                    <div class="coding-header"></div>
                    <div class="coding-header">A</div>
                    <div class="coding-header">B</div>
                    <div class="coding-header">C</div>
                    <div class="coding-header">D</div>
                    <div class="coding-header">E</div>
                    <div class="coding-row-label">1</div>
                    @for($i = 0; $i < 5; $i++)
                        <div class="coding-cell"><div class="bubble-small"></div></div>
                    @endfor
                    <div class="coding-row-label">2</div>
                    @for($i = 0; $i < 5; $i++)
                        <div class="coding-cell"><div class="bubble-small"></div></div>
                    @endfor
                </div>
            </div>

            <!-- Departamento/Sección/Área -->
            <div class="coding-section" style="margin-top: 2mm;">
                <div class="coding-title">DEPARTAMENTO/SECCIÓN/ÁREA</div>
                <div class="coding-subtitle">Marque la letra correspondiente</div>
                <div class="coding-grid">
                    <div class="coding-header"></div>
                    <div class="coding-header">A</div>
                    <div class="coding-header">B</div>
                    <div class="coding-header">C</div>
                    <div class="coding-header">D</div>
                    <div class="coding-header">E</div>
                    <div class="coding-row-label">1</div>
                    @for($i = 0; $i < 5; $i++)
                        <div class="coding-cell"><div class="bubble-small"></div></div>
                    @endfor
                    <div class="coding-row-label">2</div>
                    @for($i = 0; $i < 5; $i++)
                        <div class="coding-cell"><div class="bubble-small"></div></div>
                    @endfor
                </div>
            </div>
        </div>
    </div>
</div>
    </div>
</div>


@endsection