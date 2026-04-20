<?php

return [
    'answer_letters' => [
        'A' => 'siempre',
        'B' => 'casi_siempre',
        'C' => 'algunas_veces',
        'D' => 'casi_nunca',
        'E' => 'nunca',
    ],

    // Tabla 5
    'score_groups' => [
        // Siempre=0 ... Nunca=4
        'group_0_to_4' => [
            1, 4, 23, 24, 25, 26, 27, 28, 30, 31,
            32, 33, 34, 35, 36, 37, 38, 39, 40, 41,
            42, 43, 44, 45, 46, 47, 48, 49, 50, 51,
            52, 53, 55, 56, 57,
        ],

        // Siempre=4 ... Nunca=0
        'group_4_to_0' => [
            2, 3, 5, 6, 7, 8, 9, 10, 11, 12,
            13, 14, 15, 16, 17, 18, 19, 20, 21, 22,
            29, 54, 58, 59, 60, 61, 62, 63, 64, 65,
            66, 67, 68, 69, 70, 71, 72,
        ],
    ],

    'score_maps' => [
        'group_0_to_4' => [
            'A' => 0,
            'B' => 1,
            'C' => 2,
            'D' => 3,
            'E' => 4,
        ],
        'group_4_to_0' => [
            'A' => 4,
            'B' => 3,
            'C' => 2,
            'D' => 1,
            'E' => 0,
        ],
    ],

    // Tabla 6
    'dimensions' => [
        [
            'category' => 'Ambiente de trabajo',
            'domain' => 'Condiciones en el ambiente de trabajo',
            'dimension' => 'Condiciones peligrosas e inseguras',
            'items' => [1, 3],
        ],
        [
            'category' => 'Ambiente de trabajo',
            'domain' => 'Condiciones en el ambiente de trabajo',
            'dimension' => 'Condiciones deficientes e insalubres',
            'items' => [2, 4],
        ],
        [
            'category' => 'Ambiente de trabajo',
            'domain' => 'Condiciones en el ambiente de trabajo',
            'dimension' => 'Trabajos peligrosos',
            'items' => [5],
        ],

        [
            'category' => 'Factores propios de la actividad',
            'domain' => 'Carga de trabajo',
            'dimension' => 'Cargas cuantitativas',
            'items' => [6, 12],
        ],
        [
            'category' => 'Factores propios de la actividad',
            'domain' => 'Carga de trabajo',
            'dimension' => 'Ritmos de trabajo acelerado',
            'items' => [7, 8],
        ],
        [
            'category' => 'Factores propios de la actividad',
            'domain' => 'Carga de trabajo',
            'dimension' => 'Carga mental',
            'items' => [9, 10, 11],
        ],
        [
            'category' => 'Factores propios de la actividad',
            'domain' => 'Carga de trabajo',
            'dimension' => 'Cargas psicológicas emocionales',
            'items' => [65, 66, 67, 68],
        ],
        [
            'category' => 'Factores propios de la actividad',
            'domain' => 'Carga de trabajo',
            'dimension' => 'Cargas de alta responsabilidad',
            'items' => [13, 14],
        ],
        [
            'category' => 'Factores propios de la actividad',
            'domain' => 'Carga de trabajo',
            'dimension' => 'Cargas contradictorias o inconsistentes',
            'items' => [15, 16],
        ],

        [
            'category' => 'Factores propios de la actividad',
            'domain' => 'Falta de control sobre el trabajo',
            'dimension' => 'Falta de control y autonomía sobre el trabajo',
            'items' => [25, 26, 27, 28],
        ],
        [
            'category' => 'Factores propios de la actividad',
            'domain' => 'Falta de control sobre el trabajo',
            'dimension' => 'Limitada o nula posibilidad de desarrollo',
            'items' => [23, 24],
        ],
        [
            'category' => 'Factores propios de la actividad',
            'domain' => 'Falta de control sobre el trabajo',
            'dimension' => 'Insuficiente participación y manejo del cambio',
            'items' => [29, 30],
        ],
        [
            'category' => 'Factores propios de la actividad',
            'domain' => 'Falta de control sobre el trabajo',
            'dimension' => 'Limitada o inexistente capacitación',
            'items' => [35, 36],
        ],

        [
            'category' => 'Organización del tiempo de trabajo',
            'domain' => 'Jornada de trabajo',
            'dimension' => 'Jornadas de trabajo extensas',
            'items' => [17, 18],
        ],
        [
            'category' => 'Organización del tiempo de trabajo',
            'domain' => 'Interferencia en la relación trabajo-familia',
            'dimension' => 'Influencia del trabajo fuera del centro laboral',
            'items' => [19, 20],
        ],
        [
            'category' => 'Organización del tiempo de trabajo',
            'domain' => 'Interferencia en la relación trabajo-familia',
            'dimension' => 'Influencia de las responsabilidades familiares',
            'items' => [21, 22],
        ],

        [
            'category' => 'Liderazgo y relaciones en el trabajo',
            'domain' => 'Liderazgo',
            'dimension' => 'Escasa claridad de funciones',
            'items' => [31, 32, 33, 34],
        ],
        [
            'category' => 'Liderazgo y relaciones en el trabajo',
            'domain' => 'Liderazgo',
            'dimension' => 'Características del liderazgo',
            'items' => [37, 38, 39, 40, 41],
        ],

        [
            'category' => 'Liderazgo y relaciones en el trabajo',
            'domain' => 'Relaciones en el trabajo',
            'dimension' => 'Relaciones sociales en el trabajo',
            'items' => [42, 43, 44, 45, 46],
        ],
        [
            'category' => 'Liderazgo y relaciones en el trabajo',
            'domain' => 'Relaciones en el trabajo',
            'dimension' => 'Deficiente relación con los colaboradores que supervisa',
            'items' => [69, 70, 71, 72],
        ],

        [
            'category' => 'Liderazgo y relaciones en el trabajo',
            'domain' => 'Violencia',
            'dimension' => 'Violencia laboral',
            'items' => [57, 58, 59, 60, 61, 62, 63, 64],
        ],

        [
            'category' => 'Entorno organizacional',
            'domain' => 'Reconocimiento del desempeño',
            'dimension' => 'Escasa o nula retroalimentación del desempeño',
            'items' => [47, 48],
        ],
        [
            'category' => 'Entorno organizacional',
            'domain' => 'Reconocimiento del desempeño',
            'dimension' => 'Escaso o nulo reconocimiento y compensación',
            'items' => [49, 50, 51, 52],
        ],
        [
            'category' => 'Entorno organizacional',
            'domain' => 'Insuficiente sentido de pertenencia e inestabilidad',
            'dimension' => 'Limitado sentido de pertenencia',
            'items' => [55, 56],
        ],
        [
            'category' => 'Entorno organizacional',
            'domain' => 'Insuficiente sentido de pertenencia e inestabilidad',
            'dimension' => 'Inestabilidad laboral',
            'items' => [53, 54],
        ],
    ],
];