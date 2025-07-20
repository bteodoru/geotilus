<?php

namespace App\Libraries\SoilClassification\Granulometry\Services;

use App\Libraries\PointInPolygon;
use App\Models\Granulometry;
use App\Services\GeometryService;


class TernaryDiagramService
{
    public function __construct(private GeometryService $geometryService) {}

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
                // 'color' => $soilDomain['color'],
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
        array $usedFractions
    ) //: CoordinateData
    {
        // $rawCoordinates = $this->extractCoordinateValues($granulometry, $requiredFractions);

        if ($this->shouldNormalize($granulometry, $requiredFractions)) {
            return $this->normalizeCoordinates($usedFractions);
        }

        return [
            // 'coordinates' => $rawCoordinates,
            'coordinates' => $usedFractions,
            'normalizationApplied' => false,
            'normalizationFactor' => 1.0
        ];
        // return new CoordinateData(
        //     coordinates: new TernaryCoordinates(...$rawCoordinates),
        //     normalizationApplied: false,
        //     normalizationFactor: 1.0
        // );
    }



    private function shouldNormalize(Granulometry $granulometry, array $requiredFractions): bool
    {
        $allFractions = ['clay', 'silt', 'sand', 'gravel', 'cobble', 'boulder'];
        $unusedFractions = array_diff($allFractions, $requiredFractions);

        foreach ($unusedFractions as $fraction) {
            if (($granulometry->{$fraction} ?? 0) > 0) {
                return true;
            }
        }

        return false;
    }

    private function normalizeCoordinates(array $coordinates) //: CoordinateData
    {
        $total = array_sum($coordinates);
        $factor = $total > 0 ? 100 / $total : 1.0;

        $normalizedCoordinates = array_map(fn($value) => $value * $factor, $coordinates);

        // return new CoordinateData(
        //     coordinates: new TernaryCoordinates(...$normalizedCoordinates),
        //     normalizationApplied: true,
        //     normalizationFactor: $factor
        // );
        return [
            'coordinates' => $normalizedCoordinates,
            'normalizationApplied' => true,
            'normalizationFactor' => $factor
        ];
    }
}
