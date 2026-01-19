<?php

return [
    'colors' => [
        'nulo' => '#3B82F6', // blue-500 - Azul
        'bajo' => '#10B981', // green-500 - Verde
        'medio' => '#F59E0B', // amber-500 - Amarillo
        'alto' => '#F97316', // orange-500 - Naranja
        'muy_alto' => '#EF4444', // red-500 - Rojo
    ],

    'labels' => [
        'nulo' => 'Nulo',
        'bajo' => 'Bajo',
        'medio' => 'Medio',
        'alto' => 'Alto',
        'muy_alto' => 'Muy Alto',
    ],

    // Rangos de calificación por dominio según NOM-035-STPS-2018
    'domains' => [
        'Ambiente de trabajo' => [
            'max_score' => 20, // 5 preguntas * 4 puntos máximo
            'levels' => [
                'nulo' => ['min' => 0, 'max' => 3],
                'bajo' => ['min' => 4, 'max' => 6],
                'medio' => ['min' => 7, 'max' => 9],
                'alto' => ['min' => 10, 'max' => 12],
                'muy_alto' => ['min' => 13, 'max' => 20],
            ],
        ],
        'Factores propios de la actividad' => [
            'max_score' => 60, // 15 preguntas base + 4 condicionales
            'levels' => [
                'nulo' => ['min' => 0, 'max' => 9],
                'bajo' => ['min' => 10, 'max' => 18],
                'medio' => ['min' => 19, 'max' => 27],
                'alto' => ['min' => 28, 'max' => 36],
                'muy_alto' => ['min' => 37, 'max' => 60],
            ],
        ],
        'Organización del tiempo de trabajo' => [
            'max_score' => 24, // 6 preguntas * 4 puntos máximo
            'levels' => [
                'nulo' => ['min' => 0, 'max' => 3],
                'bajo' => ['min' => 4, 'max' => 7],
                'medio' => ['min' => 8, 'max' => 11],
                'alto' => ['min' => 12, 'max' => 15],
                'muy_alto' => ['min' => 16, 'max' => 24],
            ],
        ],
        'Liderazgo y relaciones en el trabajo' => [
            'max_score' => 76, // 19 preguntas base + 4 condicionales
            'levels' => [
                'nulo' => ['min' => 0, 'max' => 11],
                'bajo' => ['min' => 12, 'max' => 23],
                'medio' => ['min' => 24, 'max' => 35],
                'alto' => ['min' => 36, 'max' => 47],
                'muy_alto' => ['min' => 48, 'max' => 76],
            ],
        ],
        'Entorno organizacional' => [
            'max_score' => 32, // 8 preguntas * 4 puntos máximo
            'levels' => [
                'nulo' => ['min' => 0, 'max' => 4],
                'bajo' => ['min' => 5, 'max' => 10],
                'medio' => ['min' => 11, 'max' => 15],
                'alto' => ['min' => 16, 'max' => 21],
                'muy_alto' => ['min' => 22, 'max' => 32],
            ],
        ],
    ],

    // Calificación final global
    'global' => [
        'max_score' => 288, // Suma de todos los dominios
        'levels' => [
            'nulo' => ['min' => 0, 'max' => 49],
            'bajo' => ['min' => 50, 'max' => 74],
            'medio' => ['min' => 75, 'max' => 98],
            'alto' => ['min' => 99, 'max' => 139],
            'muy_alto' => ['min' => 140, 'max' => 288],
        ],
    ],
];
