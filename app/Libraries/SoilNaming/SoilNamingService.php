<?php 
// App/Libraries/SoilClassification/SoilNaming/Services/SoilNamingService.php
name
class SoilNamingService
{
    public function getSoilName(
        GranulometryClassificationResult $granulometry,
        PlasticityClassificationResult $plasticity,
        string $standard
    ): SoilNamingResult
    {
        $strategy = $this->getStrategy($standard);
        return $strategy->determineName($granulometry, $plasticity);
    }
}

// App/Libraries/SoilClassification/SoilNaming/Results/SoilNamingResult.php
class SoilNamingResult
{
    public function __construct(
        public readonly string $finalSoilName,
        public readonly GranulometryClassificationResult $granulometryResult,
        public readonly PlasticityClassificationResult $plasticityResult,
        public readonly string $namingMethod
    ) {}
}

// App/Libraries/SoilClassification/SoilNaming/Strategies/SREN_ISO_14688_2_2018_NamingStrategy.php
class SREN_ISO_14688_2_2018_NamingStrategy implements SoilNamingStrategyInterface
{
    public function determineName(
        GranulometryClassificationResult $granulometry,
        PlasticityClassificationResult $plasticity
    ): SoilNamingResult
    {
        // Logica de combinare granulometrie + plasticitate
    }
}