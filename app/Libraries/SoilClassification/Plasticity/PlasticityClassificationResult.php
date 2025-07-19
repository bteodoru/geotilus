<?php

namespace App\Libraries\SoilClassification\Plasticity;

class PlasticityClassificationResult
{

    private string $soilType;
    private string $soilCode;
    private string $plasticity;
    private string $classificationCertainty; // 'definite' or 'ambiguous'
    private array $standardInfo;
    private array $metadata;

    public function __construct(
        string $soilType,
        string $soilCode,
        string $plasticity,
        string $classificationCertainty,
        array $standardInfo,
        array $metadata = []
    ) {
        $this->soilType = $soilType;
        $this->soilCode = $soilCode;
        $this->plasticity = $plasticity;
        $this->classificationCertainty = $classificationCertainty;
        $this->standardInfo = $standardInfo;
        $this->metadata = $metadata;
    }

    public function toArray(): array
    {
        return [
            'soil_type' => $this->soilType,
            'soil_code' => $this->soilCode,
            'plasticity' => $this->plasticity,
            'classification_certainty' => $this->classificationCertainty,
            'standard_info' => $this->standardInfo,
            'metadata' => $this->metadata
        ];
    }

    public function getSoilType(): string
    {
        return $this->soilType . ' cu plasticitate ' . $this->plasticity . ' (' . $this->soilCode . ')';
    }

    public function isAmbiguous(): bool
    {
        return $this->classificationCertainty === 'ambiguous';
    }

    public function requiresVerification(): bool
    {
        return $this->metadata['requires_verification'] ?? false;
    }

    public function getCertainty(): string
    {
        return $this->classificationCertainty;
    }

    public function getUserMessage(): string
    {
        return $this->metadata['user_message'] ?? 'Clasificarea a fost efectuată cu succes.';
    }

    public function getAmbigousDomains(): array
    {
        if ($this->isAmbiguous()) {
            return $this->metadata['ambiguous_domains'];
        }
        return [];
    }
}
