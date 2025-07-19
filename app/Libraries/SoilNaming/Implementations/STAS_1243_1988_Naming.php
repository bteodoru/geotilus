<?php

namespace App\Libraries\SoilNaming\Implementations;

// use App\Libraries\SoilNaming\SoilNamingInterface;
use App\Libraries\SoilNaming\SoilNamingResult;
use App\Libraries\SoilClassification\Granulometry\GranulometryClassificationFactory;
use App\Models\Sample;

class STAS_1243_1988_Naming //implements SoilNamingInterface
{
    public function __construct(
        private GranulometryClassificationFactory $granulometryFactory
    ) {}

    public function nameSoil(Sample $sample): SoilNamingResult
    {
        $granulometryClassifier = $this->granulometryFactory->create('stas_1243_1988');
        $granulometryResult = $granulometryClassifier->classify($sample->granulometry);

        return new SoilNamingResult(
            soilName: $granulometryResult->getSoilType(),
            namingMethod: 'granulometry_only',
            standardInfo: $granulometryClassifier->getStandardInfo(),
            granulometryResult: $granulometryResult,
            plasticityResult: null,
            // metadata: [
            //     'system' => 'stas_1243_1988',
            //     'uses_plasticity' => false
            // ]
        );
    }



    // public function isApplicable(Sample $sample): bool
    // {
    //     return !is_null($sample->granulometry);
    // }

    // public function getRequiredFields(): array
    // {
    //     return ['granulometry'];
    // }

    // public function getClassificationMethods(): array
    // {
    //     return ['granulometry'];
    // }
}
