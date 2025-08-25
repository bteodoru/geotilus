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

    public function __construct(
        protected GranulometryClassificationServiceContainer $serviceContainer
    ) {}

    public static function getDependencies(): array
    {
        return [GranulometryClassificationServiceContainer::class];
    }

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
            classificationSystem: $this->getSystemCode(),
            granulometry: $sample->granulometry,
            plasticity: $sample->plasticity,
            gradationInformation: $this->performGradationAnalysis($sample->granulometry),
            fractions: $fractions,

        );
    }

    private function performGradationAnalysis(Granulometry $granulometry): array
    {
        // $gradation = $granulometry->clay + $granulometry->silt > 50 ? null : $this->getGradation($granulometry);

        if ($granulometry->clay + $granulometry->silt > 50) {
            $gradation = null;
        } else {
            $gradation = $this->getGradation($granulometry);
        }

        return [
            'gradation' => $gradation,
            'Cu' => $granulometry->cu,
            'Cc' => $granulometry->cc,
        ];
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
            $diagram = $this->serviceContainer->diagramRepository()->getDiagramForSystem($this->getSystemCode());
            return $diagram['metadata']['axes_order'];
        } catch (\Exception $e) {
            return [];
        }
    }

    private function isUsingTernaryDiagram(): bool
    {
        try {
            $this->serviceContainer->diagramRepository()->getDiagramForSystem($this->getSystemCode());
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
        $diagramConfig = $this->serviceContainer->diagramRepository()->getDiagramForSystem($this->getSystemCode());
        return $diagramConfig['domains'];
    }

    public function getSystemInfo(): array
    {
        $systemConfig = $this->serviceContainer->systemRepository()->findByCode($this->getSystemCode());
        return $systemConfig['system_info'];
    }

    public function getSystemCode(): string
    {
        $className = class_basename(static::class);

        // Elimină sufixul 'GranulometryClassifier'
        $systemPart = substr($className, 0, -strlen('GranulometryClassifier'));

        // Convertește din PascalCase în snake_case
        return $this->convertToSnakeCase($systemPart);
    }

    /**
     * Convertește din PascalCase în snake_case
     */
    private function convertToSnakeCase(string $input): string
    {
        // Inserează underscore înaintea cifrelor precedate de litere
        $result = preg_replace('/([a-zA-Z])(\d)/', '$1_$2', $input);

        // Inserează underscore înaintea literelor mari precedate de litere mici
        $result = preg_replace('/([a-z])([A-Z])/', '$1_$2', $result);

        return strtolower($result);
    }

    abstract public function getGradation(Granulometry $granulometry): ?string;
}
