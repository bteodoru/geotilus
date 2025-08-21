<?php

namespace App\Libraries\SoilClassification\Plasticity\Classifiers;

use App\Libraries\SoilClassification\Plasticity\PlasticityClassifier;

class STAS_1243_1988PlasticityClassifier extends PlasticityClassifier
{
    protected string $systemCode = 'stas_1243_1988';

    protected function getClassificationMethod(): string
    {
        return 'stas_1243_1988_two_stage_analysis';
    }

    public function classify($plasticity) //: PlasticityClassificationResult
    {
        $liquidLimit = $plasticity->liquid_limit;
        $plasticlimit = $plasticity->plastic_limit;
        $plasticityIndex = $liquidLimit - $plasticlimit;

        $classification = '';

        if ($plasticityIndex === 0) {
            $classification = 'neplastic';
        } elseif ($plasticityIndex <= 10) {
            $classification = 'plasticitate redusă';
        } elseif ($plasticityIndex <= 20) {
            $classification = 'plasticitate medie';
        } elseif ($plasticityIndex <= 35) {
            $classification = 'plasticitate mare';
        } else {
            $classification = 'foarte mare';
        }

        return $classification;
    }
}
