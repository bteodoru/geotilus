<?php

namespace App\Libraries\SoilClassification\Granulometry\Classifiers;

use App\Libraries\SoilClassification\Granulometry\GranulometryClassificationResult;
use App\Libraries\SoilClassification\Granulometry\Services\SecondaryAnalysisService;
use App\Libraries\SoilClassification\Granulometry\Services\SoilNameService;
use App\Libraries\SoilClassification\Granulometry\Services\TernaryDiagramService;
use App\Libraries\SoilClassification\Services\GranulometryService;
use App\Libraries\SoilClassification\Services\StandardRequirementsService;
use App\Models\Granulometry;
use App\Services\GeometryService;

abstract class GranulometryClassifier
{
    public function __construct(
        protected GranulometryService $granulometryService,
        protected TernaryDiagramService $ternaryDiagramService,
        protected StandardRequirementsService $requirementsService,
        protected GeometryService $geometryService,
        protected SecondaryAnalysisService $secondaryAnalysisService,
        protected SoilNameService $soilNameService
    ) {}

    public function classify(Granulometry $granulometry): GranulometryClassificationResult
    {
        $this->validateData($granulometry);

        $ternaryData = $this->processTernaryData($granulometry);
        $soil = $this->determineSoilType($ternaryData['coordinates']);
        $finalSoil = $this->analyzeSoil($granulometry, $soil);

        return $this->buildClassificationResult($granulometry, $finalSoil, $ternaryData);
    }

    private function validateData(Granulometry $granulometry): void
    {
        $errors = $this->granulometryService->validateGranulometry($granulometry);
        if (!empty($errors)) {
            throw new \InvalidArgumentException('Invalid granulometry data: ' . implode(', ', $errors));
        }
    }

    private function processTernaryData(Granulometry $granulometry): array
    {
        return $this->ternaryDiagramService->processCoordinates(
            $granulometry,
            $this->getRequiredTernaryFractions(),
            $this->getCoordinateValues($granulometry)
        );
    }

    private function determineSoilType(array $coordinates): array
    {
        [$cartesianX, $cartesianY] = $this->geometryService->ternaryToCartesian($coordinates);
        return $this->findPrimarySoil($cartesianX, $cartesianY, $this->getTernaryDiagram());
    }

    private function findPrimarySoil(float $x, float $y, array $domains): ?array
    {
        $soil = $this->ternaryDiagramService->findSoil($x, $y, $domains);
        if (!$soil) {
            throw new \RuntimeException("Cannot determine soil type.");
        }

        return $soil;
    }

    private function analyzeSoil(Granulometry $granulometry, array $soil): array
    {
        if ($this->requiresSecondaryAnalysis($soil)) {
            return $this->secondaryAnalysisService->run($granulometry, $soil, $this->geometryService);
        }

        return $soil;
    }

    private function buildClassificationResult(
        Granulometry $granulometry,
        array $soil,
        array $ternaryCoordinatesData
    ): GranulometryClassificationResult {
        $soilName = $this->soilNameService->build($soil['name'], $granulometry);

        return new GranulometryClassificationResult(
            soilType: $soilName,
            standardInfo: $this->getStandardInfo(),
            metadata: $this->buildMetadata(
                $granulometry,
                $ternaryCoordinatesData['normalizationApplied'],
                $ternaryCoordinatesData['normalizationFactor'],
                $ternaryCoordinatesData['coordinates']
            )
        );
    }

    private function requiresSecondaryAnalysis(array $primaryResult): bool
    {
        return isset($primaryResult['soils']) && count($primaryResult['soils']) > 1;
    }

    protected function buildMetadata(Granulometry $granulometry, bool $normalizationApplied, float $normalizationFactor, array $finalCoordinates): array
    {
        $metadata = $this->buildBasicMetadata($granulometry);
        $metadata['normalization'] = $this->buildNormalizationMetadata($normalizationApplied, $normalizationFactor, $finalCoordinates);

        return $metadata;
    }

    private function buildBasicMetadata(Granulometry $granulometry): array
    {
        return [
            'clay' => $granulometry->clay,
            'sand' => $granulometry->sand,
            'silt' => $granulometry->silt,
            'gravel' => $granulometry->gravel,
            'cobble' => $granulometry->cobble,
            'boulder' => $granulometry->boulder,
            'granulometric_class' => $this->granulometryService->getGranulometricClass($granulometry)
        ];
    }

    private function buildNormalizationMetadata(bool $applied, float $factor, array $coordinates): array
    {
        if (!$applied) {
            return ['applied' => false];
        }

        return [
            'applied' => true,
            'factor' => round($factor, 4),
            'normalized_coordinates' => array_map(fn($coord) => round($coord, 2), $coordinates)
        ];
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

    abstract protected function getRequiredTernaryFractions(): array;

    abstract protected function getCoordinateValues(Granulometry $granulometry): array;

    abstract protected function getTernaryDiagram(): array;

    abstract public function getStandardInfo(): array;
}
