<?php

return [
    'guide_I' => [
        'title' => 'Cuestionario de Maslach - Burnout',
        'description' => 'Evaluación del síndrome de burnout',
        'questions' => [
            [
                'key' => 'pregunta_1',
                'text' => '¿Con qué frecuencia se siente emocionalmente agotado por su trabajo?',
                'type' => 'radio',
                'options' => [
                    ['value' => 'nunca', 'label' => 'Nunca'],
                    ['value' => 'pocas_veces_año', 'label' => 'Pocas veces al año'],
                    ['value' => 'una_vez_mes', 'label' => 'Una vez al mes'],
                    ['value' => 'pocas_veces_mes', 'label' => 'Pocas veces al mes'],
                    ['value' => 'una_vez_semana', 'label' => 'Una vez a la semana'],
                    ['value' => 'pocas_veces_semana', 'label' => 'Pocas veces a la semana'],
                    ['value' => 'todos_dias', 'label' => 'Todos los días']
                ]
            ],
            [
                'key' => 'pregunta_2',
                'text' => '¿Con qué frecuencia se siente agotado al final de la jornada de trabajo?',
                'type' => 'radio',
                'options' => [
                    ['value' => 'nunca', 'label' => 'Nunca'],
                    ['value' => 'pocas_veces_año', 'label' => 'Pocas veces al año'],
                    ['value' => 'una_vez_mes', 'label' => 'Una vez al mes'],
                    ['value' => 'pocas_veces_mes', 'label' => 'Pocas veces al mes'],
                    ['value' => 'una_vez_semana', 'label' => 'Una vez a la semana'],
                    ['value' => 'pocas_veces_semana', 'label' => 'Pocas veces a la semana'],
                    ['value' => 'todos_dias', 'label' => 'Todos los días']
                ]
            ],
            // Agregar más preguntas según sea necesario
        ]
    ],

    'guide_III' => [
        'title' => 'Factores Psicosociales en el Trabajo',
        'description' => 'Evaluación de factores de riesgo psicosocial',
        'questions' => [
            // Las preguntas de la Guía III son numeradas del 01 al 72
            // Se generan dinámicamente en el componente Vue
        ],
        'options' => [
            ['value' => 'siempre', 'label' => 'Siempre'],
            ['value' => 'casi_siempre', 'label' => 'Casi siempre'],
            ['value' => 'algunas_veces', 'label' => 'Algunas veces'],
            ['value' => 'casi_nunca', 'label' => 'Casi nunca'],
            ['value' => 'nunca', 'label' => 'Nunca']
        ]
    ],

    'guide_V' => [
        'title' => 'Datos Demográficos y Ocupacionales',
        'description' => 'Información personal y laboral',
        'questions' => [
            [
                'key' => 'sexo',
                'label' => 'Sexo',
                'type' => 'select',
                'required' => true,
                'options' => [
                    ['value' => 'hombre', 'label' => 'Hombre'],
                    ['value' => 'mujer', 'label' => 'Mujer']
                ]
            ],
            [
                'key' => 'edad',
                'label' => 'Edad',
                'type' => 'number',
                'required' => true,
                'min' => 18,
                'max' => 100
            ],
            [
                'key' => 'estado_civil',
                'label' => 'Estado Civil',
                'type' => 'select',
                'required' => true,
                'options' => [
                    ['value' => 'soltero', 'label' => 'Soltero(a)'],
                    ['value' => 'casado', 'label' => 'Casado(a)'],
                    ['value' => 'union_libre', 'label' => 'Unión libre'],
                    ['value' => 'divorciado', 'label' => 'Divorciado(a)'],
                    ['value' => 'viudo', 'label' => 'Viudo(a)']
                ]
            ],
            [
                'key' => 'nivel_estudios',
                'label' => 'Nivel de Estudios',
                'type' => 'select',
                'required' => true,
                'options' => [
                    ['value' => 'primaria', 'label' => 'Primaria'],
                    ['value' => 'secundaria', 'label' => 'Secundaria'],
                    ['value' => 'preparatoria', 'label' => 'Preparatoria'],
                    ['value' => 'tecnico', 'label' => 'Técnico'],
                    ['value' => 'licenciatura', 'label' => 'Licenciatura'],
                    ['value' => 'posgrado', 'label' => 'Posgrado']
                ]
            ],
            [
                'key' => 'ocupacion',
                'label' => 'Ocupación',
                'type' => 'text',
                'required' => true
            ],
            [
                'key' => 'antiguedad_empresa',
                'label' => 'Antigüedad en la empresa (años)',
                'type' => 'number',
                'required' => true,
                'min' => 0,
                'max' => 50
            ],
            [
                'key' => 'antiguedad_puesto',
                'label' => 'Antigüedad en el puesto actual (años)',
                'type' => 'number',
                'required' => true,
                'min' => 0,
                'max' => 50
            ],
            [
                'key' => 'tipo_contrato',
                'label' => 'Tipo de Contrato',
                'type' => 'select',
                'required' => true,
                'options' => [
                    ['value' => 'indefinido', 'label' => 'Indefinido'],
                    ['value' => 'temporal', 'label' => 'Temporal'],
                    ['value' => 'por_obra', 'label' => 'Por obra determinada'],
                    ['value' => 'honorarios', 'label' => 'Honorarios']
                ]
            ],
            [
                'key' => 'tipo_jornada',
                'label' => 'Tipo de Jornada',
                'type' => 'select',
                'required' => true,
                'options' => [
                    ['value' => 'diurna', 'label' => 'Diurna'],
                    ['value' => 'nocturna', 'label' => 'Nocturna'],
                    ['value' => 'mixta', 'label' => 'Mixta'],
                    ['value' => 'rotativa', 'label' => 'Rotativa']
                ]
            ]
        ]
    ]
];