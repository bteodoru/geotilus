<?php

namespace App\Libraries\SoilNaming;

use App\Libraries\SoilNaming\SoilNamingInterface;
use App\Libraries\SoilNaming\Implementations\STAS_1243_1988_Naming;
use App\Libraries\SoilNaming\Implementations\SR_EN_ISO_14688_2005_Naming;
use App\Libraries\SoilNaming\Implementations\SR_EN_ISO_14688_2018_Naming;
use App\Libraries\SoilClassification\Granulometry\GranulometryClassificationFactory;
use App\Libraries\SoilClassification\Plasticity\PlasticityClassificationFactory;
use App\Models\Sample;

class SoilNamingFactory
{
    private array $namingSystems = [];

    public function __construct(
        private GranulometryClassificationFactory $granulometryFactory,
        private PlasticityClassificationFactory $plasticityFactory
    ) {
        $this->registerNamingSystems();
    }

    /**
     * Creează un sistem de denumire pentru un standard specific
     */
    public function create(string $standardCode): SoilNamingInterface
    {
        if (!isset($this->namingSystems[$standardCode])) {
            throw new \InvalidArgumentException("Unknown soil naming standard: {$standardCode}");
        }

        $namingSystemFactory = $this->namingSystems[$standardCode];
        return $namingSystemFactory();
    }

    /**
     * Returnează toate sistemele de denumire disponibile
     */
    public function getAvailableNamingSystems(): array
    {
        $systems = [];

        foreach ($this->namingSystems as $code => $namingSystemFactory) {
            $namingSystem = $namingSystemFactory();
            $systems[$code] = $namingSystem->getStandardInfo();
        }

        return $systems;
    }

    /**
     * Găsește sistemele aplicabile pentru un sample
     */
    public function getApplicableNamingSystems(Sample $sample): array
    {
        $applicable = [];

        foreach ($this->namingSystems as $code => $namingSystemFactory) {
            $namingSystem = $namingSystemFactory();

            if ($namingSystem->isApplicable($sample)) {
                $applicable[$code] = $namingSystem->getStandardInfo();
            }
        }

        return $applicable;
    }

    /**
     * Înregistrează sistemele de denumire disponibile
     */
    private function registerNamingSystems(): void
    {
        $this->namingSystems = [
            'stas_1243_1988' => function () {
                return new STAS_1243_1988_Naming(
                    $this->granulometryFactory
                );
            },
            'sr_en_iso_14688_2005' => function () {
                return new SR_EN_ISO_14688_2005_Naming(
                    $this->granulometryFactory
                );
            },
            'sr_en_iso_14688_2018' => function () {
                return new SR_EN_ISO_14688_2018_Naming(
                    $this->granulometryFactory,
                    $this->plasticityFactory
                );
            },
        ];
    }
}
