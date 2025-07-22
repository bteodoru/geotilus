<?php

namespace App\Libraries\SoilClassification\Granulometry;

use App\Libraries\SoilClassification\Granulometry\GranulometryClassificationResult;
use App\Libraries\SoilClassification\Granulometry\Services\GranulometryClassificationServiceContainer;

use App\Models\Granulometry;

abstract class GranulometryClassifier
{
    protected string $systemCode;

    public function __construct(
        protected GranulometryClassificationServiceContainer $serviceContainer
    ) {}

    public function classify(Granulometry $granulometry): GranulometryClassificationResult
    {
        $ternaryData = $this->processTernaryData($granulometry);
        $soil = $this->determineSoilType($ternaryData['coordinates']);

        return $this->buildClassificationResult($granulometry, $soil, $ternaryData);
    }


    public function processTernaryData(Granulometry $granulometry): array
    {
        return $this->serviceContainer->ternaryDiagram()->prepareTernaryData(
            $granulometry,
            $this->serviceContainer->granulometry()->extractGranulometricFractions($granulometry, $this->getRequiredTernaryFractions()),
        );
    }

    private function getRequiredTernaryFractions(): array
    {
        return $this->serviceContainer->diagramRepository()->getDiagramForSystem($this->systemCode)['metadata']['axes_order'];
    }

    public function determineSoilType(array $coordinates): array
    {
        [$cartesianX, $cartesianY] = $this->serviceContainer->geometry()->ternaryToCartesian($coordinates);
        return $this->findPrimarySoil($cartesianX, $cartesianY, $this->getTernaryDiagram());
    }

    private function findPrimarySoil(float $x, float $y, array $domains): ?array
    {
        $soil = $this->serviceContainer->ternaryDiagram()->findSoil($x, $y, $domains);
        if (!$soil) {
            throw new \RuntimeException("Cannot determine soil type.");
        }

        return $soil;
    }

    public function buildClassificationResult(
        Granulometry $granulometry,
        array $soil,
        array $ternaryCoordinatesData,
    ): GranulometryClassificationResult {
        $soilName = $this->serviceContainer->soilName()->build($soil['name'], $granulometry);

        return new GranulometryClassificationResult(
            soilType: $soilName,
            standardInfo: $this->getSystemInfo(),
            metadata: $this->buildMetadata(
                $granulometry,
                $ternaryCoordinatesData['normalizationApplied'],
                $ternaryCoordinatesData['normalizationFactor'],
                $ternaryCoordinatesData['coordinates']
            )
        );
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
            'granulometric_class' => $this->serviceContainer->granulometry()->getGranulometricClass($granulometry)
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



    // public function isApplicable(Granulometry $granulometry): bool
    // {
    //     return $this->requirementsService->checkBasicApplicability($granulometry);
    // }

    // public function getRequiredFields(): array
    // {
    //     return $this->requirementsService->getBasicRequirements();
    // }

    public function getAvailableSoilTypes(): array
    {
        return array_keys($this->getTernaryDiagram());
    }

    protected function getTernaryDiagram(): array
    {
        $diagramConfig = $this->serviceContainer->diagramRepository()->getDiagramForSystem($this->systemCode);
        return $diagramConfig['domains'];
    }

    public function getSystemInfo(): array
    {
        $systemConfig = $this->serviceContainer->systemRepository()->getClassificationSystem($this->systemCode);
        return $systemConfig['system_info'];
    }

    // abstract protected function getRequiredTernaryFractions(): array;
    // 
    // abstract protected function getCoordinateValues(Granulometry $granulometry): array;

    // abstract protected function getTernaryDiagram(): array;

    // abstract public function getSystemInfo(): array;
}
