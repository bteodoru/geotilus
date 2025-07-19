<?php

namespace App\Libraries\SoilNaming;

use App\Libraries\SoilClassification\Granulometry\GranulometryClassificationFactory;
use App\Libraries\SoilClassification\Plasticity\PlasticityClassificationFactory;
use App\Libraries\SoilClassification\Services\CasagrandeChartService;
use App\Libraries\SoilClassification\Services\GranulometryService;
use App\Models\Sample;

class SoilNamingResolver
{
    // public function __construct(
    //     private GranulometryClassificationFactory $granulometryFactory,
    //     private PlasticityClassificationFactory $plasticityFactory
    // ) {}

    public function classify(Sample $sample, string $standard): array
    {
        $granulometryService = new GranulometryService();
        $granulometryFactory = new GranulometryClassificationFactory($granulometryService);
        // dd(in_array($standard, array_keys($granulometryFactory->getAvailableStandards())));
        if (in_array($standard, array_keys($granulometryFactory->getAvailableStandards()))) {
            $granulometryClassifier = $granulometryFactory->create($standard);
            $granulometryResult =  $granulometryClassifier->classify($sample->granulometry);
        }

        if ($standard === 'sr_en_iso_14688_2018' && $sample->plasticity) {
            $chartDiagrmService = app(CasagrandeChartService::class);
            $casagrandeFactory = new PlasticityClassificationFactory();
            $plasticityClassifier = $casagrandeFactory->create('casagrande');
            $plasticityResult = $plasticityClassifier->classify($sample->plasticity);
            return  $plasticityResult->toArray();
        }

        return ['granulometry_result' => $granulometryResult];
    }
}
