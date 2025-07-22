<?php

namespace App\Libraries\SoilClassification\Granulometry\Classifiers;

use App\Libraries\SoilClassification\Granulometry\GranulometryClassifier;
use App\Models\Granulometry;

class SR_EN_ISO_14688_2005GranulometryClassifier extends GranulometryClassifier
{
    protected string $systemCode = 'sr_en_iso_14688_2005';


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
}
