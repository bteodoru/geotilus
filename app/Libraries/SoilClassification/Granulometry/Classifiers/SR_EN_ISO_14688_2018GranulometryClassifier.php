<?php

namespace App\Libraries\SoilClassification\Granulometry\Classifiers;

use App\Libraries\DTOs\GranulometricFraction;
use App\Libraries\PointInPolygon;
use App\Libraries\SoilClassification\Granulometry\GranulometryClassificationResult;
use App\Libraries\SoilClassification\Granulometry\GranulometryClassifier;
use App\Libraries\SoilClassification\Plasticity\PlasticityClassificationFactory;
use App\Models\Granulometry;
use App\Models\Sample;

class SR_EN_ISO_14688_2018GranulometryClassifier extends GranulometryClassifier
{
    // protected string $systemCode = 'sr_en_iso_14688_2018';

    protected function getClassificationMethod(): string
    {
        return 'sr_en_iso_14688_2_two_stage_analysis';
    }

    public function classifyFineFraction(Sample $sample) //: GranulometryClassificationResult
    {
        // dd($sample->granulometry);
        $plasticityFactory = new PlasticityClassificationFactory();
        $plasticityClassifier = $plasticityFactory->create($this->getSystemCode());
        $soil = $plasticityClassifier->classify($sample->plasticity);

        return new GranulometricFraction(
            name: $this->serviceContainer->granulometry()->getFractionName($soil->getSoilType()),
            label: $soil->getSoilType(),
            components: ['clay' => $sample->granulometry->clay, 'silt' => $sample->granulometry->silt],
            source: 'casagrande_chart',
            class: 'fine',


        );
    }

    public function getGradation(Granulometry $granulometry): ?string
    {
        $cu = $granulometry->cu;
        $cc = $granulometry->cc;

        if ($cu === null || $cc === null) {
            return null;
        }

        if ($cu < 3 && $cc < 1) {
            return 'foarte uniform';
        } elseif ($cu <= 6 && $cc < 1) {
            return 'uniform';
        } elseif ($cu <= 15 && $cc >= 1 && $cc <= 3) {
            return 'cu uniformitate medie';
        } else {
            return 'neuniform';
        }
    }
}
