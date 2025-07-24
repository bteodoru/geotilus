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
            'applicable_granulometric_classes' => ['fine', 'coarse', 'very_coarse'],
            'available_methods' => ['ternary_diagram'],
            'primary_method' => 'ternary_diagram',
        ],
        'plasticity' => [
            'available_methods' => ['consistency_limits'],
            'primary_method' => 'consistency_limits',
        ],
        'density' => [
            'available_methods' => ['relative_density'],
            'primary_method' => 'relative_density',
        ]
    ],

];
