<?php

namespace App\Libraries\SoilClassification\Granulometry\Classifiers;

use App\Libraries\PointInPolygon;
use App\Libraries\SoilClassification\Granulometry\GranulometryClassificationResult;
use App\Libraries\SoilClassification\Services\GranulometryService;
use App\Libraries\SoilClassification\Services\TernaryDiagramService;
use App\Libraries\SoilClassification\Services\StandardRequirementsService;
use App\Models\Granulometry;
use App\Services\GeometryService;

abstract class GranulometryClassifier
{
    public function __construct(
        protected GranulometryService $granulometryService,
        protected TernaryDiagramService $ternaryDiagramService,
        protected StandardRequirementsService $requirementsService,
        protected GeometryService $geometryService
    ) {}




    public function classify(Granulometry $granulometry): GranulometryClassificationResult
    {
        $errors = $this->granulometryService->validateGranulometry($granulometry);
        if (!empty($errors)) {
            throw new \InvalidArgumentException('Invalid granulometry data: ' . implode(', ', $errors));
        }


        $ternaryCoordinatesData = $this->processTernaryCoordinates($granulometry);


        [$cartesianX, $cartesianY] = $this->geometryService->ternaryToCartesian($ternaryCoordinatesData['coordinates']);

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
            $soilName = $soilType['name'];
        } else {
            $soilType = $primaryDomain;
            $soilName = $this->buildSoilName($soilType['name'], $granulometry);
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
                'applied' => $ternaryCoordinatesData['normalization_applied'],
                'factor' => round($ternaryCoordinatesData['normalization_factor'], 4),
                'normalized_coordinates' => array_map(fn($coord) => round($coord, 2), $ternaryCoordinatesData['coordinates'])
            ]

        ];


        return new GranulometryClassificationResult(
            soilType: $soilName,
            standardInfo: $this->getStandardInfo(),
            metadata: $this->buildMetadata(
                $granulometry,
                $ternaryCoordinatesData['normalization_applied'],
                $ternaryCoordinatesData['normalization_factor'],
                $ternaryCoordinatesData['coordinates']
            )
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

    abstract protected function getCoordinateValues(Granulometry $granulometry): array;

    protected function extractFractions(Granulometry $granulometry, array $fractions): array
    {
        $values = [];
        foreach ($fractions as $fraction) {
            $values[$fraction] = $granulometry->{$fraction} ?? 0;
        }
        return $values;
    }

    protected function processTernaryCoordinates(Granulometry $granulometry): array
    {
        $requiredFractions = $this->getRequiredTernaryFractions();

        $needsNormalization = $this->needsNormalization(
            $granulometry,
            $requiredFractions
        );

        $coordinates = $this->getCoordinateValues($granulometry);

        if ($needsNormalization) {
            $total = array_sum($coordinates);
            if ($total > 0) {
                $normalizationFactor = 100 / $total;
                return $this->buildNormalizedCoordinatesResult($coordinates, $normalizationFactor);
            }
        }
        return $this->buildSimpleCoordinatesResult($coordinates);
    }

    protected function needsNormalization(
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
