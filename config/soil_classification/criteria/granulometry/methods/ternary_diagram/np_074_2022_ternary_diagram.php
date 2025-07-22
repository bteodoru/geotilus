<?php

return [
    'method_metadata' => [
        'classification_system' => 'np_074_2022',
        'criterion_name' => 'granulometry',
        'method_name' => 'ternary_diagram',
        'method_description' => 'Clasificarea granulometrică folosind diagrama ternară cu trei fracțiuni principale',
        'applicable_soil_types' => ['fine_grained', 'mixed_grained'],
        'chapter_reference' => 'Anexa N. O clasificare a pământurilor din punct de vedere granulometric',
    ],

    'processing_requirements' => [
        'required_soil_fractions' => ['clay', 'silt', 'sand'],
        'fraction_size_limits' => [
            'clay' => '< 0.002 mm',
            'silt' => '0.002 - 0.05 mm',
            'sand' => '0.05 - 2.0 mm'
        ],
        'coordinate_extraction_order' => ['silt', 'clay', 'sand'],
        'normalization_policy' => 'required_when_other_fractions_present',
        'precision_requirements' => [
            'coordinate_decimals' => 2,
            'percentage_decimals' => 1
        ]
    ],

    'diagram_configuration' => [
        'coordinate_system' => 'barycentric', // Tipul sistemului de coordonate
        'apex_labels' => ['Lut', 'Praf', 'Nisip'], // Etichetele vârfurilor
        'diagram_domains' => [
            [
                "points" => [
                    [0, 50, 50],
                    [0, 100, 0],
                    [50, 50, 0],
                ],
                "color" => "#fad7d5",
                "name" => "Argilă grasă",
            ],
            [
                "points" => [
                    [50, 30, 20],
                    [30, 50, 20],
                    [50, 50, 0],
                    [60, 40, 0],
                ],
                "color" => "#fff498",
                "name" => "Argilă",
            ],
            [
                "points" => [
                    [55, 25, 20],
                    [50, 30, 20],
                    [60, 40, 0],
                    [75, 25, 0],
                ],
                "color" => "#e7ac7c",
                "name" => "Argilă prafoasă",
            ],
            [
                "points" => [
                    [45, 25, 30],
                    [50, 30, 20],
                    [55, 25, 20],
                ],
                "color" => "#f5f5f4",
                "name" => "Argilă prafoasă nisipoasă",
            ],
            [
                "points" => [
                    [0, 25, 75],
                    [0, 50, 50],
                    [30, 50, 20],
                    [50, 30, 20],
                    [45, 25, 30],
                ],
                "color" => "#c6d291",
                "name" => "Argilă nisipoasă",
            ],
            [
                "points" => [
                    [80, 0, 20],
                    [70, 10, 20],
                    [90, 10, 0],
                    [100, 0, 0],
                ],
                "color" => "#ee9479",
                "name" => "Praf",
            ],
            [
                "points" => [
                    [70, 10, 20],
                    [55, 25, 20],
                    [75, 25, 0],
                    [90, 10, 0],
                ],
                "color" => "#e5e3f0",
                "name" => "Praf argilos",
            ],
            [
                "points" => [
                    [55, 10, 35],
                    [47.5, 25, 27.5],
                    [55, 25, 20],
                    [70, 10, 20],
                ],
                "color" => "#ffe292",
                "name" => "Praf nisipos argilos",
            ],
            [
                "points" => [
                    [60, 0, 40],
                    [55, 10, 35],
                    [70, 10, 20],
                    [80, 0, 20],
                ],
                "color" => "#b2a9d2",
                "name" => "Praf nisipos",
            ],
            [
                "points" => [
                    [0, 0, 100],
                    [0, 15, 85],
                    [15, 0, 85],
                ],
                "color" => "#83c097",
                "name" => "Nisip",
            ],
            [
                "points" => [
                    [5, 10, 85],
                    [0, 15, 85],
                    [0, 25, 75],
                    [47.5, 25, 27.5],
                    [55, 10, 35],
                ],
                "color" => "#f8baa3",
                "name" => "Nisip argilos",
            ],
            [
                "points" => [
                    [15, 0, 85],
                    [5, 10, 85],
                    [55, 10, 35],
                    [60, 0, 40],
                ],
                "color" => "#d0e7d2",
                "name" => "Nisip prăfos",
            ]
        ]
    ],

    'interpretation_rules' => [
        'soil_naming_convention' => 'romanian_standard',
        'coarse_fraction_handling' => [
            'threshold_percentage' => 40,
            'naming_prefix_rule' => 'coarse_material_first_if_over_threshold'
        ]
    ]
];
