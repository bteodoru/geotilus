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
    protected string $systemCode = 'sr_en_iso_14688_2018';

    protected function getClassificationMethod(): string
    {
        return 'sr_en_iso_14688_2_two_stage_analysis';
    }

    public function classifyFineFraction(Sample $sample) //: GranulometryClassificationResult
    {
        // dd($sample->granulometry);
        $plasticityFactory = new PlasticityClassificationFactory();
        $plasticityClassifier = $plasticityFactory->create($this->systemCode);
        $soil = $plasticityClassifier->classify($sample->plasticity);

        return new GranulometricFraction(
            name: $this->serviceContainer->granulometry()->getFractionName($soil->getSoilType()),
            label: $soil->getSoilType(),
            components: ['clay' => $sample->granulometry->clay, 'silt' => $sample->granulometry->silt],
            source: 'casagrande_chart',
            class: 'fine'
        );
    }

    public function getGradationInformation(Granulometry $granulometry): ?string
    {
        $cu = $granulometry->cu;

        if ($cu === null) {
            return null;
        }

        if ($cu < 3) {
            return 'foarte uniformă';
        } elseif ($cu <= 6) {
            return 'uniformă';
        } elseif ($cu <= 15) {
            return 'cu uniformitate medie';
        } else {
            return 'neuniformă';
        }
    }
}
