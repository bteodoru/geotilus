<?php

namespace App\Libraries\SoilClassification\Contracts;

use App\Libraries\SoilClassification\Plasticity\PlasticityClassificationResult;
use App\Models\AtterbergLimit;
// use App\Models\Plasticity;


interface PlasticityClassifierInterface
{
    public function classify(AtterbergLimit $plasticity): PlasticityClassificationResult;
    public function getStandardInfo(): array;
    public function isApplicable(AtterbergLimit $plasticity): bool;
    // public function getRequiredFields(): array;
    // public function getPlasticityClasses(): array;
}
