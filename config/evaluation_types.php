<?php

/**
 * Evaluation Types Configuration
 *
 * Maps evaluation types to organizations and their properties.
 * Each organization can have one or multiple evaluation types.
 *
 * Structure:
 * 'type_key' => [
 *     'id' => unique identifier,
 *     'name' => display name in Spanish,
 *     'description' => brief description,
 *     'route' => route name for the dashboard,
 *     'page' => Inertia page component name,
 *     'organizations' => array of organization UUIDs that use this type
 * ]
 */

return [
    'clima_laboral' => [
        'id' => 'clima_laboral',
        'name' => 'Clima Laboral',
        'description' => 'Evaluación de clima organizacional y ambiente laboral',
        'route' => 'organization.dashboard.clima-laboral',
        'page' => 'Organizations/Dashboard',
        'organizations' => [
            'a06fe33d-6955-4d24-98d1-a375ecb55645',
            'a05bc65b-08cd-45d5-8ae1-f4f9d3eb5238',
        ],
    ],

    'nom_035' => [
        'id' => 'nom_035',
        'name' => 'NOM-035-STPS-2018',
        'description' => 'Evaluación de factores de riesgo psicosocial en el trabajo',
        'route' => 'organization.dashboard.nom-035',
        'page' => 'Organizations/CalizaDashboard',
        'organizations' => [
            'a0315c7c-d7a2-4969-b51e-d126fa6da1af', // CORPORACION INDUSTRIAL DE CALIZA
        ],
    ],

    'nom_002' => [
        'id' => 'nom_002',
        'name' => 'NOM-002-STPS-2010',
        'description' => 'Inspección de medidas de seguridad contra incendios',
        'route' => 'organization.dashboard.nom-002',
        'page' => 'Organizations/Nom002Dashboard',
        'organizations' => [
            'a0aefcff-d83a-428e-845a-44c65074e063',
        ],
    ],
];
