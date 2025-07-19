<?php

namespace App\Libraries\SoilClassification\Plasticity;

use App\Libraries\SoilClassification\Contracts\PlasticityClassifierInterface;
use App\Libraries\SoilClassification\Plasticity\Classifiers\CasagrandeClassifier;
use App\Libraries\SoilClassification\Services\CasagrandeChartService;

class PlasticityClassificationFactory
{
    private array $classifiers = [];

    public function __construct()
    {
        $this->registerClassifiers();
    }

    public function create(string $standardCode): PlasticityClassifierInterface
    {
        if (!isset($this->classifiers[$standardCode])) {
            throw new \InvalidArgumentException("Unknown granulometry standard: {$standardCode}");
        }

        $classifierFactory = $this->classifiers[$standardCode];
        return $classifierFactory();
    }

    private function registerClassifiers(): void
    {
        $this->classifiers = [
            'casagrande' => function () {
                return new CasagrandeClassifier(
                    app(CasagrandeChartService::class)
                );
            },
            // 'sr_en_iso_14688_2_2018' => function () {
            //     return new SR_EN_ISO_14688_2_2018_Classifier();
            // },
        ];
    }

    public function getClassifier(string $type): PlasticityClassifierInterface
    {
        if (!isset($this->classifiers[$type])) {
            throw new \InvalidArgumentException("Unknown plasticity classifier type: {$type}");
        }
        return $this->classifiers[$type]();
    }
}
