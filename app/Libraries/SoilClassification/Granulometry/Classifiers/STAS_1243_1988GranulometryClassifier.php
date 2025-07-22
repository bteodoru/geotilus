<?php

namespace App\Libraries\SoilClassification\Granulometry\Classifiers;

use App\Libraries\SoilClassification\Granulometry\GranulometryClassifier;
use App\Models\Granulometry;

class STAS_1243_1988GranulometryClassifier extends GranulometryClassifier
{

    protected string $systemCode = 'stas_1243_1988';


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
}
