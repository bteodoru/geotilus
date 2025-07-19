<?php

namespace App\Libraries\SoilNaming;

use App\Libraries\SoilNaming\SoilNamingResult;
use App\Models\Sample;

interface SoilNamingInterface
{
    /**
     * Determină numele final al pământului
     */
    public function nameSoil(Sample $sample): SoilNamingResult;

    /**
     * Informații despre sistemul de denumire
     */
    public function getStandardInfo(): array;

    /**
     * Verifică dacă poate denumi acest sample
     */
    public function isApplicable(Sample $sample): bool;

    /**
     * Returnează cerințele pentru acest sistem
     */
    public function getRequiredFields(): array;

    /**
     * Returnează metodele de clasificare folosite
     */
    public function getClassificationMethods(): array;
}
