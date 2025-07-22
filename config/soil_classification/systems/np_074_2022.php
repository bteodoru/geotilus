<?php

return [
    'system_info' => [
        'code' => 'np_074_2022',
        'name' => 'NP 074/2022',
        'version' => '2022',
        'country' => 'RO',
        'organization' => 'Ministerul Dezvoltării, Lucrărilor Publice și Administrației',
        'description' => 'Normativ pentru întocmirea documentațiilor geotehnice pentru construcții',
        'scope' => 'Stabilește modalitatea de clasificare a pământurilor din punct de vedere granulometric, în cadrul documentațiilor geotehnice',
        'publication_date' => '2023-01-20',
        'status' => 'activ',
    ],

    'supported_classification_criteria' => [
        'granulometry' => [
            'available_methods' => ['ternary_diagram'],
            'primary_method' => 'ternary_diagram',
            'mandatory' => true,
        ],
        'plasticity' => [
            'available_methods' => ['consistency_limits'],
            'primary_method' => 'consistency_limits',
            'mandatory' => false,
        ],
        'density' => [
            'available_methods' => ['relative_density'],
            'primary_method' => 'relative_density',
            'mandatory' => false,
        ]
    ],

    'classification_rules' => [
        'primary_criterion' => 'granulometry', // Criteriul principal
        'hierarchy' => ['granulometry', 'plasticity', 'density'], // Ordinea de aplicare
        'decision_tree' => [
            // Reguli pentru când să aplici ce criteriu
            'if_coarse_fraction_over_50' => 'use_granulometry_only',
            'if_fine_fraction_over_50' => 'use_granulometry_and_plasticity',
        ]
    ]
];
