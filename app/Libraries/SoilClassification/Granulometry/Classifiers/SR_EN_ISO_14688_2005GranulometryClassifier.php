<?php

namespace App\Libraries\SoilClassification\Granulometry\Classifiers;

use App\Libraries\DTOs\GranulometricFraction;
use App\Libraries\PointInPolygon;
use App\Libraries\SoilClassification\Granulometry\GranulometryClassificationResult;
use App\Libraries\SoilClassification\Granulometry\GranulometryClassifier;
use App\Models\Granulometry;
use App\Models\Sample;

class SR_EN_ISO_14688_2005GranulometryClassifier extends GranulometryClassifier
{
    protected string $systemCode = 'sr_en_iso_14688_2005';
    protected $thresholds = [50, 25];



    // public function classify_(Granulometry $granulometry) //: GranulometryClassificationResult
    // {
    //     $ternaryData = $this->processTernaryData($granulometry);
    //     $soil = $this->determineSoilType($ternaryData['coordinates']);
    //     $finalSoil = $this->analyze($granulometry, $soil, $ternaryData['normalizationFactor']);
    //     // $soilName = $this->serviceContainer->soilName()
    //     //     ->build($finalSoil['name'], $granulometry, $this->getRequiredTernaryFractions(), $this->thresholds);

    //     return new GranulometryClassificationResult(
    //         // primaryClassification: $soilName,
    //         // primaryClassification: $finalSoil['name'],
    //         // classificationSystem: $this->getSystemInfo(),
    //         classificationSystem: $this->systemCode,
    //         granulometry: $granulometry,
    //         plasticity: [],
    //         gradingParameters: [],
    //         fractions: $finalSoil,
    //         // soilType: $soilName,
    //         // standardInfo: $this->getSystemInfo(),
    //         metadata: $this->buildMetadata(
    //             $granulometry,
    //             $ternaryData['normalizationApplied'],
    //             $ternaryData['normalizationFactor'],
    //             $ternaryData['coordinates']
    //         )
    //     );
    // }
    public function classifyByTernaryDiagram(Sample $sample): GranulometricFraction
    {
        $granulometry = $sample->granulometry;
        $ternaryData = $this->processTernaryData($granulometry);
        $soil = $this->determineSoilType($ternaryData['coordinates']);
        $finalSoil = $this->analyze($granulometry, $soil, $ternaryData['normalizationFactor']);
        // $soilName = $this->serviceContainer->soilName()
        //     ->build($finalSoil['name'], $granulometry, $this->getRequiredTernaryFractions(), $this->thresholds);
        // return $finalSoil['name'];
        // dd($ternaryData);

        $components = array_map(function ($element) use ($ternaryData) {
            return $element / $ternaryData['normalizationFactor'];
        }, $ternaryData['coordinates']);

        return new GranulometricFraction(
            name: $finalSoil['name'],
            symbol: $soil['name'],
            components: $components,
            source: 'ternary_diagram',
            label: $soil['name']
        );
    }

    private function analyze(Granulometry $granulometry, array $soil, float $normalizationFactor): array
    {
        if ($this->requiresSecondaryAnalysis($soil)) {
            return $this->runSecondaryAnalysis($granulometry, $soil, $normalizationFactor);
        }


        return $soil['soils'][array_key_first($soil['soils'])] ?? $soil;
    }

    private function requiresSecondaryAnalysis(array $primaryResult): bool
    {
        return isset($primaryResult['soils']) && count($primaryResult['soils']) > 1;
    }

    public function runSecondaryAnalysis(Granulometry $granulometry, array $primarySoil, float $normalizationFactor): array
    {
        $fine = $normalizationFactor * ($granulometry->clay  + $granulometry->silt);
        $clay = $normalizationFactor * $granulometry->clay;

        foreach ($primarySoil['soils'] as $soilCode => $soilData) {
            if (!isset($soilData['points'])) {
                continue;
            }

            $result = $this->serviceContainer->geometry()->pointInPolygon([$fine, $clay], $soilData['points']);

            if ($result === PointInPolygon::INSIDE || $result === PointInPolygon::ON_BOUNDARY) {
                return [
                    'code' => $soilCode,
                    'name' => $soilData['name'],
                    'points' => $soilData['points']
                ];
            }
        }

        throw new \RuntimeException(
            "Point ({$fine}% fine, {$clay}% clay) not found in any secondary domain for primary domain: " .
                ($primarySoil['name'])
        );
    }

    protected function getClassificationMethod(): string
    {
        return 'sr_en_iso_14688_2_two_stage_analysis';
    }

    public function getGradationInformation(Granulometry $granulometry): ?string
    {
        return '';
    }
}
