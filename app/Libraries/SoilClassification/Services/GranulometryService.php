<?php

namespace App\Libraries\SoilClassification\Services;

use App\Models\Granulometry;
use App\Libraries\PointInPolygon;

/**
 * Service pentru logica comună tuturor clasificatorilor granulometrici
 */
class GranulometryService
{
    /**
     * Validează datele granulometrice
     * 
     * @param Granulometry $granulometry
     * @return array Lista erorilor (gol dacă totul e OK)
     */
    public function validateGranulometry(Granulometry $granulometry): array
    {
        $errors = [];

        // Verifică că avem datele de bază
        if (
            is_null($granulometry->clay) ||
            is_null($granulometry->silt) ||
            is_null($granulometry->sand)
        ) {
            $errors[] = 'Missing basic granulometry data (clay, silt, sand)';
            return $errors; // Nu continuăm dacă nu avem datele de bază
        }

        // Verifică că toate valorile sunt în intervalul valid
        $this->validatePercentageRange($granulometry->clay, 'clay', $errors);
        $this->validatePercentageRange($granulometry->silt, 'silt', $errors);
        $this->validatePercentageRange($granulometry->sand, 'sand', $errors);

        if ($granulometry->gravel !== null) {
            $this->validatePercentageRange($granulometry->gravel, 'gravel', $errors);
        }

        if ($granulometry->cobble !== null) {
            $this->validatePercentageRange($granulometry->cobble, 'cobble', $errors);
        }

        if ($granulometry->boulder !== null) {
            $this->validatePercentageRange($granulometry->boulder, 'boulder', $errors);
        }

        // Verifică că suma totală = 100%
        $total = $granulometry->clay +
            $granulometry->silt +
            $granulometry->sand +
            ($granulometry->gravel ?? 0) +
            ($granulometry->cobble ?? 0) +
            ($granulometry->boulder ?? 0);

        if (abs($total - 100) > 0.01) {
            $errors[] = "Total percentages sum to {$total}% instead of 100%";
        }

        return $errors;
    }

    /**
     * Validează că un procent este în intervalul 0-100
     */
    private function validatePercentageRange(float $value, string $field, array &$errors): void
    {
        if ($value < 0 || $value > 100) {
            $errors[] = "{$field} percentage ({$value}%) is out of valid range (0-100%)";
        }
    }

    /**
     * Convertește coordonate ternare în coordonate carteziene
     * Formula standard pentru triunghi echilateral
     * 
     * @param array $ternaryCoordinates [component1, component2, component3] care se adună la 100
     * @return array [x, y] coordonatele carteziene
     */
    // public function ternaryToCartesian(array $ternaryCoordinates): array
    // {
    //     if (count($ternaryCoordinates) !== 3) {
    //         throw new \InvalidArgumentException('Ternary coordinates must have exactly 3 components');
    //     }

    //     [$clay, $sand, $silt] = $ternaryCoordinates;

    //     // Validare că suma = 100%
    //     $sum = $clay + $sand + $silt;
    //     if (abs($sum - 100) > 0.001) {
    //         throw new \InvalidArgumentException("Ternary coordinates must sum to 100. Got: {$sum}");
    //     }

    //     // Formula standard pentru conversie ternary → cartesian
    //     $x = 0.5 * (2 * $silt + $clay);
    //     $y = sqrt(3) * $clay * 0.5;

    //     return [$x, $y];
    // }

    // /**
    //  * Verifică dacă un punct se află într-un poligon (wrapper pentru PointInPolygon)
    //  * 
    //  * @param array $point [x, y] coordonatele punctului
    //  * @param array $polygon [[x1,y1], [x2,y2], ...] vârfurile poligonului
    //  * @return string 'inside', 'outside', 'on_boundary', 'on_vertex'
    //  */
    // public function pointInPolygon(array $point, array $polygon): string
    // {
    //     $checker = new PointInPolygon(
    //         checkPointOnVertex: false,
    //         checkPointOnBoundary: true,
    //         tolerance: 0.001
    //     );

    //     return $checker->pointInPolygon($point, $polygon);
    // }

    /**
     * Calculează distanța minimă de la un punct la marginile unui poligon
     * 
     * @param array $point [x, y] coordonatele punctului
     * @param array $polygon [[x1,y1], [x2,y2], ...] vârfurile poligonului
     * @return float Distanța minimă
     */
    public function getMinDistanceToPolygon(array $point, array $polygon): float
    {
        $checker = new PointInPolygon();
        return $checker->getMinDistanceToPolygon($point, $polygon);
    }

    /**
     * Calculează confidence score bazat pe distanța la granițele unei zone
     * 
     * @param array $point Coordonatele punctului
     * @param array $polygon Vârfurile zonei
     * @param float $maxDistance Distanța maximă considerată relevantă
     * @param float $baseConfidence Confidence-ul minim pentru puncte pe margini
     * @return float Valoare între 0.0 și 1.0
     */
    public function calculateConfidenceScore(array $point, array $polygon, float $maxDistance = 20.0, float $baseConfidence = 0.5): float
    {
        $minDistance = $this->getMinDistanceToPolygon($point, $polygon);

        $normalizedDistance = min($minDistance / $maxDistance, 1.0);
        $confidence = $baseConfidence + (0.5 * $normalizedDistance);

        return round($confidence, 3);
    }

    /**
     * Determină clasa granulometrică (fine, coarse, very_coarse, mixed)
     * 
     * @param Granulometry $granulometry
     * @return string
     */
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

    /**
     * Determină fracțiunea majoritară dintr-o categorie
     * 
     * @param Granulometry $granulometry
     * @param string $category 'fine', 'coarse', 'very_coarse'
     * @return array ['fraction' => string, 'percentage' => float]
     */
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

    /**
     * Construiește descrierea fracțiunilor grosiere
     */
    public function getCoarseFractionDescription(Granulometry $granulometry): string
    {
        $gravel = $granulometry->gravel ?? 0;
        $cobble = $granulometry->cobble ?? 0;
        $boulder = $granulometry->boulder ?? 0;

        // Construiește array cu fracțiunile și procentele lor
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


        // Construiește descrierea cu "rar" individual pentru fiecare fracțiune < 20%
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
