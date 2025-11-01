@php
    // Calculate gender distribution from demographic data (Referencia V)
    $totalParticipants = $diagnosticData['total_participants'] ?? 0;
    $genderData = collect($demographicData ?? [])->firstWhere('title', 'Distribución Conforme a Género');
    
    $hombres = 0;
    $mujeres = 0;
    $gne = 0;
    
    if ($genderData && isset($genderData['data'])) {
        foreach ($genderData['data'] as $item) {
            $gender = $item['name'] ?? '';
            $count = $item['total'] ?? 0;
            
            if (stripos($gender, 'Hombre') !== false || stripos($gender, 'Masculino') !== false) {
                $hombres += $count;
            } elseif (stripos($gender, 'Mujer') !== false || stripos($gender, 'Femenino') !== false) {
                $mujeres += $count;
            } else {
                $gne += $count;
            }
        }
    }
    
    // Get puesto distribution from Referencia V
    $puestoData = collect($demographicData ?? [])->firstWhere('title', 'Distribución Conforme al Puesto');
    
    // Get area distribution from Referencia V
    $areaData = collect($demographicData ?? [])->firstWhere('title', 'Distribución Conforme al Área');
    
    // Helper function to get gender counts from risk_levels
    function getGenderCounts($item, $allDemographicData) {
        $hombres = 0;
        $mujeres = 0;
        $gne = 0;
        
        // Get all personal folios from this item
        $personalFolios = [];
        if (isset($item['personal_by_risk'])) {
            foreach ($item['personal_by_risk'] as $level => $folios) {
                $personalFolios = array_merge($personalFolios, $folios);
            }
        }
        
        // Get gender data
        $genderData = collect($allDemographicData ?? [])->firstWhere('title', 'Distribución Conforme a Género');
        if ($genderData && isset($genderData['data'])) {
            foreach ($genderData['data'] as $genderItem) {
                $genderName = $genderItem['name'] ?? '';
                $genderFolios = [];
                if (isset($genderItem['personal_by_risk'])) {
                    foreach ($genderItem['personal_by_risk'] as $level => $folios) {
                        $genderFolios = array_merge($genderFolios, $folios);
                    }
                }
                
                // Count intersection
                $intersection = array_intersect($personalFolios, $genderFolios);
                $count = count($intersection);
                
                if (stripos($genderName, 'Hombre') !== false || stripos($genderName, 'Masculino') !== false) {
                    $hombres += $count;
                } elseif (stripos($genderName, 'Mujer') !== false || stripos($genderName, 'Femenino') !== false) {
                    $mujeres += $count;
                } else {
                    $gne += $count;
                }
            }
        }
        
        return compact('hombres', 'mujeres', 'gne');
    }
@endphp

<div class="section">
    <h2 class="section-title">5.1 PARTICIPANTES</h2>
    
    <div class="section-content">
        <p style="margin-bottom: 12px;">
            La Norma Oficial Mexicana NOM-035-STPS-2018, especifica que el cuestionario (diagnóstico) deberá aplicarse a todos los trabajadores del centro de trabajo, o bien, se podrá aplicar a una muestra representativa de éstos. (Guía de referencia III, Numeral III.1)
        </p>

        <p style="margin-bottom: 12px;">
            Con base a las respuestas asentadas en el cuestionario denominado FDGT Ficha de Datos Generales del trabajador, la muestra representativa se distribuyó de la manera siguiente:
        </p>

        <table style="margin-bottom: 20px;">
            <thead>
                <tr>
                    <th>Total</th>
                    <th>Hombres</th>
                    <th>Mujeres</th>
                    <th>Género No especificado</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong>{{ $totalParticipants }}</strong></td>
                    <td>{{ $hombres }}</td>
                    <td>{{ $mujeres }}</td>
                    <td>{{ $gne }}</td>
                </tr>
            </tbody>
        </table>

        @if($puestoData && !empty($puestoData['data']))
        <h3 style="font-size: 10.5pt; color: #1e40af; margin: 20px 0 10px 0; font-weight: bold;">Distribución por puesto</h3>
        
        <table>
            <thead>
                <tr>
                    <th style="text-align: left; width: 40%;">Nombre</th>
                    <th style="width: 15%;">Total</th>
                    <th style="width: 15%;">Hombres</th>
                    <th style="width: 15%;">Mujeres</th>
                    <th style="width: 15%;">GNE</th>
                </tr>
            </thead>
            <tbody>
                @foreach($puestoData['data'] as $item)
                @php
                    $genderCounts = getGenderCounts($item, $demographicData);
                @endphp
                <tr>
                    <td style="text-align: left;">{{ $item['name'] ?? 'No especificado' }}</td>
                    <td>{{ $item['total'] ?? 0 }}</td>
                    <td>{{ $genderCounts['hombres'] }}</td>
                    <td>{{ $genderCounts['mujeres'] }}</td>
                    <td>{{ $genderCounts['gne'] }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif

        @if($areaData && !empty($areaData['data']))
        <h3 style="font-size: 10.5pt; color: #1e40af; margin: 20px 0 10px 0; font-weight: bold;">Distribución por área</h3>
        
        <table>
            <thead>
                <tr>
                    <th style="text-align: left; width: 40%;">Nombre</th>
                    <th style="width: 15%;">Total</th>
                    <th style="width: 15%;">Hombres</th>
                    <th style="width: 15%;">Mujeres</th>
                    <th style="width: 15%;">GNE</th>
                </tr>
            </thead>
            <tbody>
                @foreach($areaData['data'] as $item)
                @php
                    $genderCounts = getGenderCounts($item, $demographicData);
                @endphp
                <tr>
                    <td style="text-align: left;">{{ $item['name'] ?? 'No especificado' }}</td>
                    <td>{{ $item['total'] ?? 0 }}</td>
                    <td>{{ $genderCounts['hombres'] }}</td>
                    <td>{{ $genderCounts['mujeres'] }}</td>
                    <td>{{ $genderCounts['gne'] }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif
    </div>
</div>

<style>
    .section-content {
        font-size: 9.5pt;
        line-height: 1.6;
        color: #333;
    }

    .section-content p {
        text-align: justify;
    }

    .section-content h3 {
        font-weight: bold;
    }

    .section-content table {
        font-size: 9pt;
    }
</style>
