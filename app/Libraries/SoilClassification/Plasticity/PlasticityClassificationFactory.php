<?php

namespace App\Libraries\SoilClassification\Plasticity;

use App\Libraries\SoilClassification\Plasticity\Classifiers\STAS_1243_1988PlasticityClassifier;
use App\Libraries\SoilClassification\Plasticity\Classifiers\CasagrandeClassifier;
use App\Libraries\SoilClassification\Services\CasagrandeChartService;

class PlasticityClassificationFactory
{
    private array $classifiers = [];

    public function __construct()
    {
        $this->registerClassifiers();
    }

    public function create(string $standardCode) //: PlasticityClassifierInterface
    {
        if (!isset($this->classifiers[$standardCode])) {
            throw new \InvalidArgumentException("Unknown plasticity standard: {$standardCode}");
        }

        $classifierFactory = $this->classifiers[$standardCode];
        return $classifierFactory();
    }

    private function registerClassifiers(): void
    {
        $this->classifiers = [
            'sr_en_iso_14688_2018' => function () {
                return new CasagrandeClassifier(
                    app(CasagrandeChartService::class)
                );
            },
            'stas_1243_1988' => function () {
                return new STAS_1243_1988PlasticityClassifier();
            },
        ];
    }

    public function getClassifier(string $type) //: PlasticityClassifierInterface
    {
        if (!isset($this->classifiers[$type])) {
            throw new \InvalidArgumentException("Unknown plasticity classifier type: {$type}");
        }
        return $this->classifiers[$type]();
    }
}
