<?php
// path: app/Libraries/SoilClassification/Granulometry/GranulometryClassifier.php
namespace App\Libraries\SoilClassification\Granulometry;

use App\Libraries\DTOs\GranulometricFraction;
use App\Libraries\SoilClassification\Granulometry\GranulometryClassificationResult;
use App\Libraries\SoilClassification\Granulometry\Services\GranulometryClassificationServiceContainer;
use App\Models\Granulometry;
use App\Models\Sample;

abstract class GranulometryClassifier
{
    protected string $systemCode;

    public function __construct(
        protected GranulometryClassificationServiceContainer $serviceContainer
    ) {}



    private function getAvailableCoarseFractions(Granulometry $granulometry, array $usedFractions): array
    {
        $coarseFractions = ['sand', 'gravel', 'cobble', 'boulder'];
        $availableCoarse = array_diff($coarseFractions, $usedFractions);

        $extractedFractions = $this->serviceContainer->granulometry()->extractGranulometricFractions(
            $granulometry,
            $availableCoarse
        );


        $fractions = [];

        foreach (array_filter($extractedFractions, fn($value) => $value > 0) as $fraction => $percentage) {
            // dd($extractedFractions, $fraction);
            // dd($this->serviceContainer->granulometry()->getFractionName($fraction));
            $fractions[] =
                new GranulometricFraction(
                    name: $this->serviceContainer->granulometry()->getFractionName($fraction),
                    // percentage: $fraction->percentage,
                    symbol: config('granulometry.fractions.simple_fractions.' . $fraction . '.symbol') ?? '',
                    components: [$fraction => $percentage],
                    source: 'direct_extraction',
                    label: $fraction

                );
        }
        return $fractions;
    }

    public function classify(Sample $sample): GranulometryClassificationResult
    {
        $fractions = [];
        if ($this->isUsingTernaryDiagram() && $this->qualifiesForTernaryDiagram($sample->granulometry)) {
            $ternaryOutcome = $this->classifyByTernaryDiagram($sample);
            $usedFractions = array_keys($ternaryOutcome->getComponents());

            $fractions[] = $ternaryOutcome;
        } else {
            if ($this->serviceContainer->granulometry()->hasFineFraction($sample->granulometry)) {
                $fineGrained = $this->classifyFineFraction($sample);
                $usedFractions = ['clay', 'silt'];
                $fractions[] = $fineGrained;
            }
        }


        $coarseFractions = $this->getAvailableCoarseFractions($sample->granulometry, $usedFractions ?? []);
        $fractions = array_merge($fractions, $coarseFractions);

        return new GranulometryClassificationResult(
            classificationSystem: $this->systemCode,
            granulometry: $sample->granulometry,
            plasticity: $sample->plasticity,
            gradingParameters: [],
            fractions: $fractions,
        );
    }



    public function classifyByTernaryDiagram(Sample $sample): GranulometricFraction
    {
        $granulometry = $sample->granulometry;
        $ternaryData = $this->processTernaryData($granulometry);
        $soil = $this->determineSoilType($ternaryData['coordinates']);
        // dd($ternaryData, $soil);


        $components = array_map(function ($element) use ($ternaryData) {
            // dd($element, $ternaryData['normalizationFactor']);
            return $element / $ternaryData['normalizationFactor'];
        }, $ternaryData['coordinates']);


        return new GranulometricFraction(
            name: $soil['name'],
            components: $components,
            source: 'ternary_diagram',

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
        try {
            $diagram = $this->serviceContainer->diagramRepository()->getDiagramForSystem($this->systemCode);
            return $diagram['metadata']['axes_order'];
        } catch (\Exception $e) {
            return [];
        }
    }

    private function isUsingTernaryDiagram(): bool
    {
        try {
            $this->serviceContainer->diagramRepository()->getDiagramForSystem($this->systemCode);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    private function qualifiesForTernaryDiagram(Granulometry $granulometry): bool
    {
        $requiredTernaryFractions = $this->serviceContainer
            ->granulometry()
            ->extractGranulometricFractions($granulometry, $this->getRequiredTernaryFractions());

        return array_sum($requiredTernaryFractions) > 0;
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


    abstract public function getGradationInformation(Granulometry $granulometry): ?string;
}
