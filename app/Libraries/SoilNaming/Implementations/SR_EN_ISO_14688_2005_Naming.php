<?php

namespace App\Libraries\SoilNaming\Implementations;

// use App\Libraries\SoilNaming\SoilNamingInterface;
use App\Libraries\SoilNaming\SoilNamingResult;
use App\Libraries\SoilClassification\Granulometry\GranulometryClassificationFactory;
use App\Models\Sample;


class SR_EN_ISO_14688_2005_Naming //implements SoilNamingInterface
{
    public function __construct(
        private GranulometryClassificationFactory $granulometryFactory
    ) {}

    public function nameSoil(Sample $sample): SoilNamingResult
    {
        $granulometryClassifier = $this->granulometryFactory->create('sr_en_iso_14688_2005');
        $granulometryResult = $granulometryClassifier->classify($sample->granulometry);

        return new SoilNamingResult(
            soilName: $granulometryResult->getSoilType(),
            namingMethod: 'granulometry_only',
            standardInfo: $granulometryClassifier->getStandardInfo(),
            granulometryResult: $granulometryResult,
            plasticityResult: null,

            // finalSoilName: $granulometryResult->soilType,
            // classificationMethod: 'granulometry_two_stage',
            // standardInfo: $this->getStandardInfo(),
            // granulometryResult: $granulometryResult,
            // plasticityResult: null,
            // confidence: $granulometryResult->confidence,
            // classificationCertainty: $granulometryResult->classificationCertainty,
            // metadata: [
            //     'system' => 'sr_en_iso_14688_2005',
            //     'uses_plasticity' => false,
            //     'two_stage_analysis' => true
            // ]
        );
    }

    // public function getStandardInfo(): array
    // {
    //     return [
    //         'code' => 'sr_en_iso_14688_2005',
    //         'name' => 'SR EN ISO 14688-2:2005',
    //         'country' => 'RO',
    //         'description' => 'Standard european pentru denumirea pământurilor - granulometrie în două etape',
    //         'requires_plasticity' => false
    //     ];
    // }

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
    //     return ['granulometry_two_stage'];
    // }
}
