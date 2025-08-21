<?php

namespace App\Libraries\SoilClassification\Granulometry\Services;

use App\Models\Granulometry;
use App\Services\Granulometry\GranulometryAnalysisService;

class SoilNameService
{
    public function __construct(
        private GranulometryAnalysisService $granulometryService
    ) {}

    public function build(string $initialName, Granulometry $granulometry, $exclude, $thresholds, $gradationDescription = ''): string
    {
        $coarsePercentage = $this->granulometryService->getFractionsSum($granulometry, $exclude);
        if ($coarsePercentage == 0) {
            return $initialName;
        }
        $coarseDescription = $this->getSecondaryFractionDescription($granulometry, $exclude, $thresholds[1]);
        return $this->formatSoilName($initialName, $coarseDescription, $coarsePercentage, $thresholds[0], $gradationDescription);
    }

    private function formatSoilName(string $initialName, string $coarseDescription, float $coarsePercentage, float $threshold, $gradationDescription): string
    {
        if ($coarsePercentage <= $threshold) {
            return $initialName . ' cu ' . $coarseDescription . (!empty($gradationDescription) ? ', având granulometrie ' . $gradationDescription : '');
        }

        return ucfirst($coarseDescription) . ' cu ' . strtolower($initialName) . (!empty($gradationDescription) ? ', având granulometrie ' . $gradationDescription : '');
    }


    private function getSecondaryFractionDescription(Granulometry $granulometry, array $exclude = [], float $threshold = 25.0): string
    {
        $allFractions = $this->granulometryService->getAllFractionNames();

        $fractionsToAnalyze = array_diff(array_keys($allFractions), $this->granulometryService->expandGranulometricFractions($exclude));

        if (empty($fractionsToAnalyze)) {
            return '';
        }

        $extractedFractions = $this->granulometryService->extractGranulometricFractions($granulometry, $fractionsToAnalyze);

        $activeFractions = array_filter($extractedFractions, fn($percentage) => $percentage > 0);

        if (empty($activeFractions)) {
            return '';
        }

        arsort($activeFractions);

        $descriptions = [];
        foreach ($activeFractions as $fractionKey => $percentage) {
            $name = strtolower($allFractions[$fractionKey]);

            if ($percentage < $threshold) {
                $name = 'rar ' . $name;
            }

            $descriptions[] = $name;
        }

        return $this->formatFractionDescriptions($descriptions);
    }


    private function formatFractionDescriptions(array $descriptions): string
    {
        if (count($descriptions) === 1) {
            return $descriptions[0];
        } elseif (count($descriptions) === 2) {
            return $descriptions[0] . ' și ' . $descriptions[1];
        } else {
            $last = array_pop($descriptions);
            return implode(', ', $descriptions) . ' și ' . $last;
        }
    }
}
