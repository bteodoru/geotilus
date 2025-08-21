<?php

namespace App\Libraries\SoilClassification\Granulometry;

class GranulometryClassificationResult
{

    private string $soilType;
    private array $standardInfo;
    private array $metadata;

    public function __construct(
        string $soilType,
        array $standardInfo,
        array $metadata = []
    ) {
        $this->soilType = $soilType;
        $this->standardInfo = $standardInfo;
        $this->metadata = $metadata;
    }

    public function toArray(): array
    {
        return [
            'soil_type' => $this->soilType,
            'standard_info' => $this->standardInfo,
            'metadata' => $this->metadata
        ];
    }

    public function getSoilType(): string
    {
        return $this->soilType;
    }
}
