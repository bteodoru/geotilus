<?php

namespace App\Libraries\SoilNaming;

class SoilNamingResult
{
    public function __construct(
        private string $soilName,
        private  $primaryFraction,
        private array $furtherFractions,
        // private array $tertiaryFractions,
        private array $metadata
    ) {}

    public function getSoilName(): string
    {
        return $this->soilName;
    }

    public function getPrimaryFraction(): string
    {
        return $this->primaryFraction;
    }
    public function getFurtherFractions(): array
    {
        return $this->furtherFractions;
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }

    public function toArray(): array
    {
        return [
            'final_name' => $this->soilName,
            'primary_fraction' => $this->primaryFraction,
            'further_fractions' => $this->furtherFractions,
            'metadata' => $this->metadata
        ];
    }
}
