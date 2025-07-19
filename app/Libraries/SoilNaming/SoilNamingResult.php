<?php

namespace App\Libraries\SoilNaming;

class SoilNamingResult
{
    public function __construct(
        public string $soilName,
        public string $namingMethod,
        public array $standardInfo,
        public ?object $granulometryResult = null,
        public ?object $plasticityResult = null,
        public float $confidence = 0.0,
        public string $classificationCertainty = 'definite',
        public array $metadata = []
    ) {}

    public function isAmbiguous(): bool
    {
        return $this->classificationCertainty === 'ambiguous';
    }

    public function requiresVerification(): bool
    {
        return $this->metadata['requires_verification'] ?? false;
    }

    public function toArray(): array
    {
        return [
            'final_soil_name' => $this->soilName,
            'classification_method' => $this->namingMethod,
            'standard_info' => $this->standardInfo,
            'granulometry_result' => $this->granulometryResult,
            'plasticity_result' => $this->plasticityResult,
            'confidence' => $this->confidence,
            'classification_certainty' => $this->classificationCertainty,
            'metadata' => $this->metadata
        ];
    }
}
