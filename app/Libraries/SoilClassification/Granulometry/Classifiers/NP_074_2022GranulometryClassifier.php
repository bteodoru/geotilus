<?php

namespace App\Libraries\SoilClassification\Granulometry\Classifiers;

use App\Libraries\SoilClassification\Granulometry\GranulometryClassifier;
use App\Models\Granulometry;

class NP_074_2022GranulometryClassifier extends GranulometryClassifier
{

    protected string $systemCode = 'np_074_2022';

    protected function getClassificationMethod(): string
    {
        return 'stas_single_ternary_diagram';
    }
}
