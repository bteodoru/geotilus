<?php

namespace App\Libraries\SoilClassification\Plasticity;

abstract class PlasticityClassifier
{
    protected string $systemCode;

    abstract protected function getClassificationMethod(): string;

    public function getSystemCode(): string
    {
        return $this->systemCode;
    }

    public function classify($plasticity) //: PlasticityClassificationResult
    {
        // Implement the classification logic here
        // This is a placeholder for the actual classification logic
        // return new PlasticityClassificationResult();
    }
}
