<?php

namespace App\Libraries\SoilClassification\Granulometry\Services;

use App\Libraries\PointInPolygon;
use App\Models\Granulometry;
use App\Services\GeometryService;
use App\Services\Granulometry\GranulometryAnalysisService;

class TernaryDiagramService
{
    public function __construct(
        private GeometryService $geometryService,
        private GranulometryAnalysisService $granulometryAnalysisService
    ) {}

    public function findSoil(float $x, float $y, array $diagram): ?array
    {
        foreach ($diagram as $soilDomain) {
            $cartesianPoints = array_map(
                fn($ternaryPoint) => $this->geometryService->ternaryToCartesian($ternaryPoint),
                $soilDomain['points']
            );

            $result = $this->geometryService->pointInPolygon([$x, $y], $cartesianPoints);


            if ($result === PointInPolygon::INSIDE || $result === PointInPolygon::ON_BOUNDARY) {
                return $this->processSoilDomain($soilDomain, $cartesianPoints);
            }
        }

        return null;
    }

    private function processSoilDomain(array $soilDomain, array $cartesianPoints): array
    {
        if (isset($soilDomain['name'])) {
            return [
                'name' => $soilDomain['name'],
                'cartesian_points' => $cartesianPoints
            ];
        }

        if (isset($soilDomain['soils'])) {
            return $this->processComplexSoilDomain($soilDomain);
        }

        throw new \InvalidArgumentException('Invalid soil domain structure');
    }

    private function processComplexSoilDomain(array $soilDomain): array
    {
        $soilKeys = array_keys($soilDomain['soils']);
        $dynamicName = implode(';', $soilKeys);

        return [
            'name' => $dynamicName,
            // 'color' => $soilDomain['color'],
            'soils' => $soilDomain['soils']
        ];
    }

    public function prepareTernaryData(
        Granulometry $granulometry,
        array $requiredFractions,
    ): array {
        // dd($requiredFractions);
        if ($this->shouldNormalize($granulometry, $requiredFractions)) {
            return $this->normalizeCoordinates($requiredFractions);
        }

        return [
            'coordinates' => $requiredFractions,
            'normalizationApplied' => false,
            'normalizationFactor' => 1.0
        ];
    }



    private function shouldNormalize(Granulometry $granulometry, array $requiredFractions): bool
    {

        $allFractions = array_keys($this->granulometryAnalysisService->getAllFractionNames());

        $unusedFractions = array_diff($allFractions, array_keys($requiredFractions));
        foreach ($unusedFractions as $fraction) {
            if (($granulometry->{$fraction} ?? 0) > 0) {
                return true;
            }
        }

        return false;
    }

    private function normalizeCoordinates(array $coordinates): array
    {
        $total = array_sum($coordinates);
        $factor = $total > 0 ? 100 / $total : 1.0;

        $normalizedCoordinates = array_map(fn($value) => $value * $factor, $coordinates);

        return [
            'coordinates' => $normalizedCoordinates,
            'normalizationApplied' => true,
            'normalizationFactor' => $factor
        ];
    }
}
