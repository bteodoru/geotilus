<?php

namespace App\Libraries\SoilClassification\Services;

use App\Libraries\PointInPolygon;
use App\Services\GeometryService;


class TernaryDiagramService
{
    public function __construct(private GeometryService $geometryService) {}

    public function findSoilType(float $x, float $y, array $diagram): ?array
    {
        foreach ($diagram as $soilDomain) {
            $cartesianPoints = array_map(
                fn($ternaryPoint) => $this->geometryService->ternaryToCartesian($ternaryPoint),
                $soilDomain['points']
            );

            $result = $this->geometryService->pointInPolygon([$x, $y], $cartesianPoints);


            if ($result === PointInPolygon::INSIDE || $result === PointInPolygon::ON_BOUNDARY) {
                return $this->processSoilDomain($soilDomain, $cartesianPoints, $x, $y);
            }
        }

        return null;
    }

    private function processSoilDomain(array $soilDomain, array $cartesianPoints, float $x, float $y): array
    {
        if (isset($soilDomain['name'])) {
            return [
                'name' => $soilDomain['name'],
                // 'color' => $soilDomain['color'],
                'cartesian_points' => $cartesianPoints
            ];
        }

        if (isset($soilDomain['soils'])) {
            return $this->processComplexSoilDomain($soilDomain, $cartesianPoints, $x, $y);
        }

        throw new \InvalidArgumentException('Invalid soil domain structure');
    }

    private function processComplexSoilDomain(array $soilDomain, array $cartesianPoints, float $x, float $y): array
    {
        $soilKeys = array_keys($soilDomain['soils']);
        $dynamicName = implode(';', $soilKeys);

        return [
            'name' => $dynamicName,
            // 'color' => $soilDomain['color'],
            'soils' => $soilDomain['soils']
        ];
    }
}
