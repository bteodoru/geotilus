<?php

namespace App\Libraries\SoilClassification\Granulometry\Classifiers;

use App\Libraries\PointInPolygon;
use App\Libraries\SoilClassification\Granulometry\GranulometryClassificationResult;
use App\Libraries\SoilClassification\Granulometry\GranulometryClassifier;
use App\Models\Granulometry;

class SR_EN_ISO_14688_2005GranulometryClassifier extends GranulometryClassifier
{
    protected string $systemCode = 'sr_en_iso_14688_2005';


    public function classify(Granulometry $granulometry): GranulometryClassificationResult
    {
        $ternaryData = $this->processTernaryData($granulometry);
        $soil = $this->determineSoilType($ternaryData['coordinates']);
        $finalSoil = $this->analyze($granulometry, $soil, $ternaryData['normalizationFactor']);

        $soilName = $this->serviceContainer->soilName()
            ->build($finalSoil['name'], $granulometry, $this->getRequiredTernaryFractions(), [50, 25]);

        return new GranulometryClassificationResult(
            soilType: $soilName,
            standardInfo: $this->getSystemInfo(),
            metadata: $this->buildMetadata(
                $granulometry,
                $ternaryData['normalizationApplied'],
                $ternaryData['normalizationFactor'],
                $ternaryData['coordinates']
            )
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
