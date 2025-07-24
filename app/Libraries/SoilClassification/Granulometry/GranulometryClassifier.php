<?php

namespace App\Libraries\SoilClassification\Granulometry;

use App\Libraries\SoilClassification\Granulometry\GranulometryClassificationResult;
use App\Libraries\SoilClassification\Granulometry\Services\GranulometryClassificationServiceContainer;

use App\Models\Granulometry;

abstract class GranulometryClassifier
{
    protected string $systemCode;
    protected $thresholds = [50, 25];

    public function __construct(
        protected GranulometryClassificationServiceContainer $serviceContainer
    ) {}

    protected function getSupportedClasses(): array
    {
        $granulometryConfig = $this->serviceContainer->systemRepository()
            ->getCriterionConfiguration($this->systemCode, 'granulometry');

        return $granulometryConfig['applicable_granulometric_classes'] ?? [];
    }

    /**
     * Verifică dacă acest sistem suportă o clasă granulometrică specifică
     */
    protected function supportsClass(string $granulometricClass): bool
    {
        return in_array($granulometricClass, $this->getSupportedClasses());
    }

    /**
     * Verifică dacă o probă este aplicabilă pentru acest sistem
     */
    public function isApplicable_(Granulometry $granulometry): bool
    {
        // Determinăm clasa granulometrică
        $granulometricClass = $this->serviceContainer->granulometry()
            ->getGranulometricClass($granulometry);

        // Verificăm dacă sistemul suportă această clasă
        if (!$this->supportsClass($granulometricClass)) {
            return false;
        }

        return true;
    }

    public function classify(Granulometry $granulometry): GranulometryClassificationResult
    {
        if ($this->serviceContainer->granulometry()->hasFineFraction($granulometry)) {
            if ($this->supportsClass('fine')) {
                return $this->classifyFineSoil($granulometry);
            }
            $soil = 'Pământ fin';
            $soilName = $this->serviceContainer->soilName()
                ->build($soil, $granulometry, [], $this->thresholds);

            return new GranulometryClassificationResult(
                soilType: $soilName,
                standardInfo: $this->getSystemInfo(),
                metadata: $this->buildMetadata(
                    $granulometry,
                    false,
                    false,
                    []
                )
            );
        }
        return $this->classifyCoarseSoil($granulometry);
    }

    protected function classifyFineSoil(Granulometry $granulometry): GranulometryClassificationResult
    {
        $ternaryData = $this->processTernaryData($granulometry);
        $soil = $this->determineSoilType($ternaryData['coordinates']);

        return $this->buildClassificationResult($granulometry, $soil, $ternaryData);
    }

    protected function classifyCoarseSoil(Granulometry $granulometry): GranulometryClassificationResult
    {
        $granulometricClass = $this->serviceContainer->granulometry()
            ->getGranulometricClass($granulometry);
        $soil = $this->serviceContainer->granulometry()
            ->getDominantFractionInCategory($granulometry, $granulometricClass);

        $soilName = $this->serviceContainer->soilName()
            ->build($this->serviceContainer->granulometry()->getFractionName(array_keys($soil)[0]), $granulometry, array_keys($soil), [50, 25]);
        return new GranulometryClassificationResult(
            soilType: $soilName,
            standardInfo: $this->getSystemInfo(),
            metadata: $this->buildBasicMetadata(
                $granulometry
            )
        );
    }


    public function processTernaryData(Granulometry $granulometry): array
    {
        return $this->serviceContainer->ternaryDiagram()->prepareTernaryData(
            $granulometry,
            $this->serviceContainer->granulometry()->extractGranulometricFractions($granulometry, $this->getRequiredTernaryFractions()),
        );
    }

    public function getRequiredTernaryFractions(): array
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
        $soilName = $this->serviceContainer->soilName()->build($soil['name'], $granulometry, $this->getRequiredTernaryFractions(), $this->thresholds);
        // $soilName = $this->serviceContainer->soilName()->build($soil['name'], $granulometry, ['sand'], $this->thresholds);

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
        $systemConfig = $this->serviceContainer->systemRepository()->findByCode($this->systemCode);
        return $systemConfig['system_info'];
    }

    // abstract protected function getRequiredTernaryFractions(): array;
    // 
    // abstract protected function getCoordinateValues(Granulometry $granulometry): array;

    // abstract protected function getTernaryDiagram(): array;

    // abstract public function getSystemInfo(): array;
    abstract public function getGradationInformation(Granulometry $granulometry): ?string;
}
