<?php

namespace App\Services\Granulometry;

use App\Models\Granulometry;


class GranulometryAnalysisService
{

    private const COMPOSITE_FRACTIONS = [
        'fine' => ['clay', 'silt'],
        'coarse' => ['sand', 'gravel'],
        'very_coarse' => ['cobble', 'boulder'],
        'total_coarse' => ['sand', 'gravel', 'cobble', 'boulder']
    ];
    private const FRACTIONS = [
        'clay' => 'Argilă',
        'silt' => 'Praf',
        'sand' => 'Nisip',
        'gravel' => 'Pietriș',
        'cobble' => 'Bolovăniș',
        'boulder' => 'Blocuri'
    ];
    private const FRACTIONS_ = [
        'clay' => [
            'name' => 'Argilă',
            'adjective' => ['argiloasă', 'argilos'],
            'gender' => 0,
        ],
        'silt' => [
            'name' => 'Praf',
            'adjective' => ['prăfoasă', 'prăfos'],
            'gender' => 1
        ],
        'sand' => [
            'name' => 'Nisip',
            'adjective' => ['nisipoasă', 'nisipos'],
            'gender' => 1
        ],
        'gravel' => [
            'name' => 'Pietriș',
            'adjective' => ['cu pietriș', 'cu pietriș'],
            'gender' => 1
        ],
        'cobble' => [
            'name' => 'Bolovăniș',
            'adjective' => ['cu bolovăniș', 'cu bolovăniș'],
            'gender' => 1
        ],
        'boulder' => [
            'name' => 'Blocuri',
            'adjective' => ['cu blocuri', 'cu blocuri'],
            'gender' => 1
        ],
    ];



    public function getAdjective(string $fraction, string $forFraction = ''): string
    {
        $for = $forFraction ?: 'clay';

        return self::FRACTIONS_[$fraction]['adjective'][self::FRACTIONS_[$for]['gender']] ?? null;
    }
    public function getFractionName(string $fraction): string
    {
        return self::FRACTIONS_[$fraction]['name'] ?? $fraction;
    }

    public function getAllFractionNames(): array
    {
        return self::FRACTIONS;
    }

    public function extractGranulometricFractions(Granulometry $granulometry, array $granulometricFractions): array
    {
        $result = [];

        foreach ($granulometricFractions as $fractionName) {
            if ($this->isCompositeFraction($fractionName)) {
                $result[$fractionName] = $this->calculateCompositeFractionValue($granulometry, $fractionName);
            } else {
                $result[$fractionName] = $granulometry->{$fractionName} ?? 0;
            }
        }
        return $result;
    }


    public function expandGranulometricFractions(array $granulometricFractions): array
    {
        $expanded = [];
        foreach ($granulometricFractions as $fraction) {
            if ($this->isCompositeFraction($fraction)) {
                foreach (self::COMPOSITE_FRACTIONS[$fraction] as $component) {
                    $expanded[] = $component;
                }
            } else {
                $expanded[] = $fraction;
            }
        }
        return $expanded;
    }

    private function isCompositeFraction(string $fractionName): bool
    {
        return array_key_exists($fractionName, self::COMPOSITE_FRACTIONS);
    }

    private function calculateCompositeFractionValue(Granulometry $granulometry, string $compositeFractionName): float
    {
        $components = self::COMPOSITE_FRACTIONS[$compositeFractionName];
        $totalValue = 0;

        foreach ($components as $component) {
            $totalValue += $granulometry->{$component} ?? 0;
        }

        return $totalValue;
    }

    public function getGranulometricClass(Granulometry $granulometry): string
    {
        $veryCoarseContent = ($granulometry->cobble ?? 0) + ($granulometry->boulder ?? 0);
        if ($veryCoarseContent > 50) {
            return 'very_coarse';
        }

        $coarseContent = ($granulometry->sand ?? 0) + ($granulometry->gravel ?? 0);
        if ($coarseContent > 50) {
            return 'coarse';
        }

        $fineContent = ($granulometry->clay ?? 0) + ($granulometry->silt ?? 0);
        if ($fineContent > 50) {
            return 'fine';
        }

        return 'mixed';
    }


    public function getDominantFractionInCategory(Granulometry $granulometry, string $category): array
    {
        return match ($category) {
            'very_coarse' => $this->getDominantVeryCoarseFraction($granulometry),
            'coarse' => $this->getDominantCoarseFraction($granulometry),
            'fine' => $this->getDominantFineFraction($granulometry),
            'mixed' => $this->getDominantOverallFraction($granulometry),
            default => throw new \InvalidArgumentException("Unknown category: {$category}")
        };
    }

    private function getDominantVeryCoarseFraction(Granulometry $granulometry): array
    {
        $cobble = $granulometry->cobble ?? 0;
        $boulder = $granulometry->boulder ?? 0;

        return $cobble >= $boulder
            ? ['cobble'  => $cobble]
            : ['boulder'  => $boulder];
    }

    private function getDominantCoarseFraction(Granulometry $granulometry): array
    {
        $sand = $granulometry->sand ?? 0;
        $gravel = $granulometry->gravel ?? 0;

        return $sand >= $gravel && !$this->hasFineFraction($granulometry)
            ? ['sand' => $sand]
            : ['gravel' => $gravel];
    }

    private function getDominantFineFraction(Granulometry $granulometry): array
    {
        $clay = $granulometry->clay ?? 0;
        $silt = $granulometry->silt ?? 0;

        return $clay >= $silt
            ? ['fraction' => 'clay', 'percentage' => $clay]
            : ['fraction' => 'silt', 'percentage' => $silt];
    }

    private function getDominantOverallFraction(Granulometry $granulometry): array
    {
        // $fractions = [
        //     'clay' => $granulometry->clay ?? 0,
        //     'silt' => $granulometry->silt ?? 0,
        //     'sand' => $granulometry->sand ?? 0,
        //     'gravel' => $granulometry->gravel ?? 0,
        //     'cobble' => $granulometry->cobble ?? 0,
        //     'boulder' => $granulometry->boulder ?? 0,
        // ];
        $fractions = $this->extractGranulometricFractions($granulometry, array_keys(self::FRACTIONS));
        $dominantFraction = collect($fractions)
            ->sortByDesc(fn($percentage) => $percentage)
            ->keys()
            ->first();

        return [
            $dominantFraction => $fractions[$dominantFraction]
            // 'fraction' => $dominantFraction,
            // 'percentage' => $fractions[$dominantFraction]
        ];
    }

    public function isVeryCoarse(Granulometry $granulometry): bool
    {
        $veryCoarseContent = ($granulometry->cobble ?? 0) + ($granulometry->boulder ?? 0);

        return $veryCoarseContent > 50;
    }

    public function isCoarse(Granulometry $granulometry): bool
    {
        $coarseContent = ($granulometry->sand ?? 0) + ($granulometry->gravel ?? 0);

        return $coarseContent > 50;
    }

    public function isFine(Granulometry $granulometry): bool
    {
        $fineContent = ($granulometry->clay ?? 0) + ($granulometry->silt ?? 0);

        return $fineContent > 50;
    }

    public function getFractionsSum(Granulometry $granulometry, array $exclude = []): float
    {
        $defaultCoarseFractions = array_keys($this->getAllFractionNames());

        $fractionsToExtract = array_diff($defaultCoarseFractions, $this->expandGranulometricFractions($exclude));

        if (empty($fractionsToExtract)) {
            return 0.0;
        }

        $extractedFractions = $this->extractGranulometricFractions($granulometry, $fractionsToExtract);
        return array_sum($extractedFractions);
    }

    public function hasCoarseFraction(Granulometry $granulometry): bool
    {
        return ($granulometry->gravel ?? 0) > 0;
    }

    public function hasVeryCoarseFraction(Granulometry $granulometry): bool
    {
        return ($granulometry->cobble ?? 0) > 0 || ($granulometry->boulder ?? 0) > 0;
    }

    public function hasFineFraction(Granulometry $granulometry): bool
    {
        return ($granulometry->clay ?? 0) > 0 || ($granulometry->silt ?? 0) > 0;
    }
}
