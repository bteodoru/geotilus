<?php

namespace App\Services\Granulometry;

use App\Models\Granulometry;

class GranulometryAnalysisService
{
    public function isFine(Granulometry $granulometry): bool
    {
        $fineContent = $granulometry->clay + $granulometry->silt;

        return $fineContent > 50 && $granulometry->sample->cohesive_soil;
    }
    // public function isFine(Granulometry $granulometry, ?string $standard = null): bool
    // {
    //     $standard = $standard ?? config('soil_classification.default_standard');
    //     $fineContent = $granulometry->clay + $granulometry->silt;

    //     return match ($standard) {
    //         'sr_en_iso_14688_2018' => $fineContent > 50,
    //         default => $fineContent > 50
    //     };
    // }

    public function isCoarse(Granulometry $granulometry): bool
    {

        $coarseContent = $granulometry->sand + $granulometry->gravel;

        return $coarseContent > 50 && !$granulometry->sample->cohesive_soil;
    }
    // public function isCoarse(Granulometry $granulometry, ?string $standard = null): bool
    // {
    //     $standard = $standard ?? config('soil_classification.default_standard', 'sr_en_iso_14688_2018');

    //     $coarseContent = $granulometry->sand + $granulometry->gravel;

    //     return match ($standard) {
    //         'sr_en_iso_14688_2018' => $coarseContent > 50,
    //         default => $coarseContent > 50
    //     };
    // }

    public function isVeryCoarse(Granulometry $granulometry): bool
    {

        $veryCoarseContent = $granulometry->cobble + $granulometry->boulder;
        // dd($granulometry->sample);

        return $veryCoarseContent > 50 && !$granulometry->sample->cohesive_soil;;
    }
    // public function isVeryCoarse(Granulometry $granulometry, ?string $standard = null): bool
    // {
    //     $standard = $standard ?? config('soil_classification.default_standard', 'sr_en_iso_14688_2018');

    //     $veryCoarseContent = $granulometry->cobble + $granulometry->boulder;

    //     return match ($standard) {
    //         'sr_en_iso_14688_2018' => $veryCoarseContent > 50,
    //         default => $veryCoarseContent > 50
    //     };
    // }

    public function getPrimaryFraction(Granulometry $granulometry): array
    {
        if ($this->isVeryCoarse($granulometry)) {
            return $this->getVeryCoarsePrimaryFraction($granulometry);
        }

        if ($this->isCoarse($granulometry)) {
            return $this->getCoarsePrimaryFraction($granulometry);
        }

        return [];
    }

    // public function getPrimaryFraction(Granulometry $granulometry, ?string $standard = null): array
    // {
    //     if ($this->isVeryCoarse($granulometry, $standard)) {
    //         return $this->getVeryCoarsePrimaryFraction($granulometry);
    //     }

    //     if ($this->isCoarse($granulometry, $standard)) {
    //         return $this->getCoarsePrimaryFraction($granulometry);
    //     }




    //     // return $this->getMixedPrimaryFraction($granulometry);
    //     return [];
    // }

    private function getVeryCoarsePrimaryFraction(Granulometry $granulometry): array
    {
        $cobble = $granulometry->cobble ?? 0;
        $boulder = $granulometry->boulder ?? 0;

        if ($cobble > $boulder) {
            return [
                'fraction' => 'cobble',
                'percentage' => $cobble,
                'category' => 'very_coarse',
            ];
        } else {
            return [
                'fraction' => 'boulder',
                'percentage' => $boulder,
                'category' => 'very_coarse',
            ];
        }
    }

    private function getCoarsePrimaryFraction(Granulometry $granulometry): array
    {
        $sand = $granulometry->sand ?? 0;
        $gravel = $granulometry->gravel ?? 0;

        if ($sand > $gravel) {
            return [
                'fraction' => 'sand',
                'percentage' => $sand,
                'category' => 'coarse',
            ];
        } else {
            return [
                'fraction' => 'gravel',
                'percentage' => $gravel,
                'category' => 'coarse',
            ];
        }
    }

    public function getFinePrimaryFraction(Granulometry $granulometry): array
    {
        $clay = $granulometry->clay ?? 0;
        $silt = $granulometry->silt ?? 0;

        if ($clay > $silt) {
            return [
                'fraction' => 'clay',
                'percentage' => $clay,
                'category' => 'fine',
            ];
        } else {
            return [
                'fraction' => 'silt',
                'percentage' => $silt,
                'category' => 'fine',
            ];
        }
    }

    public function getSoilClass(Granulometry $granulometry, ?string $standard = null): string
    {
        if ($this->isVeryCoarse($granulometry, $standard)) return 'very_coarse';
        if ($this->isCoarse($granulometry, $standard)) return 'coarse';
        if ($this->isFine($granulometry, $standard)) return 'fine';
        return 'mixed';
    }

    public function getDominantComponent(Granulometry $granulometry): array
    {
        $components = [
            'clay' => $granulometry->clay_percentage,
            'silt' => $granulometry->silt_percentage,
            'sand' => $granulometry->sand_percentage,
        ];

        $dominantComponent = array_keys($components, max($components))[0];

        return [
            'component' => $dominantComponent,
            'percentage' => $components[$dominantComponent]
        ];
    }
}
