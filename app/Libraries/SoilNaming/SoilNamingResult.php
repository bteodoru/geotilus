<?php

namespace App\Libraries\SoilNaming;

class SoilNamingResult
{
    public function __construct(
        private string $finalName,
        private  $primaryFraction,
        private array $furtherFractions,
        // private array $tertiaryFractions,
        private array $metadata
    ) {}

    public function getFinalName(): string
    {
        return $this->finalName;
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
            'final_name' => $this->finalName,
            'primary_fraction' => $this->primaryFraction,
            'further_fractions' => $this->furtherFractions,
            'metadata' => $this->metadata
        ];
    }
}
