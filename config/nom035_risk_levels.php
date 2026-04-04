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

    // Rangos de calificación por categoría según NOM-035-STPS-2018
    'categories' => [
        'Ambiente de trabajo' => [
            'levels' => [
                'nulo' => ['min' => 0, 'max' => 4],
                'bajo' => ['min' => 5, 'max' => 8],
                'medio' => ['min' => 9, 'max' => 10],
                'alto' => ['min' => 11, 'max' => 13],
                'muy_alto' => ['min' => 14, 'max' => 999],
            ],
        ],
        'Factores propios de la actividad' => [
            'levels' => [
                'nulo' => ['min' => 0, 'max' => 14],
                'bajo' => ['min' => 15, 'max' => 29],
                'medio' => ['min' => 30, 'max' => 44],
                'alto' => ['min' => 45, 'max' => 59],
                'muy_alto' => ['min' => 60, 'max' => 999],
            ],
        ],
        'Organización del tiempo de trabajo' => [
            'levels' => [
                'nulo' => ['min' => 0, 'max' => 4],
                'bajo' => ['min' => 5, 'max' => 6],
                'medio' => ['min' => 7, 'max' => 9],
                'alto' => ['min' => 10, 'max' => 12],
                'muy_alto' => ['min' => 13, 'max' => 999],
            ],
        ],
        'Liderazgo y relaciones en el trabajo' => [
            'levels' => [
                'nulo' => ['min' => 0, 'max' => 13],
                'bajo' => ['min' => 14, 'max' => 28],
                'medio' => ['min' => 29, 'max' => 41],
                'alto' => ['min' => 42, 'max' => 57],
                'muy_alto' => ['min' => 58, 'max' => 999],
            ],
        ],
        'Entorno organizacional' => [
            'levels' => [
                'nulo' => ['min' => 0, 'max' => 9],
                'bajo' => ['min' => 10, 'max' => 13],
                'medio' => ['min' => 14, 'max' => 17],
                'alto' => ['min' => 18, 'max' => 22],
                'muy_alto' => ['min' => 23, 'max' => 999],
            ],
        ],
    ],

    // Rangos de calificación por dominio según NOM-035-STPS-2018
    'domains' => [
        'Condiciones en el ambiente de trabajo' => [
            'category' => 'Ambiente de trabajo',
            'max_score' => 20, // 5 preguntas × 4 puntos
            'levels' => [
                'nulo' => ['min' => 0, 'max' => 4],
                'bajo' => ['min' => 5, 'max' => 8],
                'medio' => ['min' => 9, 'max' => 10],
                'alto' => ['min' => 11, 'max' => 13],
                'muy_alto' => ['min' => 14, 'max' => 999],
            ],
        ],
        'Carga de trabajo' => [
            'category' => 'Factores propios de la actividad',
            'max_score' => 60, // 15 preguntas × 4 puntos (6,7,8,9,10,11,12,13,14,15,16,65,66,67,68)
            'levels' => [
                'nulo' => ['min' => 0, 'max' => 14],
                'bajo' => ['min' => 15, 'max' => 20],
                'medio' => ['min' => 21, 'max' => 26],
                'alto' => ['min' => 27, 'max' => 36],
                'muy_alto' => ['min' => 37, 'max' => 999],
            ],
        ],
        'Falta de control sobre el trabajo' => [
            'category' => 'Factores propios de la actividad',
            'max_score' => 40, // 10 preguntas × 4 puntos (23,24,25,26,27,28,29,30,35,36)
            'levels' => [
                'nulo' => ['min' => 0, 'max' => 10],
                'bajo' => ['min' => 11, 'max' => 15],
                'medio' => ['min' => 16, 'max' => 20],
                'alto' => ['min' => 21, 'max' => 24],
                'muy_alto' => ['min' => 25, 'max' => 999],
            ],
        ],
        'Jornada de trabajo' => [
            'category' => 'Organización del tiempo de trabajo',
            'max_score' => 8, // 2 preguntas × 4 puntos (17,18)
            'levels' => [
                'nulo' => ['min' => 0, 'max' => 0],
                'bajo' => ['min' => 1, 'max' => 1],
                'medio' => ['min' => 2, 'max' => 3],
                'alto' => ['min' => 4, 'max' => 5],
                'muy_alto' => ['min' => 6, 'max' => 999],
            ],
        ],
        'Interferencia en la relación trabajo-familia' => [
            'category' => 'Organización del tiempo de trabajo',
            'max_score' => 16, // 4 preguntas × 4 puntos (19,20,21,22)
            'levels' => [
                'nulo' => ['min' => 0, 'max' => 3],
                'bajo' => ['min' => 4, 'max' => 5],
                'medio' => ['min' => 6, 'max' => 7],
                'alto' => ['min' => 8, 'max' => 9],
                'muy_alto' => ['min' => 10, 'max' => 999],
            ],
        ],
        'Liderazgo' => [
            'category' => 'Liderazgo y relaciones en el trabajo',
            'max_score' => 36, // 9 preguntas × 4 puntos (31,32,33,34,37,38,39,40,41)
            'levels' => [
                'nulo' => ['min' => 0, 'max' => 8],
                'bajo' => ['min' => 9, 'max' => 11],
                'medio' => ['min' => 12, 'max' => 15],
                'alto' => ['min' => 16, 'max' => 19],
                'muy_alto' => ['min' => 20, 'max' => 999],
            ],
        ],
        'Relaciones en el trabajo' => [
            'category' => 'Liderazgo y relaciones en el trabajo',
            'max_score' => 36, // 9 preguntas × 4 puntos (42,43,44,45,46,69,70,71,72)
            'levels' => [
                'nulo' => ['min' => 0, 'max' => 9],
                'bajo' => ['min' => 10, 'max' => 12],
                'medio' => ['min' => 13, 'max' => 16],
                'alto' => ['min' => 17, 'max' => 20],
                'muy_alto' => ['min' => 21, 'max' => 999],
            ],
        ],
        'Violencia' => [
            'category' => 'Liderazgo y relaciones en el trabajo',
            'max_score' => 32, // 8 preguntas × 4 puntos (57,58,59,60,61,62,63,64)
            'levels' => [
                'nulo' => ['min' => 0, 'max' => 6],
                'bajo' => ['min' => 7, 'max' => 9],
                'medio' => ['min' => 10, 'max' => 12],
                'alto' => ['min' => 13, 'max' => 15],
                'muy_alto' => ['min' => 16, 'max' => 999],
            ],
        ],
        'Reconocimiento del desempeño' => [
            'category' => 'Entorno organizacional',
            'max_score' => 24, // 6 preguntas × 4 puntos (47,48,49,50,51,52)
            'levels' => [
                'nulo' => ['min' => 0, 'max' => 5],
                'bajo' => ['min' => 6, 'max' => 8],
                'medio' => ['min' => 9, 'max' => 13],
                'alto' => ['min' => 14, 'max' => 17],
                'muy_alto' => ['min' => 18, 'max' => 999],
            ],
        ],
        'Insuficiente sentido de pertenencia e inestabilidad' => [
            'category' => 'Entorno organizacional',
            'max_score' => 16, // 4 preguntas × 4 puntos (53,54,55,56)
            'levels' => [
                'nulo' => ['min' => 0, 'max' => 3],
                'bajo' => ['min' => 4, 'max' => 6],
                'medio' => ['min' => 7, 'max' => 9],
                'alto' => ['min' => 10, 'max' => 12],
                'muy_alto' => ['min' => 13, 'max' => 999],
            ],
        ],
    ],

    // Rangos de calificación por dimensión según NOM-035-STPS-2018
    'dimensions' => [
        'Condiciones peligrosas e inseguras' => [
            'domain' => 'Condiciones en el ambiente de trabajo',
            'levels' => [
                'nulo' => ['min' => 0, 'max' => 1],
                'bajo' => ['min' => 2, 'max' => 3],
                'medio' => ['min' => 4, 'max' => 5],
                'alto' => ['min' => 6, 'max' => 7],
                'muy_alto' => ['min' => 8, 'max' => 999],
            ],
        ],
        'Condiciones deficientes e insalubres' => [
            'domain' => 'Condiciones en el ambiente de trabajo',
            'levels' => [
                'nulo' => ['min' => 0, 'max' => 1],
                'bajo' => ['min' => 2, 'max' => 3],
                'medio' => ['min' => 4, 'max' => 5],
                'alto' => ['min' => 6, 'max' => 7],
                'muy_alto' => ['min' => 8, 'max' => 999],
            ],
        ],
        'Trabajos peligrosos' => [
            'domain' => 'Condiciones en el ambiente de trabajo',
            'levels' => [
                'nulo' => ['min' => 0, 'max' => 1],
                'bajo' => ['min' => 2, 'max' => 3],
                'medio' => ['min' => 4, 'max' => 4],
                'alto' => ['min' => 5, 'max' => 6],
                'muy_alto' => ['min' => 7, 'max' => 999],
            ],
        ],
        'Cargas cuantitativas' => [
            'domain' => 'Carga de trabajo',
            'levels' => [
                'nulo' => ['min' => 0, 'max' => 4],
                'bajo' => ['min' => 5, 'max' => 6],
                'medio' => ['min' => 7, 'max' => 8],
                'alto' => ['min' => 9, 'max' => 12],
                'muy_alto' => ['min' => 13, 'max' => 999],
            ],
        ],
        'Ritmos de trabajo acelerado' => [
            'domain' => 'Carga de trabajo',
            'levels' => [
                'nulo' => ['min' => 0, 'max' => 2],
                'bajo' => ['min' => 3, 'max' => 4],
                'medio' => ['min' => 5, 'max' => 6],
                'alto' => ['min' => 7, 'max' => 8],
                'muy_alto' => ['min' => 9, 'max' => 999],
            ],
        ],
        'Carga mental' => [
            'domain' => 'Carga de trabajo',
            'levels' => [
                'nulo' => ['min' => 0, 'max' => 5],
                'bajo' => ['min' => 6, 'max' => 8],
                'medio' => ['min' => 9, 'max' => 11],
                'alto' => ['min' => 12, 'max' => 16],
                'muy_alto' => ['min' => 17, 'max' => 999],
            ],
        ],
        'Cargas psicológicas emocionales' => [
            'domain' => 'Carga de trabajo',
            'levels' => [
                'nulo' => ['min' => 0, 'max' => 2],
                'bajo' => ['min' => 3, 'max' => 5],
                'medio' => ['min' => 6, 'max' => 8],
                'alto' => ['min' => 9, 'max' => 11],
                'muy_alto' => ['min' => 12, 'max' => 999],
            ],
        ],
        'Cargas de alta responsabilidad' => [
            'domain' => 'Carga de trabajo',
            'levels' => [
                'nulo' => ['min' => 0, 'max' => 2],
                'bajo' => ['min' => 3, 'max' => 4],
                'medio' => ['min' => 5, 'max' => 6],
                'alto' => ['min' => 7, 'max' => 8],
                'muy_alto' => ['min' => 9, 'max' => 999],
            ],
        ],
        'Cargas contradictorias o inconsistentes' => [
            'domain' => 'Carga de trabajo',
            'levels' => [
                'nulo' => ['min' => 0, 'max' => 2],
                'bajo' => ['min' => 3, 'max' => 4],
                'medio' => ['min' => 5, 'max' => 6],
                'alto' => ['min' => 7, 'max' => 8],
                'muy_alto' => ['min' => 9, 'max' => 999],
            ],
        ],
        'Falta de control y autonomía sobre el trabajo' => [
            'domain' => 'Falta de control sobre el trabajo',
            'levels' => [
                'nulo' => ['min' => 0, 'max' => 4],
                'bajo' => ['min' => 5, 'max' => 7],
                'medio' => ['min' => 8, 'max' => 10],
                'alto' => ['min' => 11, 'max' => 12],
                'muy_alto' => ['min' => 13, 'max' => 999],
            ],
        ],
        'Limitada o nula posibilidad de desarrollo' => [
            'domain' => 'Falta de control sobre el trabajo',
            'levels' => [
                'nulo' => ['min' => 0, 'max' => 2],
                'bajo' => ['min' => 3, 'max' => 4],
                'medio' => ['min' => 5, 'max' => 6],
                'alto' => ['min' => 7, 'max' => 8],
                'muy_alto' => ['min' => 9, 'max' => 999],
            ],
        ],
        'Insuficiente participación y manejo del cambio' => [
            'domain' => 'Falta de control sobre el trabajo',
            'levels' => [
                'nulo' => ['min' => 0, 'max' => 2],
                'bajo' => ['min' => 3, 'max' => 4],
                'medio' => ['min' => 5, 'max' => 6],
                'alto' => ['min' => 7, 'max' => 8],
                'muy_alto' => ['min' => 9, 'max' => 999],
            ],
        ],
        'Limitada o inexistente capacitación' => [
            'domain' => 'Falta de control sobre el trabajo',
            'levels' => [
                'nulo' => ['min' => 0, 'max' => 1],
                'bajo' => ['min' => 2, 'max' => 2],
                'medio' => ['min' => 3, 'max' => 4],
                'alto' => ['min' => 5, 'max' => 6],
                'muy_alto' => ['min' => 7, 'max' => 999],
            ],
        ],
        'Jornadas de trabajo extensas' => [
            'domain' => 'Jornada de trabajo',
            'levels' => [
                'nulo' => ['min' => 0, 'max' => 0],
                'bajo' => ['min' => 1, 'max' => 1],
                'medio' => ['min' => 2, 'max' => 3],
                'alto' => ['min' => 4, 'max' => 5],
                'muy_alto' => ['min' => 6, 'max' => 999],
            ],
        ],
        'Influencia del trabajo fuera del centro laboral' => [
            'domain' => 'Interferencia en la relación trabajo-familia',
            'levels' => [
                'nulo' => ['min' => 0, 'max' => 1],
                'bajo' => ['min' => 2, 'max' => 3],
                'medio' => ['min' => 4, 'max' => 5],
                'alto' => ['min' => 6, 'max' => 7],
                'muy_alto' => ['min' => 8, 'max' => 999],
            ],
        ],
        'Influencia de las responsabilidades familiares' => [
            'domain' => 'Interferencia en la relación trabajo-familia',
            'levels' => [
                'nulo' => ['min' => 0, 'max' => 1],
                'bajo' => ['min' => 2, 'max' => 3],
                'medio' => ['min' => 4, 'max' => 5],
                'alto' => ['min' => 6, 'max' => 7],
                'muy_alto' => ['min' => 8, 'max' => 999],
            ],
        ],
        'Características del liderazgo' => [
            'domain' => 'Liderazgo',
            'levels' => [
                'nulo' => ['min' => 0, 'max' => 3],
                'bajo' => ['min' => 4, 'max' => 5],
                'medio' => ['min' => 6, 'max' => 8],
                'alto' => ['min' => 9, 'max' => 11],
                'muy_alto' => ['min' => 12, 'max' => 999],
            ],
        ],
        'Escasa claridad de funciones' => [
            'domain' => 'Liderazgo',
            'levels' => [
                'nulo' => ['min' => 0, 'max' => 3],
                'bajo' => ['min' => 4, 'max' => 5],
                'medio' => ['min' => 6, 'max' => 7],
                'alto' => ['min' => 8, 'max' => 9],
                'muy_alto' => ['min' => 10, 'max' => 999],
            ],
        ],
        'Relaciones sociales en el trabajo' => [
            'domain' => 'Relaciones en el trabajo',
            'levels' => [
                'nulo' => ['min' => 0, 'max' => 4],
                'bajo' => ['min' => 5, 'max' => 6],
                'medio' => ['min' => 7, 'max' => 9],
                'alto' => ['min' => 10, 'max' => 11],
                'muy_alto' => ['min' => 12, 'max' => 999],
            ],
        ],
        'Deficiente relación con los colaboradores que supervisa' => [
            'domain' => 'Relaciones en el trabajo',
            'levels' => [
                'nulo' => ['min' => 0, 'max' => 2],
                'bajo' => ['min' => 3, 'max' => 4],
                'medio' => ['min' => 5, 'max' => 6],
                'alto' => ['min' => 7, 'max' => 8],
                'muy_alto' => ['min' => 9, 'max' => 999],
            ],
        ],
        'Violencia laboral' => [
            'domain' => 'Violencia',
            'levels' => [
                'nulo' => ['min' => 0, 'max' => 6],
                'bajo' => ['min' => 7, 'max' => 9],
                'medio' => ['min' => 10, 'max' => 12],
                'alto' => ['min' => 13, 'max' => 15],
                'muy_alto' => ['min' => 16, 'max' => 999],
            ],
        ],
        'Escasa o nula retroalimentación del desempeño' => [
            'domain' => 'Reconocimiento del desempeño',
            'levels' => [
                'nulo' => ['min' => 0, 'max' => 2],
                'bajo' => ['min' => 3, 'max' => 4],
                'medio' => ['min' => 5, 'max' => 7],
                'alto' => ['min' => 8, 'max' => 9],
                'muy_alto' => ['min' => 10, 'max' => 999],
            ],
        ],
        'Escaso o nulo reconocimiento y compensación' => [
            'domain' => 'Reconocimiento del desempeño',
            'levels' => [
                'nulo' => ['min' => 0, 'max' => 2],
                'bajo' => ['min' => 3, 'max' => 4],
                'medio' => ['min' => 5, 'max' => 7],
                'alto' => ['min' => 8, 'max' => 9],
                'muy_alto' => ['min' => 10, 'max' => 999],
            ],
        ],
        'Limitado sentido de pertenencia' => [
            'domain' => 'Insuficiente sentido de pertenencia e inestabilidad',
            'levels' => [
                'nulo' => ['min' => 0, 'max' => 2],
                'bajo' => ['min' => 3, 'max' => 4],
                'medio' => ['min' => 5, 'max' => 6],
                'alto' => ['min' => 7, 'max' => 8],
                'muy_alto' => ['min' => 9, 'max' => 999],
            ],
        ],
        'Inestabilidad laboral' => [
            'domain' => 'Insuficiente sentido de pertenencia e inestabilidad',
            'levels' => [
                'nulo' => ['min' => 0, 'max' => 1],
                'bajo' => ['min' => 2, 'max' => 2],
                'medio' => ['min' => 3, 'max' => 4],
                'alto' => ['min' => 5, 'max' => 6],
                'muy_alto' => ['min' => 7, 'max' => 999],
            ],
        ],
    ],

    // DEPRECATED: Rangos antiguos de dominio (mantener por compatibilidad)
    'domains_deprecated' => [
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
