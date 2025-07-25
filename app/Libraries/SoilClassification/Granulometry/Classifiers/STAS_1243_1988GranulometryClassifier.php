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

    public function getGradationInformation(Granulometry $granulometry): ?string
    {
        $cu = $granulometry->cu;

        if ($cu < 5) {
            return 'foarte uniformă';
        } elseif ($cu <= 15) {
            return 'uniformă';
        } else {
            return 'neuniformă';
        }
    }
}
