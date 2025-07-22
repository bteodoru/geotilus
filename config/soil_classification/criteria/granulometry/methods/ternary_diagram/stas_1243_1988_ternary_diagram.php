<?php

return [
    'method_metadata' => [
        'classification_system' => 'stas_1243_1988',
        'criterion_name' => 'granulometry',
        'method_name' => 'ternary_diagram',
        'method_description' => 'Clasificarea granulometrică folosind diagrama ternară cu trei fracțiuni principale',
        'applicable_soil_types' => ['fine_grained', 'mixed_grained'],
        'chapter_reference' => 'Capitolul 4.2 - Clasificarea granulometrică prin diagrama ternară',
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
                    [0.00, 0.00, 100.00],
                    [0.00, 10.00, 90.00],
                    [10.00, 0.00, 90.00],
                ],
                "color" => "#FFD700",
                "name" => "Nisip",
            ],
            [
                "points" => [
                    [10.00, 0.00, 90.00],
                    [0.00, 10.00, 90.00],
                    [0.00, 15.00, 85.00],
                    [42.50, 15.00, 42.50],
                    [50.00, 0.00, 50.00],
                ],
                "color" => "#DAA520",
                "name" => "Nisip prăfos",
            ],
            [
                "points" => [
                    [50.00, 0.00, 50.00],
                    [42.50, 15.00, 42.50],
                    [55.00, 15.00, 30.00],
                    [70.00, 0.00, 30.00],
                ],
                "color" => "#CD853F",
                "name" => "Praf nisipos",
            ],
            [
                "points" => [
                    [70.00, 0.00, 30.00],
                    [55.00, 15.00, 30.00],
                    [85.00, 15.00, 0.00],
                    [100.00, 0.00, 0.00],
                ],
                "color" => "#A0522D",
                "name" => "Praf",
            ],
            [
                "points" => [
                    [0.00, 15.00, 85.00],
                    [0.00, 30.00, 70.00],
                    [35.00, 30.00, 35.00],
                    [42.50, 15.00, 42.50],
                ],
                "color" => "#8B4513",
                "name" => "Nisip argilos",
            ],
            [
                "points" => [
                    [42.50, 15.00, 42.50],
                    [35.00, 30.00, 35.00],
                    [40.00, 30.00, 30.00],
                    [55.00, 15.00, 30.00],
                ],
                "color" => "#D2691E",
                "name" => "Praf nisipos argilos",
            ],
            [
                "points" => [
                    [55.00, 15.00, 30.00],
                    [40.00, 30.00, 30.00],
                    [70.00, 30.00, 0.00],
                    [85.00, 15.00, 0.00],
                ],
                "color" => "#BC8F8F",
                "name" => "Praf argilos",
            ],
            [
                "points" => [
                    [0.00, 30.00, 70.00],
                    [0.00, 60.00, 40.00],
                    [10.00, 60.00, 30.00],
                    [35.00, 35.00, 30.00],
                    [30.00, 30.00, 40.00],
                ],
                "color" => "#2F4F4F",
                "name" => "Argilă nisipoasă",
            ],
            [
                "points" => [
                    [30.00, 30.00, 40.00],
                    [35.00, 35.00, 30.00],
                    [40.00, 30.00, 30.00],
                ],
                "color" => "#708090",
                "name" => "Argilă prafoasă nisipoasă",
            ],
            [
                "points" => [
                    [40.00, 30.00, 30.00],
                    [35.00, 35.00, 30.00],
                    [50.00, 50.00, 0.00],
                    [70.00, 30.00, 0.00],
                ],
                "color" => "#696969",
                "name" => "Argilă prafoasă",
            ],
            [
                "points" => [
                    [35.00, 35.00, 30.00],
                    [10.00, 60.00, 30.00],
                    [40.00, 60.00, 0.00],
                    [50.00, 50.00, 0.00],
                ],
                "color" => "#556B2F",
                "name" => "Argilă",
            ],
            [
                "points" => [
                    [0.00, 60.00, 40.00],
                    [40.00, 60.00, 0.00],
                    [0.00, 100.00, 0.00],
                ],
                "color" => "#191970",
                "name" => "Argilă grasă",
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
