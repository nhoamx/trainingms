@extends('omr.layout')

@section('title', 'Guía de Referencia V')

@section('guide-title', 'GUÍA DE REFERENCIA V - DATOS GENERALES DEL TRABAJADOR Y DEL CENTRO DE TRABAJO')

@section('content')
<style>
    .folio-instructions-row { 
        display: flex; 
        gap: 4mm; 
        margin-bottom: 2.5mm; 
        align-items: flex-start; 
    }
    .folio-section { 
        border: 2px solid black; 
        padding: 2mm; 
        position: relative; 
        min-width: 50mm; 
        max-width: 65mm; 
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
        font-size: 7px;
        line-height: 1.3;
    }
    
    .folio-instructions-row {
        margin-bottom: 2.5mm;
        padding-bottom: 2mm;
        border-bottom: 1.5px solid black;
    }
    
    .three-column-layout { 
        display: flex; 
        gap: 2.5mm; 
        margin-top: 2.5mm; 
    }
    .column { 
        width: 32%; 
        page-break-inside: avoid; 
    }
    .section-title { 
        font-weight: bold; 
        font-size: 10px; 
        margin-bottom: 1.5mm; 
        margin-top: 0mm;
        text-align: center; 
        padding: 0.8mm; 
        border: 1px solid black; 
        line-height: 1.2;
    }
    .demographic-row { 
        display: flex; 
        align-items: center; 
        margin-bottom: 0.8mm; 
        min-height: 3mm; 
        font-size: 6.5px; 
    }
    .option-label { 
        font-weight: bold; 
        width: 5mm; 
        text-align: center; 
        flex-shrink: 0; 
        font-size: 10px; 
        margin-right: 0.8mm; 
    }
    .option-text { 
        flex: 1; 
        font-size: 10px; 
        margin-right: 0.8mm; 
        margin-left: 0.8mm;
        line-height: 1.2;
    }
    .bubble-small { 
        width: 4.5mm; 
        height: 4.5mm; 
        border: 1.5px solid black; 
        border-radius: 50%; 
        flex-shrink: 0; 
    }
    
    .age-section { 
        display: flex; 
        gap: 1.5mm; 
        margin-bottom: 1.5mm; 
    }
    .age-column { flex: 1; }
    .age-header { 
        font-weight: bold; 
        text-align: center; 
        font-size: 6px; 
        margin-bottom: 0.8mm; 
        border: 1px solid black; 
        padding: 0.5mm; 
    }
    .age-row { 
        display: flex; 
        align-items: center; 
        margin-bottom: 0.6mm; 
    }
    .age-number { 
        width: 4mm; 
        text-align: center; 
        font-size: 10px; 
        font-weight: bold; 
    }
    .age-bubbles { 
        display: flex; 
        gap: 0.5mm; 
        margin-left: 0.8mm; 
    }
    
    .studies-section { 
        margin-bottom: 1.5mm; 
    }
    .studies-header { 
        display: flex; 
        gap: 0.8mm; 
        margin-bottom: 0.8mm; 
    }
    .studies-label { 
        flex: 2; 
        font-size: 6.5px; 
    }
    .studies-col-header { 
        flex: 1; 
        text-align: center; 
        font-size: 9px; 
        font-weight: bold; 
        border: 1px solid black; 
        padding: 0.4mm; 
    }
    .studies-row { 
        display: flex; 
        align-items: center; 
        margin-bottom: 0.8mm; 
        gap: 0.8mm; 
    }
    .studies-text { 
        flex: 2; 
        font-size: 10px; 
        line-height: 1.2;
    }
    .studies-option { 
        flex: 1; 
        display: flex; 
        justify-content: center; 
    }
    
    .coding-section { 
        margin-bottom: 1.5mm; 
    }
    .coding-title { 
        font-weight: bold; 
        font-size: 10px; 
        margin-bottom: 0.8mm; 
    }
    .coding-subtitle { 
        font-size: 10px; 
        margin-bottom: 0.8mm; 
        font-style: italic; 
    }
    .coding-grid { 
        display: grid; 
        grid-template-columns: 5mm repeat(5, 1fr); 
        gap: 0.8mm; 
        margin-bottom: 0.8mm; 
    }
    .coding-header { 
        text-align: center; 
        font-size: 10px; 
        font-weight: bold; 
        border: 1px solid black; 
        padding: 0.4mm; 
    }
    .coding-row-label { 
        text-align: center; 
        font-weight: bold; 
        font-size: 10px; 
        border: 1px solid black; 
        padding: 0.4mm; 
    }
    .coding-cell { 
        display: flex; 
        justify-content: center; 
        align-items: center; 
    }
    
    /* Hybrid QR Code styles */
    .hybrid-qr-container {
        position: absolute;
        top: 70mm;
        right: 8mm;
        width: 25mm;
        text-align: center;
    }
    .hybrid-qr-code {
        width: 20mm;
        height: 20mm;
        margin: 0 auto 2mm;
        border: 1px solid #000;
    }
    .hybrid-qr-text {
        font-size: 7px;
        font-weight: bold;
        line-height: 1.2;
        color: #000;
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
        <div class="instructions">
            <h3 style="font-weight: bold; margin-bottom: 1.5mm; font-size: 10px;">INSTRUCCIONES:</h3>
            <p style="font-size: 10px; margin-bottom: 0.8mm;">• Las siguientes preguntas están relacionadas con sus datos generales, características sociodemográficas y las del centro de trabajo.</p>
            <p style="font-size: 10px; margin-bottom: 0.8mm;">• Para responder marque completamente con tinta azul o negra el círculo de la opción que corresponda a su situación.</p>
            <p style="font-size: 10px;">• Es importante que conteste todas las preguntas.</p>
        </div>
    </div>

    @if(isset($isHybrid) && $isHybrid)
    <!-- QR Code for Hybrid Evaluation -->
    <div class="hybrid-qr-container">
        <div class="hybrid-qr-code">
            {!! \SimpleSoftwareIO\QrCode\Facades\QrCode::size(75)->generate(url('/h/' . $folio)) !!}
        </div>
        <div class="hybrid-qr-text">
            Escanea para<br>completar<br>evaluación
        </div>
    </div>
    @endif

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