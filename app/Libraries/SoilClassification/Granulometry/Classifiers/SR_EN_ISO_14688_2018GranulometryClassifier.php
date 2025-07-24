<?php

namespace App\Libraries\SoilClassification\Granulometry\Classifiers;

use App\Libraries\PointInPolygon;
use App\Libraries\SoilClassification\Granulometry\GranulometryClassificationResult;
use App\Libraries\SoilClassification\Granulometry\GranulometryClassifier;
use App\Models\Granulometry;

class SR_EN_ISO_14688_2018GranulometryClassifier extends GranulometryClassifier
{
    protected string $systemCode = 'sr_en_iso_14688_2018';




    private function analyze(Granulometry $granulometry, array $soil): array
    {
        if ($this->requiresSecondaryAnalysis($soil)) {
            return $this->runSecondaryAnalysis($granulometry, $soil);
        }

        return $soil;
    }

    private function requiresSecondaryAnalysis(array $primaryResult): bool
    {
        return isset($primaryResult['soils']) && count($primaryResult['soils']) > 1;
    }

    public function runSecondaryAnalysis(Granulometry $granulometry, array $primarySoil): array
    {
        $fine = $granulometry->clay + $granulometry->silt;
        $clay = $granulometry->clay;

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
