<?php

namespace App\Libraries\SoilClassification\Services;

use App\Models\Granulometry;


class GranulometryService
{

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

    public function extractFractions(Granulometry $granulometry, array $fractions): array
    {
        $values = [];
        foreach ($fractions as $fraction) {
            $values[$fraction] = $granulometry->{$fraction} ?? 0;
        }
        return $values;
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
            ? ['fraction' => 'cobble', 'percentage' => $cobble]
            : ['fraction' => 'boulder', 'percentage' => $boulder];
    }

    private function getDominantCoarseFraction(Granulometry $granulometry): array
    {
        $sand = $granulometry->sand ?? 0;
        $gravel = $granulometry->gravel ?? 0;

        return $sand >= $gravel
            ? ['fraction' => 'sand', 'percentage' => $sand]
            : ['fraction' => 'gravel', 'percentage' => $gravel];
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
        $fractions = [
            'clay' => $granulometry->clay ?? 0,
            'silt' => $granulometry->silt ?? 0,
            'sand' => $granulometry->sand ?? 0,
            'gravel' => $granulometry->gravel ?? 0,
            'cobble' => $granulometry->cobble ?? 0,
            'boulder' => $granulometry->boulder ?? 0,
        ];

        $dominantFraction = collect($fractions)
            ->sortByDesc(fn($percentage) => $percentage)
            ->keys()
            ->first();

        return [
            'fraction' => $dominantFraction,
            'percentage' => $fractions[$dominantFraction]
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

    public function getCoarseFractionsPercentage(Granulometry $granulometry): float
    {
        return $granulometry->gravel ?? 0 + $granulometry->cobble ?? 0 + $granulometry->boulder ?? 0;
    }

    public function hasCoarseFraction(Granulometry $granulometry): bool
    {
        return ($granulometry->gravel ?? 0) > 0;
    }

    public function hasVeryCoarseFraction(Granulometry $granulometry): bool
    {
        return ($granulometry->cobble ?? 0) > 0 || ($granulometry->boulder ?? 0) > 0;
    }
}
