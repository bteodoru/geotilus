<?php

namespace App\Libraries\SoilClassification\Granulometry\Classifiers;

use App\Libraries\PointInPolygon;
use App\Libraries\SoilClassification\Contracts\GranulometryClassifierInterface;
use App\Libraries\SoilClassification\Granulometry\GranulometryClassificationResult;
use App\Libraries\SoilClassification\Services\GranulometryService;
use App\Libraries\SoilClassification\Services\TernaryDiagramService;
use App\Libraries\SoilClassification\Services\StandardRequirementsService;
use App\Models\Granulometry;
use App\Services\GeometryService;

abstract class GranulometryClassifier //implements GranulometryClassifierInterface
{
    public function __construct(
        protected GranulometryService $granulometryService,
        protected TernaryDiagramService $ternaryDiagramService,
        protected StandardRequirementsService $requirementsService,
        protected GeometryService $geometryService
    ) {}


    public function classify_____(Granulometry $granulometry): GranulometryClassificationResult
    {
        $errors = $this->granulometryService->validateGranulometry($granulometry);
        if (!empty($errors)) {
            throw new \InvalidArgumentException('Invalid granulometry data: ' . implode(', ', $errors));
        }

        $normalizationApplied = false;
        $normalizationFactor = 1.0;

        $ternaryCoordinates = $this->getTernaryCoordinatesOrder($granulometry);
        if (
            $this->granulometryService->hasCoarseFraction($granulometry) ||
            $this->granulometryService->hasVeryCoarseFraction($granulometry)
        ) {
            $total = array_sum($ternaryCoordinates);
            if ($total > 0) {
                $normalizationFactor = 100 / $total;
                $normalizationApplied = true;
            }
        }
        $ternaryCoordinates = array_map(fn($value) => $value * $normalizationFactor, $ternaryCoordinates);
        [$cartesianX, $cartesianY] = $this->geometryService->ternaryToCartesian($ternaryCoordinates);

        $soilType = $this->ternaryDiagramService->findSoilType(
            $cartesianX,
            $cartesianY,
            $this->getTernaryDiagram()
        );

        if (!$soilType) {
            throw new \RuntimeException($this->getClassificationErrorMessage($granulometry));
        }

        $soilName = $this->buildSoilName($soilType['name'], $granulometry);

        return new GranulometryClassificationResult(
            soilType: $soilName,
            standardInfo: $this->getStandardInfo(),
            // color: $soilType['color'],
            // metadata: $this->buildMetadata($granulometry, $soilType)
            metadata: $this->buildMetadata($granulometry, $normalizationApplied, $normalizationFactor, $ternaryCoordinates)
        );
    }

    public function classify(Granulometry $granulometry): GranulometryClassificationResult
    {
        $errors = $this->granulometryService->validateGranulometry($granulometry);
        if (!empty($errors)) {
            throw new \InvalidArgumentException('Invalid granulometry data: ' . implode(', ', $errors));
        }


        $ternaryCoordinates = $this->prepareTernaryCoordinates($granulometry);


        [$cartesianX, $cartesianY] = $this->geometryService->ternaryToCartesian($ternaryCoordinates['coordinates']);

        $primaryDomain = $this->ternaryDiagramService->findSoilType(
            $cartesianX,
            $cartesianY,
            $this->getTernaryDiagram()
        );

        if (!$primaryDomain) {
            throw new \RuntimeException("Cannot determine soil type");
        }

        if ($this->requiresSecondaryAnalysis($primaryDomain)) {
            $soilType = $this->performSecondaryAnalysis($granulometry, $primaryDomain);
        } else {
            $soilType = $primaryDomain;
        }

        $metadata = [
            'clay' => $granulometry->clay,
            'sand' => $granulometry->sand,
            'silt' => $granulometry->silt,
            'gravel' => $granulometry->gravel,
            'cobble' => $granulometry->cobble,
            'boulder' => $granulometry->boulder,
            'fine' => $granulometry->clay + $granulometry->silt,
            'granulometric_class' => $this->granulometryService->getGranulometricClass($granulometry),
            'normalization' => [
                'applied' => $ternaryCoordinates['normalization_applied'],
                'factor' => round($ternaryCoordinates['normalization_factor'], 4),
                'normalized_coordinates' => array_map(fn($coord) => round($coord, 2), $ternaryCoordinates['coordinates'])
            ]

        ];


        return new GranulometryClassificationResult(
            soilType: $soilType['name'],
            standardInfo: $this->getStandardInfo(),
            metadata: $metadata
        );
    }

    private function requiresSecondaryAnalysis(array $primaryResult): bool
    {
        return isset($primaryResult['soils']) && count($primaryResult['soils']) > 1;
    }

    private function performSecondaryAnalysis(Granulometry $granulometry, array $primaryDomain): array
    {
        $fine = $granulometry->clay + $granulometry->silt;
        $clay = $granulometry->clay;

        foreach ($primaryDomain['soils'] as $soilCode => $soilData) {

            if (!isset($soilData['points'])) {
                continue; // Skip domenii fără puncte secundare
            }

            $result = $this->geometryService->pointInPolygon([$fine, $clay], $soilData['points']);

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
                ($primaryDomain['name'])
        );
    }

    // abstract protected function prepareTernaryCoordinates(Granulometry $granulometry): array;



    public function isApplicable(Granulometry $granulometry): bool
    {
        return $this->requirementsService->checkBasicApplicability($granulometry);
    }

    public function getRequiredFields(): array
    {
        return $this->requirementsService->getBasicRequirements();
    }

    public function getAvailableSoilTypes(): array
    {
        return array_keys($this->getTernaryDiagram());
    }

    public function supportsSoilCategory(string $category): bool
    {
        return in_array($category, ['fine', 'coarse', 'mixed']);
    }


    abstract protected function getRequiredTernaryFractions(): array;

    abstract protected function getTernaryCoordinatesOrder(Granulometry $granulometry): array;

    protected function getFractions(Granulometry $granulometry, array $fractions): array
    {
        $values = [];
        foreach ($fractions as $fraction) {
            $values[$fraction] = $granulometry->{$fraction} ?? 0;
        }
        return $values;
    }

    protected function prepareTernaryCoordinates(Granulometry $granulometry): array
    {
        $requiredFractions = $this->getRequiredTernaryFractions();

        $needsNormalization = $this->requiresNormalization(
            $granulometry,
            $requiredFractions
        );

        $coordinates = $this->getTernaryCoordinatesOrder($granulometry);

        if ($needsNormalization) {
            $total = array_sum($coordinates);
            if ($total > 0) {
                $normalizationFactor = 100 / $total;
                return $this->buildNormalizedCoordinatesResult($coordinates, $normalizationFactor);
            }
        }
        return $this->buildSimpleCoordinatesResult($coordinates);
    }

    protected function requiresNormalization(
        Granulometry $granulometry,
        array $requiredFractions
    ): bool {
        $allFractions = ['clay', 'silt', 'sand', 'gravel', 'cobble', 'boulder'];

        $unusedFractions = array_diff($allFractions, $requiredFractions);

        foreach ($unusedFractions as $fraction) {
            $value = $granulometry->{$fraction} ?? 0;
            if ($value > 0) {
                return true;
            }
        }

        return false;
    }

    protected function buildSimpleCoordinatesResult(array $coordinates): array
    {
        return [
            'coordinates' => $coordinates,
            'normalization_applied' => false,
            'normalization_factor' => 1.0,
        ];
    }

    protected function buildNormalizedCoordinatesResult(
        array $originalCoordinates,
        float $normalizationFactor
    ): array {
        $normalizedCoordinates = array_map(
            fn($value) => $value * $normalizationFactor,
            $originalCoordinates
        );

        return [
            'coordinates' => $normalizedCoordinates,
            'normalization_applied' => true,
            'normalization_factor' => $normalizationFactor,
            'original_coordinates' => $originalCoordinates
        ];
    }

    // protected function normalizeFractions(Granulometry $granulometry): array
    // {
    //     $clay = $granulometry->clay ?? 0;
    //     $silt = $granulometry->silt ?? 0;
    //     $sand = $granulometry->sand ?? 0;
    //     $gravel = $granulometry->gravel ?? 0;
    //     $cobble = $granulometry->cobble ?? 0;
    //     $boulder = $granulometry->boulder ?? 0;

    //     $fineTotal = $clay + $silt + $sand;

    //     $coarseTotal = $gravel + $cobble + $boulder;

    //     // Dacă nu avem fracțiuni grosiere, returnează valorile originale
    //     if ($coarseTotal == 0 || $fineTotal == 0) {
    //         return [
    //             'clay' => $clay,
    //             'silt' => $silt,
    //             'sand' => $sand,
    //             'gravel' => $gravel,
    //             'cobble' => $cobble,
    //             'boulder' => $boulder,
    //             'normalization_applied' => false,
    //             'original_fine_total' => $fineTotal
    //         ];
    //     }

    //     // Normalizează fracțiunile fine la 100%
    //     $normalizationFactor = 100 / $fineTotal;

    //     return [
    //         'clay' => $clay * $normalizationFactor,
    //         'silt' => $silt * $normalizationFactor,
    //         'sand' => $sand * $normalizationFactor,
    //         'gravel' => $gravel, // Păstrăm originalele pentru metadata
    //         'cobble' => $cobble,
    //         'boulder' => $boulder,
    //         'normalization_applied' => true,
    //         'normalization_factor' => $normalizationFactor,
    //         'original_fine_total' => $fineTotal,
    //         'coarse_total' => $coarseTotal
    //     ];
    // }

    public function buildSoilName(string $ternaryName, Granulometry $granulometry): string
    {
        $coarsePercentage = $this->granulometryService->getCoarseFractionsPercentage($granulometry);

        // Dacă nu avem material grosier, returnează numele din ternary
        if ($coarsePercentage == 0) {
            return $ternaryName;
        }

        $coarseDescription = $this->granulometryService->getCoarseFractionDescription($granulometry);


        if ($coarsePercentage <= 40) {
            // ≤ 40%: "Denumirea din diagrama ternară cu [material grosier]"
            return $ternaryName . ' cu ' . $coarseDescription;
        } else {
            // > 40%: "[Material grosier] cu denumirea din diagrama ternară"
            return ucfirst($coarseDescription) . ' cu ' . strtolower($ternaryName);
        }
    }

    /**
     * Returnează diagrama ternară specifică standardului
     */
    abstract protected function getTernaryDiagram(): array;

    /**
     * Returnează informațiile despre standard
     */
    abstract public function getStandardInfo(): array;

    /**
     * Generează metadatele specifice clasificării
     */
    protected function buildMetadata(Granulometry $granulometry, bool $normalizationApplied, float $normalizationFactor, array $finalCoordinates): array
    {
        $metadata = [
            'clay' => $granulometry->clay,
            'sand' => $granulometry->sand,
            'silt' => $granulometry->silt,
            'gravel' => $granulometry->gravel,
            'cobble' => $granulometry->cobble,
            'boulder' => $granulometry->boulder,
            // 'soil_fractions' => [
            //     'clay' => $granulometry->clay,
            //     'sand' => $granulometry->sand,
            //     'silt' => $granulometry->silt,
            //     'gravel' => $granulometry->gravel,
            //     'cobble' => $granulometry->cobble,
            //     'boulder' => $granulometry->boulder
            // ],
            // 'classification_method' => $this->getClassificationMethod(),
            'granulometric_class' => $this->granulometryService->getGranulometricClass($granulometry)
        ];

        if ($normalizationApplied) {
            $metadata['normalization'] = [
                'applied' => true,
                'factor' => round($normalizationFactor, 4),
                'normalized_coordinates' => array_map(fn($coord) => round($coord, 2), $finalCoordinates)
            ];
        } else {
            $metadata['normalization'] = ['applied' => false];
        }

        return $metadata;
    }

    /**
     * Returnează numele metodei de clasificare (pentru metadata)
     */
    // abstract protected function getClassificationMethod(): string;

    /**
     * Generează mesajul de eroare pentru clasificare eșuată
     */
    protected function getClassificationErrorMessage(Granulometry $granulometry): string
    {
        return "Nu s-a putut determina tipul de pământ pentru: " .
            "Clay={$granulometry->clay}%, " .
            "Sand={$granulometry->sand}%, " .
            "Silt={$granulometry->silt}%";
    }
}
