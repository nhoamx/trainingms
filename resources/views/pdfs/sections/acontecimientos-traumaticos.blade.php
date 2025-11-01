<div class="section">
    <h2 class="section-title">5.2 ACONTECIMIENTOS TRAUMÁTICOS SEVEROS</h2>
    
    <div class="section-content">
        <p style="margin-bottom: 12px;">
            Se realizó la identificación de los trabajadores que han sido sujetos a acontecimientos traumáticos severos durante o con motivo del trabajo, mediante la aplicación del cuestionario CITSATS (Cuestionario para identificar a trabajadores que fueron sujetos a acontecimientos traumáticos severos. Sección 1), de la Guía de Referencia I de la Norma Oficial Mexicana NOM-035-STPS-2018.
        </p>

        <p style="margin-bottom: 15px; font-weight: bold;">
            Total de trabajadores que fueron sujetos a acontecimientos traumáticos severos: <strong style="color: #dc2626;">{{ $traumaticEventsData['total_affected'] ?? 0 }}</strong>
        </p>

        <p style="margin-bottom: 10px;">
            La identificación se obtuvo aplicando las preguntas siguientes:
        </p>

        <p style="margin-bottom: 10px; font-weight: bold;">
            ¿Ha presenciado o sufrido alguna vez, durante o con motivo del trabajo un acontecimiento como los siguientes:
        </p>

        <table>
            <thead>
                <tr>
                    <th style="text-align: left; width: 50%;">Tipo de Acontecimiento</th>
                    <th style="width: 12.5%;">Hombres</th>
                    <th style="width: 12.5%;">Mujeres</th>
                    <th style="width: 12.5%;">GNE</th>
                    <th style="width: 12.5%;">Total</th>
                </tr>
            </thead>
            <tbody>
                @if(!empty($traumaticEventsData['by_event_type']))
                    @php
                        $eventOrder = ['1', '2', '3', '4', '5', '6'];
                    @endphp
                    @foreach($eventOrder as $eventId)
                        @if(isset($traumaticEventsData['by_event_type'][$eventId]))
                            @php
                                $event = $traumaticEventsData['by_event_type'][$eventId];
                            @endphp
                            <tr>
                                <td style="text-align: left;">{{ $event['description'] }}</td>
                                <td>{{ $event['hombres'] }}</td>
                                <td>{{ $event['mujeres'] }}</td>
                                <td>{{ $event['gne'] }}</td>
                                <td><strong>{{ $event['total'] }}</strong></td>
                            </tr>
                        @else
                            <tr>
                                <td style="text-align: left;">
                                    @if($eventId == '1')
                                        Accidente que tenga como consecuencia la muerte, la pérdida de un miembro o una lesión grave?
                                    @elseif($eventId == '2')
                                        Asaltos?
                                    @elseif($eventId == '3')
                                        Actos violentos que derivaron en lesiones graves?
                                    @elseif($eventId == '4')
                                        Secuestro?
                                    @elseif($eventId == '5')
                                        Amenazas?, o
                                    @elseif($eventId == '6')
                                        Cualquier otro que ponga en riesgo su vida o salud, y/o la de otras personas?
                                    @endif
                                </td>
                                <td>0</td>
                                <td>0</td>
                                <td>0</td>
                                <td><strong>0</strong></td>
                            </tr>
                        @endif
                    @endforeach
                @else
                    <tr>
                        <td colspan="5" style="text-align: center; font-style: italic; color: #666;">
                            No se han registrado acontecimientos traumáticos severos
                        </td>
                    </tr>
                @endif
            </tbody>
        </table>
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

    .section-content table {
        font-size: 9pt;
    }
</style>
