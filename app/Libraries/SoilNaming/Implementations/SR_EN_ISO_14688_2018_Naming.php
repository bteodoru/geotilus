<?php

namespace App\Libraries\SoilNaming\Implementations;

use App\Libraries\SoilNaming\SoilNamingInterface;
use App\Libraries\SoilNaming\SoilNamingResult;
use App\Libraries\SoilClassification\Granulometry\GranulometryClassificationFactory;
use App\Libraries\SoilClassification\Plasticity\PlasticityClassificationFactory;
use App\Models\Sample;


class SR_EN_ISO_14688_2018_Naming implements SoilNamingInterface
{
    public function __construct(
        private GranulometryClassificationFactory $granulometryFactory,
        private PlasticityClassificationFactory $plasticityFactory
    ) {}

    public function nameSoil(Sample $sample): SoilNamingResult
    {
        $granulometryClassifier = $this->granulometryFactory->create('sr_en_iso_14688_2005');
        $granulometryResult = $granulometryClassifier->classify($sample->granulometry);

        $primaryCategory = $granulometryResult->metadata['primary_category'] ?? 'unknown';

        // Pentru pământuri fine cu plasticitate
        if ($primaryCategory === 'fine' && $sample->plasticity) {
            try {
                $plasticityClassifier = $this->plasticityFactory->create('casagrande');
                $plasticityResult = $plasticityClassifier->classify($sample->plasticity);

                $finalName = $this->combineSoilNames($granulometryResult, $plasticityResult);
                // $combinedCertainty = $this->combineCertainties(
                //     $granulometryResult->classificationCertainty,
                //     $plasticityResult->classificationCertainty
                // );

                return new SoilNamingResult(
                    soilName: $finalName,
                    namingMethod: 'granulometry_plus_plasticity',
                    standardInfo: $this->getStandardInfo(),
                    granulometryResult: $granulometryResult,
                    plasticityResult: $plasticityResult,
                    // confidence: min($granulometryResult->confidence, $plasticityResult->confidence),
                    // classificationCertainty: $combinedCertainty,
                    metadata: [
                        'system' => 'sr_en_iso_14688_2018',
                        'uses_plasticity' => true,
                        'combined_classification' => true
                    ]
                );
            } catch (\Exception $e) {
                // Fallback la doar granulometrie
                return new SoilNamingResult(
                    finalSoilName: $granulometryResult->getSoilType(),
                    namingMethod: 'granulometry_fallback',
                    standardInfo: $this->getStandardInfo(),
                    granulometryResult: $granulometryResult,
                    plasticityResult: null,
                    // confidence: $granulometryResult->confidence,
                    // classificationCertainty: $granulometryResult->classificationCertainty,
                    metadata: [
                        'system' => 'sr_en_iso_14688_2018',
                        'uses_plasticity' => false,
                        'plasticity_error' => $e->getMessage(),
                        'fallback_reason' => 'plasticity_classification_failed'
                    ]
                );
            }
        }

        // Pentru pământuri groasă sau fără plasticitate
        return new SoilNamingResult(
            soilName: $granulometryResult->getSoilType(),
            namingMethod: 'granulometry_only',
            standardInfo: $this->getStandardInfo(),
            granulometryResult: $granulometryResult,
            plasticityResult: null,
            // confidence: $granulometryResult->confidence,
            // classificationCertainty: $granulometryResult->classificationCertainty,
            metadata: [
                'system' => 'sr_en_iso_14688_2018',
                'uses_plasticity' => false,
                'soil_category' => $primaryCategory
            ]
        );
    }

    private function combineSoilNames($granulometryResult, $plasticityResult): string
    {
        return match ($plasticityResult->plasticityClass) {
            'CL' => 'Argilă cu plasticitate scăzută',
            'CH' => 'Argilă cu plasticitate înaltă',
            'ML' => 'Praf cu plasticitate scăzută',
            'MH' => 'Praf cu plasticitate înaltă',
            default => $granulometryResult->soilType . ' (plasticitate: ' . $plasticityResult->plasticityClass . ')'
        };
    }

    private function combineCertainties(string $granulometryCertainty, string $plasticityCertainty): string
    {
        return ($granulometryCertainty === 'ambiguous' || $plasticityCertainty === 'ambiguous')
            ? 'ambiguous'
            : 'definite';
    }

    public function getStandardInfo(): array
    {
        return [
            'code' => 'sr_en_iso_14688_2018',
            'name' => 'SR EN ISO 14688-2:2018',
            'country' => 'RO',
            'description' => 'Standard european pentru denumirea pământurilor - granulometrie + plasticitate',
            'requires_plasticity' => true
        ];
    }

    public function isApplicable(Sample $sample): bool
    {
        // Necesită granulometrie, plasticitatea e opțională
        return !is_null($sample->granulometry);
    }

    public function getRequiredFields(): array
    {
        return ['granulometry', 'plasticity (for fine soils)'];
    }

    public function getClassificationMethods(): array
    {
        return ['granulometry_two_stage', 'plasticity_casagrande'];
    }
}
