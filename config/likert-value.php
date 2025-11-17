<?php

return [
    'puestos' => [
        1 => 'OPERADOR COSTURA',
        2 => 'OPERADOR CORTE',
        3 => 'LÍDER',
        4 => 'ESPECIALISTA',
        5 => 'FLEXIBLE',
        6 => 'INSPECTOR',
        7 => 'CLERK',
        8 => 'TÉCNICO',
        9 => 'MATERIALISTA',
        10 => 'MONTACARGUISTA',
        11 => 'SUPERVISOR',
        12 => 'SUPERINTENDENTE',
        13 => 'GERENTE',
        14 => 'INGENIERO',
        15 => 'ANALISTA',
        16 => 'ADMINISTRATIVO',
        17 => 'GENERALISTA',
        18 => 'MECÁNICO',
        19 => 'COORDINADOR',
        20 => 'PROGRAMADOR/ PLANEADOR',
        21 => 'COMPRADOR',
        22 => 'CONTADOR',
        23 => 'CONTRALOR',
        24 => 'ADMINISTRADOR',
    ],
    'areas' => [
        1 => 'PRODUCCIÓN',
        2 => 'CALIDAD',
        3 => 'MANTENIMIENTO',
        4 => 'ADMINISTRACIÓN',
        5 => 'MATERIALES',
        6 => 'INGENIERÍA',
        7 => 'SISTEMAS',
        8 => 'RECURSOS HUMANOS',
        9 => 'SEGURIDAD Y MEDIO AMBIENTE',
        10 => 'MEJORA CONTINUA',
        11 => 'PROYECTOS / PROGRAMACIÓN',
        12 => 'ALMACÉN',
        13 => 'FINANZAS',
        14 => 'IMPORT / EXPORT',
        15 => 'OPERACIONES',
        16 => 'MANUFACTURA',
        17 => 'OTROS',
    ],
    'preguntas' => [
        1 => 'La empresa prioriza mi seguridad física mediante prácticas consistentes como charlas diarias de seguridad, listas de verificación y prevención de riesgos.',
        2 => 'El liderazgo aborda activamente riesgos, como el acoso, horas extras excesivas, cargas de trabajo excesivas, o tensión por alta rotación, brechas en capacitación o falta de control sobre mis tareas.',
        3 => 'Me siento seguro en mi empleo porque la empresa utiliza prácticas competitivas para mantener la estabilidad.',
        4 => 'Los esfuerzos de la empresa para reducir la rotación me dan confianza sobre mi empleo a largo plazo aquí.',
        5 => 'Mis salarios y beneficios son justos y competitivos en comparación con los estándares de la industria.',
        6 => 'La gerencia se comunica de manera abierta y regular sobre actualizaciones de la empresa, como desafíos de calidad o estado del negocio.',
        7 => 'El GERENTE DE PLANTA me trata con justicia.',
        8 => 'El GERENTE DE PRODUCCIÓN me trata con justicia.',
        9 => 'El GERENTE DE RECURSOS HUMANOS me trata con justicia.',
        10 => 'Mi SUPERVISOR me trata con justicia.',
        11 => 'Mi LÍDER DE EQUIPO me trata con justicia.',
        12 => 'Se me anima a participar en procesos de toma de decisiones que afectan mi trabajo.',
        13 => 'La empresa valora mi aporte en mejoras operativas, como cambios en procesos o medidas de seguridad.',
        14 => 'El liderazgo involucra a los empleados en iniciativas como relanzar celdas de trabajo o actualizar estándares.',
        15 => 'Mis contribuciones a la calidad y operaciones son reconocidas regularmente por la gerencia.',
        16 => 'La empresa recompensa el desempeño confiable, como colocar trabajadores calificados en roles críticos.',
        17 => 'Recibo capacitación adecuada para desempeñar mi trabajo de manera efectiva, incluyendo desarrollo continuo más allá de la incorporación básica.',
        18 => 'La empresa proporciona suficientes capacitadores y recursos para apoyar el crecimiento de habilidades en áreas como crosstraining, estándares de calidad y seguridad, u otras áreas relacionadas con elementos clave de mi trabajo.',
        19 => 'La empresa apoya mi equilibrio entre vida laboral y personal mediante patrones de turnos razonables y horas extras manejables.',
        20 => 'Las políticas de ausentismo y el tiempo planeado libre se manejan de manera respetuosa.',
        21 => 'Existen caminos claros para el avance profesional basados en mis habilidades y desempeño.',
        22 => 'La empresa invierte en desarrollar empleados para roles superiores, como líderes de equipo o posiciones calificadas.',
        23 => 'La empresa proporciona recursos accesibles, como consejería o programas de asistencia, para desafíos personales y laborales como ausentismo, rotación, estrés.',
    ],
    'niveles' => [
        'Entorno Laboral Seguro' => [
            'preguntas' => [1, 2],
        ],
        'Seguridad Laboral' => [
            'preguntas' => [3, 4],
        ],
        'Compensación Justa' => [
            'preguntas' => [5],
        ],
        'Comunicación Abierta' => [
            'preguntas' => [6, 7, 8, 9, 10, 11],
        ],
        'Participación de los Empleados' => [
            'preguntas' => [12, 13, 14],
        ],
        'Reconocimiento y Recompensa' => [
            'preguntas' => [15, 16],
        ],
        'Capacitación y Desarrollo' => [
            'preguntas' => [17, 18],
        ],
        'Equilibrio entre Vida Laboral y Personal' => [
            'preguntas' => [19, 20],
        ],
        'Avance Profesional' => [
            'preguntas' => [21, 22],
        ],
        'Apoyo al Empleado' => [
            'preguntas' => [23],
        ],
    ],
    'valorNiveles' => [
        'Entorno Laboral Seguro' => [
            'Totalmente Desacuerdo' => [
                'min' => 2,
                'max' => 3.5,
            ],
            'Desacuerdo' => [
                'min' => 3.6,
                'max' => 5,
            ],
            'De Acuerdo' => [
                'min' => 5.1,
                'max' => 6.5,
            ],
            'Totalmente de Acuerdo' => [
                'min' => 6.6,
                'max' => 8,
            ],
        ],
        'Seguridad Laboral' => [
            'Totalmente Desacuerdo' => [
                'min' => 2,
                'max' => 3.5,
            ],
            'Desacuerdo' => [
                'min' => 3.6,
                'max' => 5,
            ],
            'De Acuerdo' => [
                'min' => 5.1,
                'max' => 6.5,
            ],
            'Totalmente de Acuerdo' => [
                'min' => 6.6,
                'max' => 8,
            ],
        ],
        'Compensación Justa' => [
            'Totalmente Desacuerdo' => [
                'min' => 1,
                'max' => 1.75,
            ],
            'Desacuerdo' => [
                'min' => 1.76,
                'max' => 2.5,
            ],
            'De Acuerdo' => [
                'min' => 2.6,
                'max' => 3.25,
            ],
            'Totalmente de Acuerdo' => [
                'min' => 3.26,
                'max' => 4,
            ],
        ],
        'Comunicación Abierta' => [
            'Totalmente Desacuerdo' => [
                'min' => 6,
                'max' => 10.5,
            ],
            'Desacuerdo' => [
                'min' => 10.6,
                'max' => 15,
            ],
            'De Acuerdo' => [
                'min' => 15.1,
                'max' => 19.5,
            ],
            'Totalmente de Acuerdo' => [
                'min' => 19.6,
                'max' => 24,
            ],
        ],
        'Participación de los Empleados' => [
            'Totalmente Desacuerdo' => [
                'min' => 3,
                'max' => 5.25,
            ],
            'Desacuerdo' => [
                'min' => 5.26,
                'max' => 7.5,
            ],
            'De Acuerdo' => [
                'min' => 7.6,
                'max' => 9.75,
            ],
            'Totalmente de Acuerdo' => [
                'min' => 9.76,
                'max' => 12,
            ],
        ],
        'Reconocimiento y Recompensa' => [
            'Totalmente Desacuerdo' => [
                'min' => 2,
                'max' => 3.5,
            ],
            'Desacuerdo' => [
                'min' => 3.6,
                'max' => 5,
            ],
            'De Acuerdo' => [
                'min' => 5.1,
                'max' => 6.5,
            ],
            'Totalmente de Acuerdo' => [
                'min' => 6.6,
                'max' => 8,
            ],
        ],
        'Capacitación y Desarrollo' => [
            'Totalmente Desacuerdo' => [
                'min' => 2,
                'max' => 3.5,
            ],
            'Desacuerdo' => [
                'min' => 3.6,
                'max' => 5,
            ],
            'De Acuerdo' => [
                'min' => 5.1,
                'max' => 6.5,
            ],
            'Totalmente de Acuerdo' => [
                'min' => 6.6,
                'max' => 8,
            ],
        ],
        'Equilibrio entre Vida Laboral y Personal' => [
            'Totalmente Desacuerdo' => [
                'min' => 2,
                'max' => 3.5,
            ],
            'Desacuerdo' => [
                'min' => 3.6,
                'max' => 5,
            ],
            'De Acuerdo' => [
                'min' => 5.1,
                'max' => 6.5,
            ],
            'Totalmente de Acuerdo' => [
                'min' => 6.6,
                'max' => 8,
            ],
        ],
        'Avance Profesional' => [
            'Totalmente Desacuerdo' => [
                'min' => 2,
                'max' => 3.5,
            ],
            'Desacuerdo' => [
                'min' => 3.6,
                'max' => 5,
            ],
            'De Acuerdo' => [
                'min' => 5.1,
                'max' => 6.5,
            ],
            'Totalmente de Acuerdo' => [
                'min' => 6.6,
                'max' => 8,
            ],
        ],
        'Apoyo al Empleado' => [
            'Totalmente Desacuerdo' => [
                'min' => 1,
                'max' => 1.75,
            ],
            'Desacuerdo' => [
                'min' => 1.76,
                'max' => 2.5,
            ],
            'De Acuerdo' => [
                'min' => 2.6,
                'max' => 3.25,
            ],
            'Totalmente de Acuerdo' => [
                'min' => 3.26,
                'max' => 4,
            ],
        ],
        'Clima Laboral' => [
            'Totalmente Desacuerdo' => [
                'min' => 23,
                'max' => 40.5,
            ],
            'Desacuerdo' => [
                'min' => 40.6,
                'max' => 58,
            ],
            'De Acuerdo' => [
                'min' => 59,
                'max' => 75.5,
            ],
            'Totalmente de Acuerdo' => [
                'min' => 75.6,
                'max' => 93,
            ],
        ],
    ],
    'valorOpciones' => [
        'A' => 4,
        'B' => 3,
        'C' => 2,
        'D' => 1,
    ],
];
