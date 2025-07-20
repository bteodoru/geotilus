<?php

namespace App\Libraries\SoilClassification\Granulometry\Classifiers;

use App\Libraries\SoilClassification\Granulometry\GranulometryClassifier;
use App\Models\Granulometry;

class STAS_1243_1988GranulometryClassifier extends GranulometryClassifier
{
    protected function getClassificationMethod(): string
    {
        return 'stas_single_ternary_diagram';
    }

    protected function getRequiredTernaryFractions(): array
    {
        return ['silt', 'clay', 'sand'];
    }

    protected function getCoordinateValues(Granulometry $granulometry): array
    {
        $fractions = $this->granulometryService->extractFractions($granulometry, $this->getRequiredTernaryFractions());
        $coordinates = [
            $fractions['silt'],
            $fractions['clay'],
            $fractions['sand']
        ];

        return $coordinates;
    }

    public function getStandardInfo(): array
    {
        return [
            'code' => 'stas_1243_1988',
            'name' => 'STAS 1243',
            'version' => '1988',
            'country' => 'RO',
            'description' => 'Standard românesc pentru clasificarea pământurilor'
        ];
    }

    protected function getTernaryDiagram(): array
    {
        return [
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
        ];
    }
}
