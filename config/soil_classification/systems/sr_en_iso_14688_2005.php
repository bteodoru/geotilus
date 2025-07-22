<?php

return [
    'system_info' => [
        'code' => 'sr_en_14688_2005',
        'name' => 'SR EN ISO 14688-2',
        'version' => '2005',
        'country' => 'RO',
        'organization' => 'Institutul Român de Standardizare',
        'description' => 'Investigaţii şi încercări geotehnice. Identificarea şi clasificarea pământurilor. Partea 2: Principii pentru o clasificare',
        'scope' => 'Definește metodele de clasificare și identificare a pământurilor pentru lucrări geotehnice',
        'publication_date' => '1988-01-01',
        'status' => 'abrogat',
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
