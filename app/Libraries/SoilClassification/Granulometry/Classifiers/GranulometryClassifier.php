<?php

namespace App\Libraries\SoilClassification\Granulometry\Classifiers;

use App\Libraries\SoilClassification\Contracts\GranulometryClassifierInterface;
use App\Libraries\SoilClassification\Granulometry\GranulometryClassificationResult;
use App\Libraries\SoilClassification\Services\GranulometryService;
use App\Libraries\SoilClassification\Services\TernaryDiagramService;
use App\Libraries\SoilClassification\Services\StandardRequirementsService;
use App\Models\Granulometry;
use App\Services\GeometryService;

abstract class GranulometryClassifier implements GranulometryClassifierInterface
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

    // HOOK METHODS - fiecare implementare le definește

    /**
     * Returnează coordonatele ternare în ordinea specifică standardului
     */
    protected function getTernaryCoordinatesOrder(Granulometry $granulometry): array
    {
        return [
            $granulometry->silt,
            $granulometry->clay,
            $granulometry->sand
        ];
    }

    protected function normalizeFractions(Granulometry $granulometry): array
    {
        $clay = $granulometry->clay ?? 0;
        $silt = $granulometry->silt ?? 0;
        $sand = $granulometry->sand ?? 0;
        $gravel = $granulometry->gravel ?? 0;
        $cobble = $granulometry->cobble ?? 0;
        $boulder = $granulometry->boulder ?? 0;

        $fineTotal = $clay + $silt + $sand;

        $coarseTotal = $gravel + $cobble + $boulder;

        // Dacă nu avem fracțiuni grosiere, returnează valorile originale
        if ($coarseTotal == 0 || $fineTotal == 0) {
            return [
                'clay' => $clay,
                'silt' => $silt,
                'sand' => $sand,
                'gravel' => $gravel,
                'cobble' => $cobble,
                'boulder' => $boulder,
                'normalization_applied' => false,
                'original_fine_total' => $fineTotal
            ];
        }

        // Normalizează fracțiunile fine la 100%
        $normalizationFactor = 100 / $fineTotal;

        return [
            'clay' => $clay * $normalizationFactor,
            'silt' => $silt * $normalizationFactor,
            'sand' => $sand * $normalizationFactor,
            'gravel' => $gravel, // Păstrăm originalele pentru metadata
            'cobble' => $cobble,
            'boulder' => $boulder,
            'normalization_applied' => true,
            'normalization_factor' => $normalizationFactor,
            'original_fine_total' => $fineTotal,
            'coarse_total' => $coarseTotal
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
