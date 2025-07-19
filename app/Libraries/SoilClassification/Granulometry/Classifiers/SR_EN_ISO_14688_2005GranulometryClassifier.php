<?php

namespace App\Libraries\SoilClassification\Granulometry\Classifiers;

use App\Libraries\PointInPolygon;
use App\Libraries\SoilClassification\Granulometry\GranulometryClassificationResult;
use App\Models\Granulometry;

class SR_EN_ISO_14688_2005GranulometryClassifier extends GranulometryClassifier
{

    public function classify(Granulometry $granulometry): GranulometryClassificationResult
    {
        $errors = $this->granulometryService->validateGranulometry($granulometry);
        if (!empty($errors)) {
            throw new \InvalidArgumentException('Invalid granulometry data: ' . implode(', ', $errors));
        }

        $normalizationApplied = false;
        $normalizationFactor = 1.0;

        $ternaryCoordinates = $this->getTernaryCoordinatesOrder($granulometry);

        if (
            $this->granulometryService->hasVeryCoarseFraction($granulometry)
        ) {
            $total = array_sum($ternaryCoordinates);
            if ($total > 0) {
                $normalizationFactor = 100 / $total;
                $normalizationApplied = true;
            }
        }

        $ternaryCoordinates = array_map(fn($value) => $value * $normalizationFactor, $ternaryCoordinates);
        [$cartesianX, $cartesianY] = $this->geometryService->ternaryToCartesian($ternaryCoordinates);

        $primaryDomain = $this->ternaryDiagramService->findSoilType(
            $cartesianX,
            $cartesianY,
            $this->getTernaryDiagram()
        );

        if (!$primaryDomain) {
            throw new \RuntimeException("Cannot determine soil type");
        }

        if ($this->requiresSecondaryAnalysis($primaryDomain)) {
            $soilType = $this->performSecondaryAnalysis($granulometry, $primaryDomain);
        } else {
            $soilType = $primaryDomain;
        }

        $metadata = [
            'clay' => $granulometry->clay,
            'sand' => $granulometry->sand,
            'silt' => $granulometry->silt,
            'gravel' => $granulometry->gravel,
            'cobble' => $granulometry->cobble,
            'boulder' => $granulometry->boulder,
            'fine' => $granulometry->clay + $granulometry->silt,
            'granulometric_class' => $this->granulometryService->getGranulometricClass($granulometry),

        ];

        if ($normalizationApplied) {
            $metadata['normalization'] = [
                'applied' => true,
                'factor' => round($normalizationFactor, 4),
                'normalized_coordinates' => array_map(fn($coord) => round($coord, 2), $ternaryCoordinates)
            ];
        } else {
            $metadata['normalization'] = ['applied' => false];
        }



        return new GranulometryClassificationResult(
            soilType: $soilType['name'],
            standardInfo: $this->getStandardInfo(),
            metadata: $metadata
        );
    }

    private function requiresSecondaryAnalysis(array $primaryResult): bool
    {
        return isset($primaryResult['soils']) && count($primaryResult['soils']) > 1;
    }

    private function performSecondaryAnalysis(Granulometry $granulometry, array $primaryDomain): array
    {
        $fine = $granulometry->clay + $granulometry->silt;
        $clay = $granulometry->clay;

        foreach ($primaryDomain['soils'] as $soilCode => $soilData) {

            if (!isset($soilData['points'])) {
                continue; // Skip domenii fără puncte secundare
            }

            $result = $this->geometryService->pointInPolygon([$fine, $clay], $soilData['points']);

            if ($result === PointInPolygon::INSIDE || $result === PointInPolygon::ON_BOUNDARY) {
                return [
                    'code' => $soilCode,
                    'name' => $soilData['name'],
                    'points' => $soilData['points']
                ];
            }
        }
        throw new \RuntimeException(
            "Point ({$fine}% fine, {$clay}% clay) not found in any secondary domain for primary domain: " .
                ($primaryDomain['name'])
        );
    }

    protected function buildMetadata(
        Granulometry $granulometry,
        bool $normalizationApplied,
        float $normalizationFactor,
        array $finalCoordinates
    ): array {
        $metadata = [
            'clay' => $granulometry->clay,
            'sand' => $granulometry->sand,
            'silt' => $granulometry->silt,
            'gravel' => $granulometry->gravel,
            'cobble' => $granulometry->cobble,
            'boulder' => $granulometry->boulder,
            'fine' => $granulometry->clay + $granulometry->silt,
            'granulometric_class' => $this->granulometryService->getGranulometricClass($granulometry),

        ];

        if ($normalizationApplied) {
            $metadata['normalization'] = [
                'applied' => true,
                'factor' => round($normalizationFactor, 4),
                'normalized_coordinates' => array_map(fn($coord) => round($coord, 2), $finalCoordinates)
            ];
        } else {
            $metadata['normalization'] = ['applied' => false];
        }

        return $metadata;
    }

    protected function getClassificationMethod(): string
    {
        return 'sr_en_iso_14688_2_two_stage_analysis';
    }

    protected function getTernaryCoordinatesOrder(Granulometry $granulometry): array
    {
        $clay = $granulometry->clay;
        $silt = $granulometry->silt;
        $sand = $granulometry->sand;
        $fine = $clay + $silt;
        $gravel = $granulometry->gravel;

        return [
            $fine,
            $sand,
            $gravel
        ];
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
