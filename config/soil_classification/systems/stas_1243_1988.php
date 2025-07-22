<?php

return [
    'system_info' => [
        'code' => 'stas_1243_1988',
        'name' => 'STAS 1243',
        'version' => '1988',
        'country' => 'RO',
        'organization' => 'Institutul Român de Standardizare',
        'description' => 'Standard românesc pentru clasificarea pământurilor',
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
