<?php

namespace App\Libraries\SoilClassification\Granulometry\Services;

use App\Models\Granulometry;
// use App\Libraries\SoilClassification\Services\GranulometryService;
use App\Services\Granulometry\GranulometryAnalysisService;

class SoilNameService
{
    public function __construct(
        private GranulometryAnalysisService $granulometryService
    ) {}

    public function build(string $initialName, Granulometry $granulometry, $exclude, $thresholds): string
    {
        $coarsePercentage = $this->granulometryService->getCoarseFractionsPercentage($granulometry, $exclude);
        if ($coarsePercentage == 0) {
            return $initialName;
        }

        $coarseDescription = $this->getSecondaryFractionDescription($granulometry, $exclude, $thresholds[1]);
        return $this->formatSoilName($initialName, $coarseDescription, $coarsePercentage, $thresholds[0]);
    }

    private function formatSoilName(string $initialName, string $coarseDescription, float $coarsePercentage, float $threshold): string
    {
        if ($coarsePercentage <= $threshold) {
            return $initialName . ' cu ' . $coarseDescription;
        }

        return ucfirst($coarseDescription) . ' cu ' . strtolower($initialName);
    }

    private function getSecondaryFractionDescription_(Granulometry $granulometry, array $exclude = ['sand'], $threshold): string
    {

        $sand = $granulometry->sand ?? 0;
        $gravel = $granulometry->gravel ?? 0;
        $cobble = $granulometry->cobble ?? 0;
        $boulder = $granulometry->boulder ?? 0;

        $coarseFractions = [
            'boulder' => ['percentage' => $boulder, 'name' => 'blocuri'],
            'cobble' => ['percentage' => $cobble, 'name' => 'bolovăniș'],
            'gravel' => ['percentage' => $gravel, 'name' => 'pietriș'],
            'sand' => ['percentage' => $sand, 'name' => 'nisip']
        ];

        if (!empty($exclude)) {
            $coarseFractions = array_diff_key($coarseFractions, array_flip($exclude));
        }

        // Filtrează doar fracțiunile > 0 și sortează descrescător
        $activeFractions = array_filter($coarseFractions, fn($fraction) => $fraction['percentage'] > 0);
        uasort($activeFractions, fn($a, $b) => $b['percentage'] <=> $a['percentage']);

        if (empty($activeFractions)) {
            return '';
        }

        $descriptions = [];
        foreach ($activeFractions as $fraction) {
            $name = $fraction['name'];

            // Adaugă "rar" dacă fracțiunea individuală < 20%
            if ($fraction['percentage'] < $threshold) {
                $name = 'rar ' . $name;
            }

            $descriptions[] = $name;
        }

        // Combină cu "și" între ultimele două
        if (count($descriptions) === 1) {
            return $descriptions[0];
        } elseif (count($descriptions) === 2) {
            return $descriptions[0] . ' și ' . $descriptions[1];
        } else {
            $last = array_pop($descriptions);
            return implode(', ', $descriptions) . ' și ' . $last;
        }
    }

    private function getSecondaryFractionDescription(Granulometry $granulometry, array $exclude = [], float $threshold = 25.0): string
    {
        $allFractions = $this->granulometryService->getAllFractionNames();

        $fractionsToAnalyze = array_diff(array_keys($allFractions), $exclude);

        if (empty($fractionsToAnalyze)) {
            return '';
        }

        $extractedFractions = $this->granulometryService->extractGranulometricFractions($granulometry, $fractionsToAnalyze);
        // Extragem valorile
        $extractedFractions = $this->granulometryService->extractGranulometricFractions($granulometry, $fractionsToAnalyze);

        // Filtrăm doar fracțiunile > 0
        $activeFractions = array_filter($extractedFractions, fn($percentage) => $percentage > 0);

        if (empty($activeFractions)) {
            return '';
        }

        // Sortăm descrescător după procent
        arsort($activeFractions);

        // Construim descrierile direct
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
