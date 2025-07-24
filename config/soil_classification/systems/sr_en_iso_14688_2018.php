<?php

return [
    'system_info' => [
        'code' => 'sr_en_iso_14688_2018',
        'name' => 'SR EN ISO 14688-2',
        'version' => '2018',
        'country' => 'RO',
        'organization' => 'Institutul Român de Standardizare',
        'description' => 'Investigaţii şi încercări geotehnice. Identificarea şi clasificarea pământurilor. Partea 2: Principii pentru o clasificare',
        'scope' => 'Definește metodele de clasificare și identificare a pământurilor pentru lucrări geotehnice',
        'publication_date' => '2018-07-31',
        'status' => 'activ',
    ],

    'supported_classification_criteria' => [
        'granulometry' => [
            'applicable_granulometric_classes' => ['coarse', 'very_coarse'],
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
