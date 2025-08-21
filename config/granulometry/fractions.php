<?php
// config/granulometry/fractions.php
return [
    'simple_fractions' => [
        'clay' => [
            'name' => 'Argilă',
            'adjective' => ['argiloasă', 'argilos'],
            'gender' => 0,
            'class' => 'fine',
            'symbol' => 'Cl',
        ],
        'silt' => [
            'name' => 'Praf',
            'adjective' => ['prăfoasă', 'prăfos'],
            'gender' => 1,
            'class' => 'fine',
            'symbol' => 'Si',
        ],
        'sand' => [
            'name' => 'Nisip',
            'adjective' => ['nisipoasă', 'nisipos'],
            'gender' => 1,
            'class' => 'coarse',
            'symbol' => 'Sa',
        ],
        'gravel' => [
            'name' => 'Pietriș',
            'adjective' => ['cu pietriș', 'cu pietriș'],
            'gender' => 1,
            'class' => 'coarse',
            'symbol' => 'Gr',
        ],
        'cobble' => [
            'name' => 'Bolovăniș',
            'adjective' => ['cu bolovăniș', 'cu bolovăniș'],
            'gender' => 1,
            'class' => 'very_coarse',
            'symbol' => 'Co',
        ],
        'boulder' => [
            'name' => 'Blocuri',
            'adjective' => ['cu blocuri', 'cu blocuri'],
            'gender' => 1,
            'class' => 'very_coarse',
            'symbol' => 'Bo',
        ],
    ],
    'composite_fractions' => [
        'fine' => [
            'components' => ['clay', 'silt'],
            'name' => 'Fracțiuni fine',
        ],
        'coarse' => [
            'components' => ['sand', 'gravel'],
            'name' => 'Fracțiuni grosiere',
        ],
        'very_coarse' => [
            'components' => ['cobble', 'boulder'],
            'name' => 'Fracțiuni foarte grosiere',
        ],
    ],

    'aliases' => [
        'fines' => ['clay', 'silt'],
        'coarses' => ['sand', 'gravel', 'cobble', 'boulder'],
    ]
];
