<?php

namespace App\Libraries\SoilClassification\Granulometry;

use App\Libraries\SoilClassification\Contracts\GranulometryClassifierInterface;
use App\Libraries\SoilClassification\Granulometry\Classifiers\NP_074_2022GranulometryClassifier;
use App\Libraries\SoilClassification\Granulometry\Classifiers\SR_EN_ISO_14688_2005GranulometryClassifier;
use App\Libraries\SoilClassification\Services\GranulometryService;
use App\Libraries\SoilClassification\Granulometry\Classifiers\STAS_1243_1988GranulometryClassifier;
use App\Libraries\SoilClassification\Services\StandardRequirementsService;
use App\Libraries\SoilClassification\Services\TernaryDiagramService;
use App\Models\Granulometry;
use App\Services\GeometryService;

/**
 * Factory pentru crearea clasificatorilor granulometrici
 */
class GranulometryClassificationFactory
{
    private GranulometryService $granulometryService;
    private array $classifiers = [];

    public function __construct(GranulometryService $granulometryService)
    {
        $this->granulometryService = $granulometryService;
        $this->registerClassifiers();
    }

    /**
     * Creează un clasificator pentru un standard specific
     */
    public function create(string $standardCode): GranulometryClassifierInterface
    {
        if (!isset($this->classifiers[$standardCode])) {
            throw new \InvalidArgumentException("Unknown granulometry standard: {$standardCode}");
        }

        $classifierFactory = $this->classifiers[$standardCode];
        return $classifierFactory();
    }

    /**
     * Returnează toate standardele disponibile
     */
    public function getAvailableStandards(): array
    {
        $standards = [];

        foreach ($this->classifiers as $code => $classifierFactory) {
            $classifier = $classifierFactory();
            $standards[$code] = $classifier->getStandardInfo();
        }

        return $standards;
    }

    /**
     * Găsește standardele aplicabile pentru datele respective
     */
    public function getApplicableStandards(Granulometry $granulometry): array
    {
        $applicable = [];

        foreach ($this->classifiers as $code => $classifierFactory) {
            $classifier = $classifierFactory();

            if ($classifier->isApplicable($granulometry)) {
                $applicable[$code] = $classifier->getStandardInfo();
            }
        }

        return $applicable;
    }

    /**
     * Înregistrează clasificatorii disponibili
     */
    private function registerClassifiers(): void
    {
        $this->classifiers = [
            'stas_1243_1988' => function () {
                return new STAS_1243_1988GranulometryClassifier(
                    $this->granulometryService,
                    app(TernaryDiagramService::class),
                    app(StandardRequirementsService::class),
                    app(GeometryService::class)
                );
            },
            'np_074_2022' => function () {
                return new NP_074_2022GranulometryClassifier(
                    $this->granulometryService,
                    app(TernaryDiagramService::class),
                    app(StandardRequirementsService::class),
                    app(GeometryService::class)

                );
            },
            'sr_en_iso_14688_2005' => function () {
                return new SR_EN_ISO_14688_2005GranulometryClassifier(
                    $this->granulometryService,
                    app(TernaryDiagramService::class),
                    app(StandardRequirementsService::class),
                    app(GeometryService::class)

                );
            },


        ];
    }
}
