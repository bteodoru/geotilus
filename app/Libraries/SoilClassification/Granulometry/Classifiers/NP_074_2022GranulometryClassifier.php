<?php

namespace App\Libraries\SoilClassification\Granulometry\Classifiers;

use App\Libraries\SoilClassification\Granulometry\GranulometryClassifier;
use App\Libraries\SoilClassification\Granulometry\Services\GranulometryClassificationServiceContainer;
use App\Models\Granulometry;

class NP_074_2022GranulometryClassifier extends GranulometryClassifier
{

    // protected string $systemCode = 'np_074_2022';
    // protected $thresholds = [40, 20];
    public function __construct(
        protected GranulometryClassificationServiceContainer $serviceContainer,
        // private SomeSpecialService $specialService // Dependență suplimentară
    ) {
        parent::__construct($serviceContainer);
    }

    public static function getDependencies(): array
    {
        return [
            GranulometryClassificationServiceContainer::class,
            // SomeSpecialService::class, // Adaugă serviciul suplimentar
        ];
    }
    protected function getClassificationMethod(): string
    {
        return 'stas_single_ternary_diagram';
    }



    public function getGradation(Granulometry $granulometry): ?string
    {

        return null;
    }
}
