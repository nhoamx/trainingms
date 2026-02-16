@extends('omr.layout')

@section('title', 'Guía de Referencia V')

@section('guide-title', 'GUÍA DE REFERENCIA V - DATOS DEL TRABAJADOR')

@section('nom-header')
    <h2>NOM-035-STPS-2018</h2>
    <p></p>
@endsection


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
    .instructions { 
        flex: 1; 
        min-width: 0; 
        font-size: 12px;
        line-height: 1.4;
    }
    .instructions h3 {
        font-size: 13px;
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
    .bubble-folio { 
        width: 4mm; 
        height: 4mm; 
        border: 1.5px solid black; 
        border-radius: 50%; 
        flex-shrink: 0; 
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



@section('date-row')
    <div style="display: flex; align-items: center; justify-content: space-between; gap: 5mm; width: 100%;">
        <!-- Izquierda: XYZ -->
        <div style="flex-shrink: 0; font-weight: bold; font-size: 12px;">
            TMS-GR5-DT
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

<div class="folio-instructions-row">
        <div class="folio-section">
            <div class="folio-title">Folio</div>
            <!-- Header con espacios para escribir los dígitos -->
            <div class="folio-header">
                <div class="folio-digit-column"></div>
                @for($i = 0; $i < 11; $i++)
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
                        @for($i = 0; $i < 11; $i++)
                            @php
                                $folioDigit = isset($folio) && strlen($folio) > $i ? $folio[$i] : null;
                                $isSelected = $folioDigit == $digit;
                            @endphp
                            <div class="bubble-folio {{ $isSelected ? 'bubble-filled' : '' }}"></div>
                        @endfor
                    </div>
                </div>
            @endfor
        </div>
        <div class="instructions">
            <h3>INDICACIONES</h3>
            <ol>
                <li>Las siguientes preguntas están relacionadas con sus datos generales.</li>
                <li>Utilizar pluma negra, punta mediana.</li>
                <li>No doblar la hoja.</li>
                <li>Rellenar completamente el círculo.</li>
            </ol>
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
                <div class="coding-subtitle">Rellene la letra correspondiente</div>
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
                <div class="coding-subtitle">Rellene la letra correspondiente</div>
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