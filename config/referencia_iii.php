<?php

return [
    'general' => [
        1 => 'El espacio donde trabajo me permite realizar mis actividades de manera segura e higiénica',
        2 => 'Mi trabajo me exige hacer mucho esfuerzo físico',
        3 => 'Me preocupa sufrir un accidente en mi trabajo',
        4 => 'Considero que en mi trabajo se aplican las normas de seguridad y salud en el trabajo',
        5 => 'Considero que las actividades que realizo son peligrosas',
        6 => 'Por la cantidad de trabajo que tengo debo quedarme tiempo adicional a mi turno',
        7 => 'Por la cantidad de trabajo que tengo debo trabajar sin parar',
        8 => 'Considero que es necesario mantener un ritmo de trabajo acelerado',
        9 => 'Mi trabajo exige que esté muy concentrado',
        10 => 'Mi trabajo requiere que memorice mucha información',
        11 => 'En mi trabajo tengo que tomar decisiones difíciles muy rápido',
        12 => 'Mi trabajo exige que atienda varios asuntos al mismo tiempo',
        13 => 'En mi trabajo soy responsable de cosas de mucho valor',
        14 => 'Respondo ante mi jefe por los resultados de toda mi área de trabajo',
        15 => 'En el trabajo me dan órdenes contradictorias',
        16 => 'Considero que en mi trabajo me piden hacer cosas innecesarias',
        17 => 'Trabajo horas extras más de tres veces a la semana',
        18 => 'Mi trabajo me exige laborar en días de descanso, festivos o fines de semana',
        19 => 'Considero que el tiempo en el trabajo es mucho y perjudica mis actividades familiares o personales',
        20 => 'Debo atender asuntos de trabajo cuando estoy en casa',
        21 => 'Pienso en las actividades familiares o personales cuando estoy en mi trabajo',
        22 => 'Pienso que mis responsabilidades familiares afectan mi trabajo',
        23 => 'Mi trabajo permite que desarrolle nuevas habilidades',
        24 => 'En mi trabajo puedo aspirar a un mejor puesto',
        25 => 'Durante mi jornada de trabajo puedo tomar pausas cuando las necesito',
        26 => 'Puedo decidir cuánto trabajo realizo durante la jornada laboral',
        27 => 'Puedo decidir la velocidad a la que realizo mis actividades en mi trabajo',
        28 => 'Puedo cambiar el orden de las actividades que realizo en mi trabajo',
        29 => 'Los cambios que se presentan en mi trabajo dificultan mi labor',
        30 => 'Cuando se presentan cambios en mi trabajo se tienen en cuenta mis ideas o aportaciones',
        31 => 'Me informan con claridad cuáles son mis funciones',
        32 => 'Me explican claramente los resultados que debo obtener en mi trabajo',
        33 => 'Me explican claramente los objetivos de mi trabajo',
        34 => 'Me informan con quién puedo resolver problemas o asuntos de trabajo',
        35 => 'Me permiten asistir a capacitaciones relacionadas con mi trabajo',
        36 => 'Recibo capacitación útil para hacer mi trabajo',
        37 => 'Mi jefe ayuda a organizar mejor el trabajo',
        38 => 'Mi jefe tiene en cuenta mis puntos de vista y opiniones',
        39 => 'Mi jefe me comunica a tiempo la información relacionada con el trabajo',
        40 => 'La orientación que me da mi jefe me ayuda a realizar mejor mi trabajo',
        41 => 'Mi jefe ayuda a solucionar los problemas que se presentan en el trabajo',
        42 => 'Puedo confiar en mis compañeros de trabajo',
        43 => 'Entre compañeros solucionamos los problemas de trabajo de forma respetuosa',
        44 => 'En mi trabajo me hacen sentir parte del grupo',
        45 => 'Cuando tenemos que realizar trabajo de equipo los compañeros colaboran',
        46 => 'Mis compañeros de trabajo me ayudan cuando tengo dificultades',
        47 => 'Me informan sobre lo que hago bien en mi trabajo',
        48 => 'La forma como evalúan mi trabajo en mi centro de trabajo me ayuda a mejorar mi desempeño',
        49 => 'En mi centro de trabajo me pagan a tiempo mi salario',
        50 => 'El pago que recibo es el que merezco por el trabajo que realizo',
        51 => 'Si obtengo los resultados esperados en mi trabajo me recompensan o reconocen',
        52 => 'Las personas que hacen bien el trabajo pueden crecer laboralmente',
        53 => 'Considero que mi trabajo es estable',
        54 => 'En mi trabajo existe continua rotación de personal',
        55 => 'Siento orgullo de laborar en este centro de trabajo',
        56 => 'Me siento comprometido con mi trabajo',
        57 => 'En mi trabajo puedo expresarme libremente sin interrupciones',
        58 => 'Recibo críticas constantes a mi persona y/o trabajo',
        59 => 'Recibo burlas, calumnias, difamaciones, humillaciones o ridiculizaciones',
        60 => 'Se ignora mi presencia o se me excluye de las reuniones de trabajo y en la toma de decisiones',
        61 => 'Se manipulan las situaciones de trabajo para hacerme parecer un mal trabajador',
        62 => 'Se ignoran mis éxitos laborales y se atribuyen a otros trabajadores',
        63 => 'Me bloquean o impiden las oportunidades que tengo para obtener ascenso o mejora en mi trabajo',
        64 => 'He presenciado actos de violencia en mi centro de trabajo',
    ],

    'conditional_sections' => [
        'customer_service' => [
            'condition' => 'En mi trabajo debo brindar servicio a clientes o usuarios',
            'questions' => [
                65 => 'Atiendo clientes o usuarios muy enojados',
                66 => 'Mi trabajo me exige atender personas muy necesitadas de ayuda o enfermas',
                67 => 'Para hacer mi trabajo debo demostrar sentimientos distintos a los míos',
                68 => 'Mi trabajo me exige atender situaciones de violencia',
            ],
        ],
        'management' => [
            'condition' => 'Soy jefe de otros trabajadores',
            'questions' => [
                69 => 'Comunican tarde los asuntos de trabajo',
                70 => 'Dificultan el logro de los resultados del trabajo',
                71 => 'Cooperan poco cuando se necesita',
                72 => 'Ignoran las sugerencias para mejorar su trabajo',
            ],
        ],
    ],

    'dimensions' => [
        'Ambiente de trabajo' => [
            'Condiciones en el ambiente de trabajo' => [
                'Condiciones peligrosas e inseguras' => [1, 3],
                'Condiciones deficientes e insalubres' => [2, 4],
                'Trabajos peligrosos' => [5],
            ],
        ],

        'Factores propios de la actividad' => [
            'Carga de trabajo' => [
                'Cargas cuantitativas' => [6, 12],
                'Ritmos de trabajo acelerado' => [7, 8],
                'Carga mental' => [9, 10, 11],
                'Cargas psicológicas emocionales' => [65, 66, 67, 68],
                'Cargas de alta responsabilidad' => [13, 14],
                'Cargas contradictorias o inconsistentes' => [15, 16],
            ],
            'Falta de control sobre el trabajo' => [
                'Falta de control y autonomía sobre el trabajo' => [25, 26, 27, 28],
                'Limitada o nula posibilidad de desarrollo' => [23, 24],
                'Insuficiente participación y manejo del cambio' => [29, 30],
                'Limitada o inexistente capacitación' => [35, 36],
            ],
        ],

        'Organización del tiempo de trabajo' => [
            'Jornada de trabajo' => [
                'Jornadas de trabajo extensas' => [17, 18],
            ],
            'Interferencia en la relación trabajo-familia' => [
                'Influencia del trabajo fuera del centro laboral' => [19, 20],
                'Influencia de las responsabilidades familiares' => [21, 22],
            ],
        ],

        'Liderazgo y relaciones en el trabajo' => [
            'Liderazgo' => [
                'Escasa claridad de funciones' => [31, 32, 33, 34],
                'Características del liderazgo' => [37, 38, 39, 40, 41],
            ],
            'Relaciones en el trabajo' => [
                'Relaciones sociales en el trabajo' => [42, 43, 44, 45, 46],
                'Deficiente relación con los colaboradores que supervisa' => [69, 70, 71, 72],
            ],
            'Violencia' => [
                'Violencia laboral' => [57, 58, 59, 60, 61, 62, 63, 64],
            ],
        ],

        'Entorno organizacional' => [
            'Reconocimiento del desempeño' => [
                'Escasa o nula retroalimentación del desempeño' => [47, 48],
                'Escaso o nulo reconocimiento y compensación' => [49, 50, 51, 52],
            ],
            'Insuficiente sentido de pertenencia e inestabilidad' => [
                'Limitado sentido de pertenencia' => [55, 56],
                'Inestabilidad laboral' => [53, 54],
            ],
        ],
    ],

    // Respuestas Si/No
    'acontecimientos_traumaticos' => [
        'title' => '¿Ha presenciado o sufrido alguna vez, durante o con motivo del trabajo un acontecimiento como los siguientes:',
        'questions' => [
            73 => 'Accidente que tenga como consecuencia la muerte, la pérdida de un miembro o una lesión grave',
            74 => 'Asaltos',
            75 => 'Actos violentos que derivaron en lesiones graves',
            76 => 'Secuestro',
            77 => 'Amenazas',
            78 => 'Cualquier otro que ponga en riesgo su vida o salud, y/o la de otras personas',
        ],
    ],
];
