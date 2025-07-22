<?php

namespace App\Services;

use App\Libraries\PointInPolygon;

class GeometryService
{
    /**
     * Verifică dacă un punct se află într-un poligon (wrapper pentru PointInPolygon)
     * 
     * @param array $point [x, y] coordonatele punctului
     * @param array $polygon [[x1,y1], [x2,y2], ...] vârfurile poligonului
     * @return string 'inside', 'outside', 'on_boundary', 'on_vertex'
     */
    public function pointInPolygon(array $point, array $polygon): string
    {
        $checker = new PointInPolygon(
            checkPointOnVertex: false,
            checkPointOnBoundary: true,
            tolerance: 0.001
        );

        return $checker->pointInPolygon($point, $polygon);
    }

    public function analyzePointPosition(array $point, array $polygon): array
    {
        $checker = new PointInPolygon(
            checkPointOnVertex: true,    // Verifică și vertex-urile
            checkPointOnBoundary: true,  // Verifică și boundary-urile
            tolerance: 0.001
        );

        $result = $checker->pointInPolygon($point, $polygon);

        return [
            'status' => $result,
            'is_inside' => $result === PointInPolygon::INSIDE,
            'is_ambiguous' => in_array($result, [PointInPolygon::ON_BOUNDARY, PointInPolygon::ON_VERTEX]),
        ];
    }

    /**
     * Convertește coordonate ternare în carteziene (doar pentru diagrame ternare)
     */
    public function ternaryToCartesian(array $ternaryCoordinates): array
    {
        $ternaryCoordinates = array_values($ternaryCoordinates);
        if (count($ternaryCoordinates) !== 3) {
            throw new \InvalidArgumentException('Ternary coordinates must have exactly 3 components');
        }

        [$a, $b, $c] = $ternaryCoordinates;

        // Validare că suma = 100%
        $sum = $a + $b + $c;
        if (abs($sum - 100) > 0.001) {
            throw new \InvalidArgumentException("Ternary coordinates must sum to 100. Got: {$sum}");
        }

        // Formula standard pentru conversie ternary → cartesian
        $x = 0.5 * (2 * $a + $b);
        $y = sqrt(3) * $b * 0.5;

        return [$x, $y];
    }
}
