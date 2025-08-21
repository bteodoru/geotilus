<?php
// path: app/Libraries/SoilClassification/Granulometry/GranulometryClassifier.php
namespace App\Libraries\SoilClassification\Granulometry;

use App\Libraries\DTOs\GranulometricFraction;
use App\Libraries\SoilClassification\Granulometry\GranulometryClassificationResult;
use App\Libraries\SoilClassification\Granulometry\Services\GranulometryClassificationServiceContainer;
use App\Libraries\SoilClassification\Plasticity\PlasticityClassificationFactory;
use App\Models\AtterbergLimit;
use App\Models\Granulometry;
use App\Models\Sample;
use Illuminate\Support\Arr;

abstract class GranulometryClassifier
{
    protected string $systemCode;
    protected $thresholds = [50, 25];

    public function __construct(
        protected GranulometryClassificationServiceContainer $serviceContainer
    ) {}
    protected function getGraphicalMethod(): ?string
    {
        $config = $this->serviceContainer->systemRepository()
            ->getCriterionConfiguration($this->systemCode, 'granulometry');

        $method = $config['graphical_method'] ?? 'none';

        return $method === 'none' ? null : $method;
    }

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

    private function getAvailableCoarseFractions(Granulometry $granulometry, array $usedFractions): array
    {
        $coarseFractions = ['sand', 'gravel', 'cobble', 'boulder'];
        $availableCoarse = array_diff($coarseFractions, $usedFractions);

        $extractedFractions = $this->serviceContainer->granulometry()->extractGranulometricFractions(
            $granulometry,
            $availableCoarse
        );

        // return array_filter(
        //     $extractedFractions,
        //     fn($value) => $value > 0
        // );
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

                // 'name' => $this->serviceContainer->granulometry()->getFractionName($fraction),
                // 'percentage' => $percentage,
                // 'class' => 'coarse',
            ;
        }
        return $fractions;
    }



    public function classify(Sample $sample) //: GranulometryClassificationResult
    {
        $fractions = [];
        // dd($this->qualifiesForTernaryDiagram($sample->granulometry));
        if ($this->isUsingTernaryDiagram() && $this->qualifiesForTernaryDiagram($sample->granulometry)) {
            $ternaryOutcome = $this->classifyByTernaryDiagram($sample);
            $usedFractions = array_keys($ternaryOutcome->getComponents());
            // if ($ternaryOutcome->isSimple()) {
            //     $res = $this->serviceContainer->granulometry()->getFractionName($ternaryOutcome->getPrimaryComponent());
            // }
            // dd($res);
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
            metadata: $this->buildBasicMetadata($sample->granulometry)
        );
        // dd($coarseFractions);

        // return $this->createClassificationResult($sample->granulometry, [
        //     'ternary' => $ternaryOutcome ?? null,
        //     'fine' => $fineGrained ?? null,
        //     'coarse' => $coarseFractions,
        // ]);


        // return ['ternary' => $ternaryOutcome ?? null, 'fine' => $fineGrained ?? null, 'coarse' => $coarseFractions]; // $this->processCasagrandeChartClassification($sample);
    }

    public function classifyFineFraction(Sample $sample) //: GranulometricFraction
    {
        return;
    }
    public function classify________(Sample $sample): GranulometryClassificationResult
    {
        $granulometry = $sample->granulometry;
        $fractions = [];
        $usedFractions = [];

        // Process graphical classification if available
        $graphicalMethod = $this->getGraphicalMethod();
        if ($graphicalMethod) {
            $graphicalResult = $this->processGraphicalClassification($sample, $graphicalMethod);
            $fractions[] = $graphicalResult['fraction'];
            $usedFractions = $graphicalResult['used_fractions'];
        }

        // Process remaining fractions
        $remainingFractions = $this->processRemainingFractions($granulometry, $usedFractions);
        $fractions = array_merge($fractions, $remainingFractions);

        // Sort fractions by percentage (descending)
        $sortedFractions = $this->sortFractionsByPercentage($fractions);

        return $this->createClassificationResult($granulometry, $sortedFractions);
    }

    protected function processGraphicalClassification(Sample $sample, string $method): array
    {

        return match ($method) {
            'ternary_diagram' => $this->processTernaryDiagramClassification($sample),
            'casagrande_chart' => $this->processCasagrandeChartClassification($sample),
            default => throw new \InvalidArgumentException("Unknown graphical method: {$method}")
        };
    }

    private function processTernaryDiagramClassification(Sample $sample): array
    {
        $soilName = $this->classifyByDiagram($sample);
        $requiredFractions = $this->getRequiredTernaryFractions();
        $expandedFractions = $this->serviceContainer->granulometry()->expandGranulometricFractions($requiredFractions);
        $ternaryFractions = $this->serviceContainer->granulometry()->extractGranulometricFractions($sample->granulometry, $expandedFractions);

        if (array_sum($ternaryFractions) <= 0) {
            throw new \RuntimeException('No valid ternary fractions found for classification');
        }

        return [
            'fraction' => [
                'soil' => $soilName,
                'class' => $this->getTernarySoilClass($ternaryFractions),
                'percentage' => array_sum($ternaryFractions),
            ],
            'used_fractions' => $requiredFractions
        ];
    }

    private function processCasagrandeChartClassification(Sample $sample): array
    {
        $soilName = $this->classifyByDiagram($sample);

        return [
            'fraction' => [
                'soil' => $soilName,
                'class' => 'fine',
                'percentage' => $sample->granulometry->clay + $sample->granulometry->silt,
            ],
            'used_fractions' => ['clay', 'silt']
        ];
    }

    private function processRemainingFractions(Granulometry $granulometry, array $usedFractions): array
    {
        $allFractions = $this->serviceContainer->granulometry()->getAllFractionNames();
        $expandedUsedFractions = $this->serviceContainer->granulometry()->expandGranulometricFractions($usedFractions);
        $fractionsToAnalyze = array_diff(array_keys($allFractions), $expandedUsedFractions);

        $extractedFractions = $this->serviceContainer->granulometry()->extractGranulometricFractions($granulometry, $fractionsToAnalyze);
        $activeFractions = array_filter($extractedFractions, fn($percentage) => $percentage > 0);

        $fractions = [];
        foreach ($activeFractions as $fraction => $percentage) {
            $fractions[] = [
                'soil' => $allFractions[$fraction]['name'],
                'class' => $allFractions[$fraction]['class'],
                'percentage' => $percentage,
            ];
        }

        return $fractions;
    }

    private function sortFractionsByPercentage(array $fractions): array
    {
        $sortedFractions = Arr::sortDesc($fractions, function ($fraction) {
            return $fraction['percentage'];
        });

        return array_values($sortedFractions);
    }

    private function createClassificationResult(Granulometry $granulometry, array $sortedFractions): GranulometryClassificationResult
    {
        return new GranulometryClassificationResult(
            classificationSystem: $this->systemCode,
            granulometry: $granulometry,
            plasticity: [],
            gradingParameters: [],
            fractions: $sortedFractions,
            metadata: $this->buildBasicMetadata($granulometry)
        );
    }

    private function getTernarySoilClass($ternaryFractions): string
    {
        if ($ternaryFractions['clay'] + $ternaryFractions['silt'] > 0.5 * array_sum($ternaryFractions)) {
            return 'fine';
        }
        return 'coarse';
    }

    public function classify___(Granulometry $granulometry): GranulometryClassificationResult
    {
        // dd($this->isUsingTernaryDiagram());
        if ($this->serviceContainer->granulometry()->hasFineFraction($granulometry)) {
            if ($this->supportsClass('fine')) {
                return $this->classifyFineSoil($granulometry);
            }
            return $this->classifyFineSoilByPlasticity($granulometry->sample);
        }
        return $this->classifyCoarseSoil($granulometry);
    }
    public function classify_(Granulometry $granulometry): GranulometryClassificationResult
    {
        if ($this->serviceContainer->granulometry()->hasFineFraction($granulometry)) {
            if ($this->supportsClass('fine')) {
                return $this->classifyFineSoil($granulometry);
            }
            $genericSoil = 'Pământ fin';
            $soilName = $this->serviceContainer->soilName()
                ->build($genericSoil, $granulometry, ['clay', 'silt'], $this->thresholds);

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

    public function classifyFineSoil____(Granulometry $granulometry) //: GranulometryClassificationResult
    {
        $ternaryData = $this->processTernaryData($granulometry);
        $soil = $this->determineSoilType($ternaryData['coordinates']);
        // dd($ternaryData, $soil);

        return $soil['name'];
    }
    public function classifyByDiagram(Sample $sample) //: GranulometryClassificationResult
    {
        $granulometry = $sample->granulometry;
        $ternaryData = $this->processTernaryData($granulometry);
        $soil = $this->determineSoilType($ternaryData['coordinates']);
        // dd($ternaryData, $soil);

        return $soil['name'];
    }

    public function classifyByTernaryDiagram(Sample $sample) //: GranulometryClassificationResult
    {
        $granulometry = $sample->granulometry;
        $ternaryData = $this->processTernaryData($granulometry);
        $soil = $this->determineSoilType($ternaryData['coordinates']);
        // dd($ternaryData, $soil);

        $requiredTernaryFractions = $this->serviceContainer
            ->granulometry()
            ->extractGranulometricFractions($granulometry, $this->getRequiredTernaryFractions());

        $components = array_map(function ($element) use ($ternaryData) {
            // dd($element, $ternaryData['normalizationFactor']);
            return $element / $ternaryData['normalizationFactor'];
        }, $ternaryData['coordinates']);
        // $components = array_map(function ($element) use ($ternaryData) {
        //     return $element / $ternaryData['normalizationFactor'];
        // }, array_filter($ternaryData['coordinates'], function ($element) {
        //     return $element > 0;
        // }));

        return new GranulometricFraction(
            name: $soil['name'],
            components: $components,
            source: 'ternary_diagram',

        );

        // return $soil['name'];
    }

    protected function classifyFineSoil_(Granulometry $granulometry): GranulometryClassificationResult
    {
        $ternaryData = $this->processTernaryData($granulometry);
        $soil = $this->determineSoilType($ternaryData['coordinates']);
        // dd($ternaryData, $soil);

        return $this->buildClassificationResult($granulometry, $soil, $ternaryData);
    }

    protected function classifyFineSoilByPlasticity(Sample $sample): GranulometryClassificationResult
    {
        $plasticityFactory = new PlasticityClassificationFactory();
        $plasticityClassifier = $plasticityFactory->create($this->systemCode);
        $primaryFraction = $plasticityClassifier->classify($sample->plasticity);
        if ($this->serviceContainer->granulometry()->isFine($sample->granulometry)) {

            return new GranulometryClassificationResult(
                primaryClassification: $this->serviceContainer->granulometry()->getFractionName($primaryFraction->getSoilType()),
                classificationSystem: $this->systemCode,
                granulometry: $sample->granulometry,
                plasticity: [
                    'liquid_limit' => $sample->plasticity->liquid_limit,
                    'plastic_limit' => $sample->plasticity->plastic_limit,
                    'plasticity_index' => $sample->plasticity->liquid_limit - $sample->plasticity->plastic_limit,
                ],
                gradingParameters: [],
                metadata: [
                    'used_fractions' => ['clay', 'silt'],
                ],

            );
        }

        $granulometricClass = $this->serviceContainer->granulometry()
            ->getGranulometricClass($sample->granulometry);
        $soil = $this->serviceContainer->granulometry()
            ->getDominantFractionInCategory($sample->granulometry, $granulometricClass);
        $soilName = $this->serviceContainer->granulometry()->getFractionName(array_keys($soil)[0]) . ' ' . $this->serviceContainer->granulometry()->getAllFractionNames()[$primaryFraction->getSoilType()]['adjective'][1];
        return new GranulometryClassificationResult(
            primaryClassification: $soilName,
            classificationSystem: $this->systemCode,
            granulometry: $sample->granulometry,
            plasticity: [],
            gradingParameters: [],
            metadata: [
                'used_fractions' => ['clay', 'silt', array_keys($soil)[0]],
                // 'used_fractions' => array_keys($this->serviceContainer->granulometry()->getAllFractionNames()),
            ]
        );
    }

    protected function classifyCoarseSoil(Granulometry $granulometry): GranulometryClassificationResult
    {
        $granulometricClass = $this->serviceContainer->granulometry()
            ->getGranulometricClass($granulometry);
        $soil = $this->serviceContainer->granulometry()
            ->getDominantFractionInCategory($granulometry, $granulometricClass);
        // dd($soil);
        // $soilName = $this->serviceContainer->soilName()
        //     ->build($this->serviceContainer->granulometry()->getFractionName(array_keys($soil)[0]), $granulometry, array_keys($soil), [50, 25], $this->getGradationInformation($granulometry));
        // dd([array_keys($soil)[0]]);
        return new GranulometryClassificationResult(
            primaryClassification: $this->serviceContainer->granulometry()->getFractionName(array_keys($soil)[0]),
            classificationSystem: $this->systemCode,
            granulometry: $granulometry,
            plasticity: [],
            gradingParameters: [],
            // metadata: [],
            // soilType: $soilName,
            // standardInfo: $this->getSystemInfo(),
            metadata: [
                'used_fractions' => [array_keys($soil)[0]],
                // 'granulometry' => $granulometry,
            ] + $this->buildBasicMetadata(
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

    public function buildClassificationResult(
        Granulometry $granulometry,
        array $soil,
        array $ternaryCoordinatesData,
    ): GranulometryClassificationResult {
        // $soilName = $this->serviceContainer->soilName()->build($soil['name'], $granulometry, $this->getRequiredTernaryFractions(), $this->thresholds);
        // $soilName = $this->serviceContainer->soilName()->build($soil['name'], $granulometry, ['sand'], $this->thresholds);

        return new GranulometryClassificationResult(
            primaryClassification: $soil['name'],
            // primaryClassification: $soilName,
            // classificationSystem: $this->getSystemInfo(),
            classificationSystem: $this->systemCode,
            granulometry: $granulometry,
            plasticity: [],
            gradingParameters: [],
            // metadata: [],
            // soilType: $soilName,
            // standardInfo: $this->getSystemInfo(),
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
        $metadata['used_fractions'] = $this->getRequiredTernaryFractions();

        $metadata['normalization'] = $this->buildNormalizationMetadata($normalizationApplied, $normalizationFactor, $finalCoordinates);

        return $metadata;
    }

    private function buildBasicMetadata(Granulometry $granulometry): array
    {
        return [
            // 'clay' => $granulometry->clay,
            // 'sand' => $granulometry->sand,
            // 'silt' => $granulometry->silt,
            // 'gravel' => $granulometry->gravel,
            // 'cobble' => $granulometry->cobble,
            // 'boulder' => $granulometry->boulder,
            // 'granulometry' => $granulometry,
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

    // abstract protected function classifyFineFraction(Sample $sample): GranulometricFraction;
}
