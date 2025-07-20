<?php

namespace App\Libraries\SoilClassification\Granulometry\Services;

use App\Models\Granulometry;
use App\Libraries\SoilClassification\Services\GranulometryService;

class SoilNameService
{
    public function __construct(
        private GranulometryService $granulometryService
    ) {}

    public function build(string $initialName, Granulometry $granulometry): string
    {
        $coarsePercentage = $this->granulometryService->getCoarseFractionsPercentage($granulometry);

        if ($coarsePercentage == 0) {
            return $initialName;
        }

        $coarseDescription = $this->getCoarseFractionDescription($granulometry);

        return $this->formatSoilName($initialName, $coarseDescription, $coarsePercentage);
    }

    private function formatSoilName(string $initialName, string $coarseDescription, float $coarsePercentage): string
    {
        if ($coarsePercentage <= 40) {
            return $initialName . ' cu ' . $coarseDescription;
        }

        return ucfirst($coarseDescription) . ' cu ' . strtolower($initialName);
    }

    private function getCoarseFractionDescription(Granulometry $granulometry): string
    {
        $gravel = $granulometry->gravel ?? 0;
        $cobble = $granulometry->cobble ?? 0;
        $boulder = $granulometry->boulder ?? 0;

        $coarseFractions = [
            'boulder' => ['percentage' => $boulder, 'name' => 'blocuri'],
            'cobble' => ['percentage' => $cobble, 'name' => 'bolovăniș'],
            'gravel' => ['percentage' => $gravel, 'name' => 'pietriș']
        ];
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
            if ($fraction['percentage'] < 20) {
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
}
