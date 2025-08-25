<?php

namespace App\Libraries\SoilClassification\Granulometry;

use App\Libraries\SoilClassification\Granulometry\GranulometryClassifier;
use App\Libraries\SoilClassification\Granulometry\Classifiers\STAS_1243_1988GranulometryClassifier;
use App\Libraries\SoilClassification\Granulometry\Classifiers\NP_074_2022GranulometryClassifier;
use App\Libraries\SoilClassification\Granulometry\Classifiers\SR_EN_ISO_14688_2005GranulometryClassifier;
use App\Libraries\SoilClassification\Granulometry\Classifiers\SR_EN_ISO_14688_2018GranulometryClassifier;
use App\Libraries\SoilClassification\Granulometry\Services\GranulometryClassificationServiceContainer;
use App\Models\Granulometry;

/**
 * Factory pentru crearea clasificatorilor granulometrici
 */
class GranulometryClassificationFactory
{

    private array $classifiers = [];

    public function __construct()
    {
        $this->registerClassifiers();
    }

    /**
     * Creează un clasificator pentru un standard specific
     */
    public function create(string $standardCode): GranulometryClassifier
    {

        if (!isset($this->classifiers[$standardCode])) {
            throw new \InvalidArgumentException("Unknown granulometry standard: {$standardCode}");
        }

        $classifierFactory = $this->classifiers[$standardCode];
        return $classifierFactory();
    }


    /**
     * Găsește standardele aplicabile pentru datele respective
     */
    public function getApplicableSystems(Granulometry $granulometry): array
    {
        $applicable = [];

        foreach ($this->classifiers as $code => $classifierFactory) {
            $classifier = $classifierFactory();

            $applicable[$code] = $classifier->getSystemInfo();
        }

        return $applicable;
    }

    /**
     * Înregistrează clasificatorii disponibili
     */
    private function registerClassifiers(): void
    {
        $this->classifiers = [
            'stas_1243_1988' => fn() => new STAS_1243_1988GranulometryClassifier(
                GranulometryClassificationServiceContainer::create()
            ),
            'np_074_2022' => fn() => new NP_074_2022GranulometryClassifier(
                GranulometryClassificationServiceContainer::create()
            ),
            'sr_en_iso_14688_2005' => fn() => new SR_EN_ISO_14688_2005GranulometryClassifier(
                GranulometryClassificationServiceContainer::create()
            ),
            'sr_en_iso_14688_2018' => fn() => new SR_EN_ISO_14688_2018GranulometryClassifier(
                GranulometryClassificationServiceContainer::create()
            ),
        ];
    }
}
