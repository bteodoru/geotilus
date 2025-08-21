<?php

namespace App\Libraries\SoilClassification\Plasticity\Classifiers;

use App\Libraries\SoilClassification\Contracts\PlasticityClassifierInterface;
use App\Libraries\SoilClassification\Plasticity\Classifiers\CasagrandeClassifier;
use App\Libraries\SoilClassification\Plasticity\PlasticityClassificationResult;
use App\Libraries\SoilClassification\Plasticity\PlasticityClassifier;
use App\Libraries\SoilClassification\Services\CasagrandeChartService;

class SR_EN_ISO_14688_2018GPlasticityClassifier extends PlasticityClassifier
{
    protected string $systemCode = 'sr_en_iso_14688_2018';

    protected function getClassificationMethod(): string
    {
        return 'sr_en_iso_14688_2_two_stage_analysis';
    }

    public function classify($plasticity): PlasticityClassificationResult
    {
        $classifier = new CasagrandeClassifier(
            app(CasagrandeChartService::class)
        );
        return $classifier->classify($plasticity);
    }
}
