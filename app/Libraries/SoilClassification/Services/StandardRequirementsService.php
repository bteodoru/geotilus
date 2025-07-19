<?php

namespace App\Libraries\SoilClassification\Services;

use App\Models\Granulometry;

class StandardRequirementsService
{
    public function getBasicRequirements(): array
    {
        return ['clay', 'sand', 'silt'];
    }

    public function getExtendedRequirements(): array
    {
        return [...$this->getBasicRequirements(), 'gravel'];
    }

    public function getFullRequirements(): array
    {
        return [...$this->getExtendedRequirements(), 'cobble', 'boulder'];
    }

    public function checkBasicApplicability(Granulometry $granulometry): bool
    {
        return !is_null($granulometry->clay) &&
            !is_null($granulometry->sand) &&
            !is_null($granulometry->silt);
    }
}
