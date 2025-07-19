<?php

namespace App\Libraries;

use Exception;

class SoilPhaseCalculator
{
    protected ?float $moisture_content = null;
    protected ?float $bulk_density = null;
    protected ?float $particle_density = null;
    protected ?float $dry_density = null;
    protected ?float $porosity = null;
    protected ?float $voids_ratio = null;
    protected ?float $moisture_content_at_saturation = null;
    protected ?float $saturated_density = null;
    protected ?float $submerged_density = null;
    protected ?float $degree_of_saturation = null;


    public function compute(float $moisture_content, float $bulk_density, ?float $particle_density = null): void
    {
        $this->moisture_content = $moisture_content;
        $this->bulk_density = $bulk_density;
        $this->particle_density = $particle_density;

        $this->dry_density = $this->computeDryDensity();

        if ($this->particle_density !== null) {
            $this->porosity = $this->computePorosity();
            $this->voids_ratio = $this->computeVoidsRatio();
            $moisture_content_at_saturation = $this->computeMoistureContentAtSaturation();

            if ($moisture_content_at_saturation < $this->moisture_content) {
                throw new Exception('Moisture content at full saturation is less than the actual moisture content.');
            }

            $this->moisture_content_at_saturation = $moisture_content_at_saturation;
            $this->saturated_density = $this->computeSaturatedDensity();
            $this->submerged_density = $this->computeSubmergedDensity();
            $this->degree_of_saturation = $this->computeDegreeOfSaturation();
        }
    }

    public function getDryDensity(): ?float
    {
        return $this->dry_density;
    }

    public function getPorosity(): ?float
    {
        return $this->porosity;
    }

    public function getVoidsRatio(): ?float
    {
        return $this->voids_ratio;
    }

    public function getMoistureContentAtSaturation(): ?float
    {
        return $this->moisture_content_at_saturation;
    }

    public function getSaturatedDensity(): ?float
    {
        return $this->saturated_density;
    }

    public function getSubmergedDensity(): ?float
    {
        return $this->submerged_density;
    }

    public function getDegreeOfSaturation(): ?float
    {
        return $this->degree_of_saturation;
    }

    protected function computeDryDensity(): float
    {
        return $this->bulk_density / (1 + $this->moisture_content / 100);
    }

    protected function computePorosity(): float
    {
        return (($this->particle_density - $this->dry_density) / $this->particle_density) * 100;
    }

    protected function computeVoidsRatio(): float
    {
        return $this->porosity / (100 - $this->porosity);
    }

    protected function computeMoistureContentAtSaturation(): float
    {
        return $this->voids_ratio / $this->particle_density * 100;
    }

    protected function computeSaturatedDensity(): float
    {
        return $this->dry_density * (1 +  $this->moisture_content_at_saturation / 100);
    }

    protected function computeSubmergedDensity(): float
    {
        return $this->saturated_density - 1;
    }

    protected function computeDegreeOfSaturation(): float
    {
        return $this->moisture_content / $this->moisture_content_at_saturation * 100;
    }
}
