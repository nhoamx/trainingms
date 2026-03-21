<?php

return [
    'sexo' => ['Masculino', 'Femenino'],
    // Rango de edad en años
    // Se registra 01, 05, 12, 13, 15 ..., 70
    // El array es asociativo para filtrar valores y generar reportes
    'edad' => [
        '15 - 19',
        '20 - 24',
        '25 - 29',
        '30 - 34',
        '35 - 39',
        '40 - 44',
        '45 - 49',
        '50 - 54',
        '55 - 59',
        '60 - 64',
        '65 - 69',
        '70 o más',
    ],
    'estado_civil' => [
        'Casado',
        'Soltero',
        'Unión libre',
        'Divorciado',
        'Viudo',
    ],
    'nivel_estudios' => [
        'Sin formación',
        'Primaria' => ['Terminada', 'Incompleta'],
        'Secundaria' => ['Terminada', 'Incompleta'],
        'Preparatoria o Bachillerato' => ['Terminada', 'Incompleta'],
        'Técnico Superior' => ['Terminada', 'Incompleta'],
        'Licenciatura' => ['Terminada', 'Incompleta'],
        'Maestría' => ['Terminada', 'Incompleta'],
        'Doctorado' => ['Terminada', 'Incompleta'],
    ],
    'datos_laborales' => [
        'ocupacion_puesto' => '',
        'departamento_seccion_area' => '',
        'tipo_puesto' => [
            'Operativo',
            'Profesional o técnico',
            'Supervisor',
            'Gerente',
        ],
        'tipo_contratacion' => [
            'Por obra o proyecto',
            'Por tiempo determinado (temporal)',
            'Tiempo indeterminado',
            'Honorarios',
        ],
        'tipo_personal' => [
            'Sindicalizado',
            'Confianza',
            'Ninguno',
        ],
        'tipo_jornada' => [
            'Fijo nocturno (entre las 20:00 y 6:00 hrs)',
            'Fijo diurno (entre las 6:00 y 20:00 hrs)',
            'Fijo mixto (combinación de nocturno y diurno)',
        ],
        'rotacion_turnos' => [
            'Sí',
            'No',
        ],
        'experiencia' => [
            'tiempo_puesto_actual' => [
                'Menos de 6 meses',
                'Entre 6 meses y 1 año',
                'Entre 1 a 4 años',
                'Entre 5 a 9 años',
                'Entre 10 a 14 años',
                'Entre 15 a 19 años',
                'Entre 20 a 24 años',
                '25 años o más',
            ],
            'tiempo_experiencia_laboral' => [
                'Menos de 6 meses',
                'Entre 6 meses y 1 año',
                'Entre 1 a 4 años',
                'Entre 5 a 9 años',
                'Entre 10 a 14 años',
                'Entre 15 a 19 años',
                '20 años o más',
            ],
        ],
    ],
];
