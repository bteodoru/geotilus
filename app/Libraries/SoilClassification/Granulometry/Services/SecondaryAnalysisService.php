<?php

namespace App\Libraries\SoilClassification\Granulometry\Services;

use App\Models\Granulometry;
use App\Libraries\PointInPolygon;
use App\Services\GeometryService;

class SecondaryAnalysisService
{
    public function run(Granulometry $granulometry, array $primarySoil, GeometryService $geometryService): array
    {
        $fine = $granulometry->clay + $granulometry->silt;
        $clay = $granulometry->clay;

        foreach ($primarySoil['soils'] as $soilCode => $soilData) {
            if (!isset($soilData['points'])) {
                continue;
            }

            $result = $geometryService->pointInPolygon([$fine, $clay], $soilData['points']);

            if ($result === PointInPolygon::INSIDE || $result === PointInPolygon::ON_BOUNDARY) {
                return [
                    'code' => $soilCode,
                    'name' => $soilData['name'],
                    'points' => $soilData['points']
                ];
            }
        }

        throw new \RuntimeException(
            "Point ({$fine}% fine, {$clay}% clay) not found in any secondary domain for primary domain: " .
                ($primarySoil['name'])
        );
    }
}
