<?php

namespace App\Libraries\SoilClassification\Plasticity\Classifiers;

use App\Libraries\SoilClassification\Plasticity\PlasticityClassifier;

class SR_EN_ISO_14688_2005GPlasticityClassifier extends PlasticityClassifier
{
    protected string $systemCode = 'sr_en_iso_14688_2005';

    protected function getClassificationMethod(): string
    {
        return 'sr_en_iso_14688_2_two_stage_analysis';
    }
}
