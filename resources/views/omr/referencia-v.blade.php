@extends('omr.layout')

@section('title', 'Guía de Referencia V')

@section('guide-title', 'GUÍA DE REFERENCIA V - DATOS GENERALES DEL TRABAJADOR Y DEL CENTRO DE TRABAJO')

@section('content')
<div class="instructions">
    <h3 style="font-weight: bold; margin-bottom: 3mm;">INSTRUCCIONES:</h3>
    <p>• Las siguientes preguntas están relacionadas con sus datos generales, características sociodemográficas y las del centro de trabajo.</p>
    <p>• Para responder marque completamente con tinta azul o negra el círculo de la opción que corresponda a su situación.</p>
    <p>• Es importante que conteste todas las preguntas.</p>
</div>

<!-- Sexo -->
<div class="demographic-section">
    <div class="demographic-title">SEXO</div>
    <div class="demographic-options">
        @foreach($config['sexo'] as $option)
            <div class="demographic-option">
                <div class="bubble"></div>
                <span style="margin-left: 2mm; font-size: 10px;">{{ $option }}</span>
            </div>
        @endforeach
    </div>
</div>

<!-- Edad -->
<div class="demographic-section">
    <div class="demographic-title">EDAD</div>
    <div class="demographic-options">
        @foreach($config['edad'] as $option)
            <div class="demographic-option">
                <div class="bubble"></div>
                <span style="margin-left: 2mm; font-size: 10px;">{{ $option }} años</span>
            </div>
        @endforeach
    </div>
</div>

<!-- Estado Civil -->
<div class="demographic-section">
    <div class="demographic-title">ESTADO CIVIL</div>
    <div class="demographic-options">
        @foreach($config['estado_civil'] as $option)
            <div class="demographic-option">
                <div class="bubble"></div>
                <span style="margin-left: 2mm; font-size: 10px;">{{ $option }}</span>
            </div>
        @endforeach
    </div>
</div>

<!-- Nivel de Estudios -->
<div class="demographic-section">
    <div class="demographic-title">NIVEL DE ESTUDIOS</div>
    <div class="demographic-options">
        @foreach($config['nivel_estudios'] as $key => $value)
            @if(is_array($value))
                @foreach($value as $subOption)
                    <div class="demographic-option">
                        <div class="bubble"></div>
                        <span style="margin-left: 2mm; font-size: 10px;">{{ $key }} - {{ $subOption }}</span>
                    </div>
                @endforeach
            @else
                <div class="demographic-option">
                    <div class="bubble"></div>
                    <span style="margin-left: 2mm; font-size: 10px;">{{ $value }}</span>
                </div>
            @endif
        @endforeach
    </div>
</div>

<!-- Datos Laborales -->
<div style="page-break-before: always;"></div>
<div class="section-title">DATOS LABORALES</div>

@php
$laboralData = $config['datos_laborales'] ?? [];
@endphp

@if(isset($laboralData['ocupacion_puesto']))
<div class="demographic-section">
    <div class="demographic-title">OCUPACIÓN/PUESTO</div>
    <div class="demographic-options">
        @foreach($laboralData['ocupacion_puesto'] as $option)
            <div class="demographic-option">
                <div class="bubble"></div>
                <span style="margin-left: 2mm; font-size: 9px;">{{ $option }}</span>
            </div>
        @endforeach
    </div>
</div>
@endif

@if(isset($laboralData['tipo_puesto']))
<div class="demographic-section">
    <div class="demographic-title">TIPO DE PUESTO</div>
    <div class="demographic-options">
        @foreach($laboralData['tipo_puesto'] as $option)
            <div class="demographic-option">
                <div class="bubble"></div>
                <span style="margin-left: 2mm; font-size: 10px;">{{ $option }}</span>
            </div>
        @endforeach
    </div>
</div>
@endif

@if(isset($laboralData['tipo_contratacion']))
<div class="demographic-section">
    <div class="demographic-title">TIPO DE CONTRATACIÓN</div>
    <div class="demographic-options">
        @foreach($laboralData['tipo_contratacion'] as $option)
            <div class="demographic-option">
                <div class="bubble"></div>
                <span style="margin-left: 2mm; font-size: 10px;">{{ $option }}</span>
            </div>
        @endforeach
    </div>
</div>
@endif

@if(isset($laboralData['tipo_jornada']))
<div class="demographic-section">
    <div class="demographic-title">TIPO DE JORNADA</div>
    <div class="demographic-options">
        @foreach($laboralData['tipo_jornada'] as $option)
            <div class="demographic-option">
                <div class="bubble"></div>
                <span style="margin-left: 2mm; font-size: 10px;">{{ $option }}</span>
            </div>
        @endforeach
    </div>
</div>
@endif

@if(isset($laboralData['rotacion_turnos']))
<div class="demographic-section">
    <div class="demographic-title">ROTACIÓN DE TURNOS</div>
    <div class="demographic-options">
        @foreach($laboralData['rotacion_turnos'] as $option)
            <div class="demographic-option">
                <div class="bubble"></div>
                <span style="margin-left: 2mm; font-size: 10px;">{{ $option }}</span>
            </div>
        @endforeach
    </div>
</div>
@endif

@if(isset($laboralData['experiencia_vida_laboral']))
<div class="demographic-section">
    <div class="demographic-title">EXPERIENCIA EN LA VIDA LABORAL</div>
    <div class="demographic-options">
        @foreach($laboralData['experiencia_vida_laboral'] as $option)
            <div class="demographic-option">
                <div class="bubble"></div>
                <span style="margin-left: 2mm; font-size: 10px;">{{ $option }}</span>
            </div>
        @endforeach
    </div>
</div>
@endif

<!-- Espacio para información adicional -->
<div style="margin-top: 10mm; border: 1px solid black; padding: 3mm; background: #f8f8f8;">
    <h4 style="font-weight: bold; margin-bottom: 3mm;">INFORMACIÓN ADICIONAL DEL CENTRO DE TRABAJO:</h4>
    
    <div style="margin-bottom: 3mm;">
        <span style="font-weight: bold; font-size: 10px;">Nombre de la organización:</span>
        <div style="border-bottom: 1px solid black; margin-top: 1mm; height: 4mm;"></div>
    </div>
    
    <div style="margin-bottom: 3mm;">
        <span style="font-weight: bold; font-size: 10px;">Departamento/Área/Sección:</span>
        <div style="border-bottom: 1px solid black; margin-top: 1mm; height: 4mm;"></div>
    </div>
    
    <div style="margin-bottom: 3mm;">
        <span style="font-weight: bold; font-size: 10px;">Número de trabajadores en el centro de trabajo:</span>
        <div style="border-bottom: 1px solid black; margin-top: 1mm; height: 4mm;"></div>
    </div>
</div>

<!-- Área de verificación -->
<div style="position: absolute; bottom: 20mm; right: 10mm; border: 2px solid black; padding: 3mm; background: #f8f8f8;">
    <h4 style="font-weight: bold; margin-bottom: 2mm; text-align: center; font-size: 10px;">VERIFICACIÓN</h4>
    <div style="text-align: center;">
        <span style="font-size: 8px;">DATOS COMPLETOS:</span>
        <div style="display: flex; gap: 3mm; justify-content: center; margin-top: 1mm;">
            <div class="option-group">
                <span class="option-label">SÍ</span>
                <div class="bubble"></div>
            </div>
            <div class="option-group">
                <span class="option-label">NO</span>
                <div class="bubble"></div>
            </div>
        </div>
    </div>
</div>
@endsection