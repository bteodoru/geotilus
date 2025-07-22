<?php

return [
    'metadata' => [
        'system_code' => 'np_074_2022',
        'axes_order' => ['silt', 'clay', 'sand'],
        'description' => 'Diagrama ternară pentru clasificarea granulometrică recomandată de NP 074/2022, normativul pentru întocmirea documentațiilor geotehnice pentru construcții.'
    ],
    'domains' => [
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
];
