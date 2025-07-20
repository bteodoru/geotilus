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

        $coarseDescription = $this->granulometryService->getCoarseFractionDescription($granulometry);

        return $this->formatSoilName($initialName, $coarseDescription, $coarsePercentage);
    }

    private function formatSoilName(string $initialName, string $coarseDescription, float $coarsePercentage): string
    {
        if ($coarsePercentage <= 40) {
            return $initialName . ' cu ' . $coarseDescription;
        }

        return ucfirst($coarseDescription) . ' cu ' . strtolower($initialName);
    }
}
