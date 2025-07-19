<?php

namespace App\Libraries\SoilClassification\Granulometry;

class GranulometryClassificationResult
{

    private string $soilType;
    // private float $cartesianX;
    // private float $cartesianY;
    // private float $confidence;
    private array $standardInfo;
    // private string $color;
    private array $metadata;

    public function __construct(
        string $soilType,
        // float $cartesianX,
        // float $cartesianY,
        // float $confidence,
        array $standardInfo,
        // string $color,
        array $metadata = []
    ) {
        $this->soilType = $soilType;
        // $this->cartesianX = $cartesianX;
        // $this->cartesianY = $cartesianY;
        // $this->confidence = $confidence;
        $this->standardInfo = $standardInfo;
        // $this->color = $color;
        $this->metadata = $metadata;
    }

    public function toArray(): array
    {
        return [
            'soil_type' => $this->soilType,
            // 'cartesian_x' => $this->cartesianX,
            // 'cartesian_y' => $this->cartesianY,
            // 'confidence' => $this->confidence,
            'standard_info' => $this->standardInfo,
            // 'color' => $this->color,
            'metadata' => $this->metadata
        ];
    }

    public function getSoilType(): string
    {
        return $this->soilType;
    }

    // public function getConfidence(): float
    // {
    //     return $this->confidence;
    // }

    // public function getCartesianX(): float
    // {
    //     return $this->cartesianX;
    // }

    // public function getCartesianY(): float
    // {
    //     return $this->cartesianY;
    // }
}
