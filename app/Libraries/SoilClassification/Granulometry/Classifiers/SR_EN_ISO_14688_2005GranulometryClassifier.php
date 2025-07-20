<?php

namespace App\Libraries\SoilClassification\Granulometry\Classifiers;

use App\Libraries\SoilClassification\Granulometry\GranulometryClassifier;
use App\Models\Granulometry;

class SR_EN_ISO_14688_2005GranulometryClassifier extends GranulometryClassifier
{


    protected function getClassificationMethod(): string
    {
        return 'sr_en_iso_14688_2_two_stage_analysis';
    }

    protected function getRequiredTernaryFractions(): array
    {
        return ['silt', 'clay', 'sand', 'gravel'];
    }

    protected function getCoordinateValues(Granulometry $granulometry): array
    {

        $fractions = $this->granulometryService->extractFractions($granulometry, $this->getRequiredTernaryFractions());
        $coordinates = [
            $fractions['silt'] + $fractions['clay'],
            $fractions['sand'],
            $fractions['gravel']
        ];

        return $coordinates;
    }

    public function getStandardInfo(): array
    {
        return [
            'code' => 'sr_en_14688_2005',
            'name' => 'SR EN ISO 14688-2',
            'version' => '2005',
            'country' => 'RO',
            'description' => 'Investigaţii şi încercări geotehnice. Identificarea şi clasificarea pământurilor. Partea 2: Principii pentru o clasificare',
        ];
    }

    protected function getTernaryDiagram(): array
    {
        $si = [
            "points" => [
                [40, 0],
                [40, 8],
                [15, 3],
                [15, 0]
            ],
            "name" => "prăfos",
        ];

        $cl = [
            "points" => [
                [40, 8],
                [40, 40],
                [15, 15],
                [15, 3]
            ],
            "name" => "argilos",
        ];

        $Si = [
            "points" => [
                [100, 0],
                [100, 10],
                [40, 4],
                [40, 0]
            ],
            "name" => "Praf",
        ];

        $Cl = [
            "points" => [
                [100, 40],
                [100, 100],
                [40, 40],
                [40, 16]
            ],
            "name" => "Argilă",
        ];

        $clSi = [
            "points" => [
                [100, 10],
                [100, 20],
                [40, 8],
                [40, 4]
            ],
            "name" => "Praf argilos",
        ];

        $siCL = [
            "points" => [
                [100, 20],
                [100, 40],
                [40, 16],
                [40, 8]
            ],
            "name" => "Argilă prăfoasă",
        ];

        return [
            [
                "points" => [ //[fine, Sa, Gr]
                    [80, 20, 0],
                    [40, 60, 0],
                    [40, 40, 20],
                    [60, 20, 20]
                ],
                "soils" => [
                    "saSi" => [
                        "points" => $Si["points"],
                        "name" => "Praf nisipos",
                    ],
                    "saclSi" => [
                        "points" => $clSi["points"],
                        "name" => "Praf argilos nisipos",
                    ],
                    "sasiCl" => [
                        "points" => $siCL["points"],
                        "name" => "Argilă prăfoasă nisipoasă",
                    ],
                    "saCl" => [
                        "points" => $Cl["points"],
                        "name" => "Argilă nisipoasă",
                    ],
                ],
                "color" => "#42a2dc",
            ],
            [
                "points" => [
                    [80, 0, 20],
                    [60, 20, 20],
                    [40, 20, 40],
                    [40, 0, 60]
                ],
                "soils" => [
                    "grSi" => [
                        "points" => $Si["points"],
                        "name" => "Praf cu pietriș",
                    ],
                    "grclSi" => [
                        "points" => $clSi["points"],
                        "name" => "Praf argilos cu pietriș",
                    ],
                    "grsiCl" => [
                        "points" => $siCL["points"],
                        "name" => "Argilă prăfoasă cu pietriș",
                    ],
                    "grCl" => [
                        "points" => $Cl["points"],
                        "name" => "Argilă cu pietriș",
                    ],
                ],
                "color" => "#42a2dc",
            ],
            [
                "points" => [
                    [100, 0, 0],
                    [80, 20, 0],
                    [60, 20, 20],
                    [80, 0, 20]
                ],
                "soils" => [
                    "Si" => [
                        "points" => $Si["points"],
                        "name" => "Praf",
                    ],
                    "clSi" => [
                        "points" => $clSi["points"],
                        "name" => "Praf argilos",
                    ],
                    "siCl" => [
                        "points" => $siCL["points"],
                        "name" => "Argilă prăfoasă",
                    ],
                    "Cl" => [
                        "points" => $Cl["points"],
                        "name" => "Argilă",
                    ],
                ],
                "color" => "#42a2dc",
            ],
            [
                "points" => [
                    [40, 0, 60],
                    [40, 20, 40],
                    [15, 20, 65],
                    [15, 0, 85]
                ],
                "soils" => [
                    "siGr" => [
                        "points" => $si["points"],
                        "name" => "Pietriș prăfos",
                    ],
                    "clGr" => [
                        "points" => $cl["points"],
                        "name" => "Pietriș argilos",
                    ]
                ],
                "color" => "#42a2dc",

            ],
            [
                "points" => [
                    [40, 20, 40],
                    [20, 40, 40],
                    [15, 42.5, 42.5],
                    [15, 20, 65]
                ],
                "soils" => [
                    "sasiGr" => [
                        "points" => $si["points"],
                        "name" => "Pietriș prăfos nisipos",
                    ],
                    "saclGr" => [
                        "points" => $cl["points"],
                        "name" => "Pietriș argilos nisipos",
                    ]
                ],
                "color" => "#42a2dc",
            ],
            [
                "points" => [
                    [40, 40, 20],
                    [15, 65, 20],
                    [15, 42.5, 42.5],
                    [20, 40, 40]
                ],
                "soils" => [
                    "grsiSa" => [
                        "points" => $si["points"],
                        "name" => "Nisip prăfos cu pietriș",
                    ],
                    "grclSa" => [
                        "points" => $cl["points"],
                        "name" => "Nisip argilos cu pietriș",
                    ]
                ],
                "color" => "#42a2dc",
            ],
            [
                "points" => [
                    [40, 60, 0],
                    [15, 85, 0],
                    [15, 65, 20],
                    [40, 40, 20]
                ],
                "soils" => [
                    "siSa" => [
                        "points" => $si["points"],
                        "name" => "Nisip prăfos",
                    ],
                    "clSa" => [
                        "points" => $cl["points"],
                        "name" => "Nisip argilos",
                    ]
                ],
                "color" => "#42a2dc",
            ],
            [
                "points" => [
                    [15, 85, 0],
                    [0, 100, 0],
                    [0, 80, 20],
                    [15, 65, 20]
                ],
                "soils" => [
                    "Sa" => [
                        // "points" => [],
                        "name" => "Nisip",
                    ],
                ],
                "color" => "#42a2dc",
            ],
            [
                "points" => [
                    [15, 65, 20],
                    [0, 80, 20],
                    [0, 50, 50],
                    [15, 42.5, 42.5]
                ],
                "soils" => [
                    "grSa" => [
                        // "points" => [],
                        "name" => "Nisip cu pietriș",
                    ],
                ],
                "color" => "#42a2dc",
            ],
            [
                "points" => [
                    [15, 42.5, 42.5],
                    [0, 50, 50],
                    [0, 20, 80],
                    [15, 20, 65]
                ],
                "soils" => [
                    "saGr" => [
                        // "points" => [],
                        "name" => "Pietriș nisipos",
                    ],
                ],
                "color" => "#42a2dc",
            ],
            [
                "points" => [
                    [15, 20, 65],
                    [0, 20, 80],
                    [0, 0, 100],
                    [15, 0, 85]
                ],
                "soils" => [
                    "Gr" => [
                        // "points" => [],
                        "name" => "Pietriș",
                    ],
                ],
                "color" => "#42a2dc",
            ],
            [
                "points" => [
                    [60, 20, 20],
                    [40, 40, 20],
                    [40, 30, 30]
                ],
                "soils" => [
                    "grsaSi" => [
                        "points" => $Si["points"],
                        "name" => "Praf nisipos cu pietriș",
                    ],
                    "grsaCl" => [
                        "points" => $Cl["points"],
                        "name" => "Argilă nisipoasă cu pietriș",
                    ],
                ],
                "color" => "#42a2dc",
            ],
            [
                "points" => [
                    [40, 40, 20],
                    [20, 40, 40],
                    [40, 30, 30]
                ],
                "soils" => [
                    "grsasiS" => [
                        "points" => $si["points"],
                        "name" => "Pământ prăfos nisipos, cu pietriș",
                    ],
                    "grsaclS" => [
                        "points" => $cl["points"],
                        "name" => "Pământ argilos nisipos, cu pietriș",
                    ],
                ],
                "color" => "#42a2dc",
            ],
            [
                "points" => [
                    [40, 30, 30],
                    [20, 40, 40],
                    [40, 20, 40],
                ],
                "soils" => [
                    "sagrsiS" => [
                        "points" => $si["points"],
                        "name" => "Pământ prăfos, cu pietriș, nisipos",
                    ],
                    "sagrclS" => [
                        "points" => $cl["points"],
                        "name" => "Pământ argilos, cu pietriș, nisipos",
                    ],
                ],
                "color" => "#42a2dc",
            ],
            [
                "points" => [
                    [60, 20, 20],
                    [40, 30, 30],
                    [40, 20, 40],
                ],
                "soils" => [
                    "sagrSi" => [
                        "points" => $Si["points"],
                        "name" => "Praf cu pietriș, nisipos",
                    ],
                    "sagrCl" => [
                        "points" => $Cl["points"],
                        "name" => "Argilă cu pietriș, nisipoasă",
                    ],
                ],
                "color" => "#42a2dc",
            ],

        ];
    }
}
