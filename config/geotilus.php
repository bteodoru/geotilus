<?php

return [


    'CasagrandePlasticityChartDomains' => [
        'SiL' => [
            'points' => [
                [10, 0],
                [10, 4],
                [(4 + 0.73 * 20) / 0.73, 4],
                [35, 0.73 * (35 - 20)],
                [35, 0]
            ],
            'plasticity' => 'redusă',
            'soil' => 'praf',
            'color' => "#FDE272"
            // 'color' => "#FEF7C3"
        ],
        'ClL-SiL' => [
            'points' => [
                [10, 4],
                [10, 7],
                [(7 + 0.73 * 20) / 0.73, 7],
                [(4 + 0.73 * 20) / 0.73, 4]
            ],
            'plasticity' => 'redusă',
            'soil' => 'argilă-praf',
            'color' => "#292524"
            // 'color' => "#713B12"
            // 'color' => "#5D6B98"
        ],
        'ClL' => [
            'points' => [
                [(7 + 0.9 * 8) / 0.9, 7],
                [35, 0.9 * (35 - 8)],
                [35, 0.73 * (35 - 20)],
                [(7 + 0.73 * 20) / 0.73, 7]
            ],
            'plasticity' => 'redusă',
            'soil' => 'argilă',
            'color' => "#D7D3D0"
            // 'color' => "#F5F5F4"

        ],
        'SiM' => [
            'points' => [
                [35, 0],
                [35, 0.73 * (35 - 20)],
                [50, 0.73 * (50 - 20)],
                [50, 0]
            ],
            'plasticity' => 'medie',
            'soil' => 'praf',
            'color' => "#FAC515"
            // 'color' => "#FEEE95"
        ],
        'ClM' => [
            'points' => [
                [35, 0.73 * (35 - 20)],
                [35, 0.9 * (35 - 8)],
                [50, 0.9 * (50 - 8)],
                [50, 0.73 * (50 - 20)]
            ],
            'plasticity' => 'medie',
            'soil' => 'argilă',
            'color' => "#A9A29D"
            // 'color' => "#E7E5E4"
        ],
        'SiH' => [
            'points' => [
                [50, 0],
                [50, 0.73 * (50 - 20)],
                [70, 0.73 * (70 - 20)],
                [70, 0]
            ],
            'plasticity' => 'mare',
            'soil' => 'praf',
            'color' => "#EAAA08"
            // 'color' => "#FDE272"
        ],
        'ClH' => [
            'points' => [
                [50, 0.73 * (50 - 20)],
                [50, 0.9 * (50 - 8)],
                [70, 0.9 * (70 - 8)],
                [70, 0.73 * (70 - 20)]
            ],
            'plasticity' => 'mare',
            'soil' => 'argilă',
            'color' => "#79716B"
            // 'color' => "#D7D3D0"
        ],
        'SiV' => [
            'points' => [
                [70, 0],
                [70, 0.73 * (70 - 20)],
                [100, 0.73 * (100 - 20)],
                [100, 0]
            ],
            'plasticity' => 'foarte mare',
            'soil' => 'praf',
            'color' => "#CA8504"
            // 'color' => "#FAC515"
        ],
        'ClV' => [
            'points' => [
                [70, 0.73 * (70 - 20)],
                [70, 0.9 * (70 - 8)],
                [100, 0.9 * (100 - 8)],
                [100, 0.73 * (100 - 20)]
            ],
            'plasticity' => 'foarte mare',
            'soil' => 'argilă',
            'color' => "#57534E"
            // 'color' => "#A9A29D"
        ],

    ],

    'soilsBySTAS1243' => [
        "Nisip"                     => [
            "points" => [
                [0, 0],
                [5, 8.6602545],
                [10, 0]
            ],
            "color"  => "#42a2dc",
        ],
        "Nisip prăfos"              => [
            "points" => [
                [10, 0],
                [5, 8.6602545],
                [7.5, 12.990381],
                [50, 12.990381],
                [50, 0]
            ],
            "color"  => "#42a2dc",
        ],
        "Praf nisipos"              => [
            "points" => [
                [50, 0],
                [50, 12.990381],
                [62.5, 12.990381],
                [70, 0]
            ],
            "color"  => "#42a2dc",
        ],
        "Praf"                      => [
            "points" => [
                [70, 0],
                [62.5, 12.990381],
                [92.5, 12.990381],
                [100, 0]
            ],
            "color"  => "#42a2dc",
        ],
        "Nisip argilos"             => [
            "points" => [
                [7.5, 12.990381],
                [15, 25.980762],
                [50, 25.980762],
                [50, 12.990381]
            ],
            "color"  => "#42a2dc",
        ],
        "Praf nisipos argilos"      => [
            "points" => [
                [50, 12.990381],
                [50, 25.980762],
                [55, 25.980762],
                [62.5, 12.990381]
            ],
            "color"  => "#42a2dc",
        ],
        "Praf argilos"              => [
            "points" => [
                [62.5, 12.990381],
                [55, 25.980762],
                [85, 25.980762],
                [92.5, 12.990381]
            ],
            "color"  => "#42a2dc",
        ],
        "Argilă nisipoasă"          => [
            "points" => [
                [15, 25.980762],
                [30, 51.961525],
                [40, 51.961525],
                [52.5, 30.310888],
                [45, 25.980762]
            ],
            "color"  => "#42a2dc",
        ],
        "Argilă prafoasă nisipoasă" => [
            "points" => [
                [45, 25.980762],
                [52.5, 30.310888],
                [55, 25.980762]
            ],
            "color"  => "#42a2dc",
        ],
        "Argilă prafoasă"           => [
            "points" => [
                [55, 25.980762],
                [52.5, 30.310888],
                [75, 43.30127],
                [85, 25.980762]
            ],
            "color"  => "#42a2dc",
        ],
        "Argilă"                    => [
            "points" => [
                [52.5, 30.310888],
                [40, 51.961525],
                [70, 51.961525],
                [75, 43.30127]
            ],
            "color"  => "#42a2dc",
        ],
        "Argilă grasă"              => [
            "points" => [
                [30, 51.961525],
                [70, 51.961525],
                [50, 86.60254]
            ],
            "color"  => "#42a2dc",
        ],
    ],

    'soilsByNP074' => [
        "Argilă grasă" => [
            "points" => [
                [50, 50, 0],
                [100, 0, 0],
                [50, 0, 50],
            ],
            "color" => "#fad7d5",
        ],
        "Argilă" => [
            "points" => [
                [30, 20, 50],
                [50, 20, 30],
                [50, 0, 50],
                [40, 0, 60],
            ],
            "color" => "#fff498",
        ],
        "Argilă prafoasă" => [
            "points" => [
                [25, 20, 55],
                [30, 20, 50],
                [40, 0, 60],
                [25, 0, 75],
            ],
            "color" => "#e7ac7c",
        ],
        "Argilă prafoasă nisipoasă" => [
            "points" => [
                [25, 30, 45],
                [30, 20, 50],
                [25, 20, 55],
            ],
            "color" => "#f5f5f4",
        ],
        "Argila nisipoasă" => [
            "points" => [
                [25, 75, 0],
                [50, 50, 0],
                [50, 20, 30],
                [30, 20, 50],
                [25, 30, 45],
            ],
            "color" => "#c6d291",
        ],
        "Praf" => [
            "points" => [
                [0, 20, 80],
                [10, 20, 70],
                [10, 0, 90],
                [0, 0, 100],
            ],
            "color" => "#ee9479",
        ],
        "Praf argilos" => [
            "points" => [
                [10, 20, 70],
                [25, 20, 55],
                [25, 0, 75],
                [10, 0, 90],
            ],
            "color" => "#e5e3f0",
        ],
        "Praf nisipos argilos" => [
            "points" => [
                [10, 35, 55],
                [25, 27.5, 47.5],
                [25, 20, 55],
                [10, 20, 70],
            ],
            "color" => "#ffe292",
        ],
        "Praf nisipos" => [
            "points" => [
                [0, 40, 60],
                [10, 35, 55],
                [10, 20, 70],
                [0, 20, 80],
            ],
            "color" => "#b2a9d2",
        ],
        "Nisip" => [
            "points" => [
                [0, 100, 0],
                [15, 85, 0],
                [0, 85, 15],
            ],
            "color" => "#83c097",
        ],
        "Nisip argilos" => [
            "points" => [
                [10, 85, 5],
                [15, 85, 0],
                [25, 75, 0],
                [25, 27.5, 47.5],
                [10, 35, 55],
            ],
            "color" => "#f8baa3",
        ],
        "Nisip prăfos" => [
            "points" => [
                [0, 85, 15],
                [10, 85, 5],
                [10, 35, 55],
                [0, 40, 60],
            ],
            "color" => "#d0e7d2",
        ],
    ],


];
