<?php

namespace App\Libraries\SoilClassification\Plasticity\Classifiers;

use App\Libraries\SoilClassification\Contracts\PlasticityClassifierInterface;
use App\Libraries\SoilClassification\Plasticity\PlasticityClassificationResult;
use App\Libraries\SoilClassification\Services\CasagrandeChartService;
use App\Models\AtterbergLimit;
// use App\Models\Plasticity;
use App\Services\GeometryService;

class CasagrandeClassifier implements PlasticityClassifierInterface
{
    public function __construct(
        protected CasagrandeChartService $chartDiagramService
        // protected GeometryService $geometryService
    ) {}
    public function classify(AtterbergLimit $plasticity): PlasticityClassificationResult
    {
        $liquidLimit = $plasticity->liquid_limit;
        $plasticlimit = $plasticity->plastic_limit;
        $plasticityIndex = $liquidLimit - $plasticlimit;
        // $plasticityIndex = $plasticity->plasticity_index;
        // dd($this->getChartDomains($this->chartDiagramService->getChartLimit($liquidLimit)));
        $result = $this->chartDiagramService->findSoilType(
            $liquidLimit,
            $plasticityIndex,
            $this->getChartDomains($this->chartDiagramService->getChartLimit($liquidLimit))
        );

        if (!$result) {
            throw new \RuntimeException("Cannot determine plasticity class");
        }


        return new PlasticityClassificationResult(
            // plasticityClass: $domain['code'],
            soilType: $result['name'],
            soilCode: $result['code'],
            plasticity: $result['plasticity'] ?? 'unknown',
            classificationCertainty: $result['classification_certainty'] ?? 'definite',
            standardInfo: $this->getStandardInfo(),
            // color: $result['color'],
            metadata: $this->buildMetadata($plasticity, $result)
        );
    }



    public function getStandardInfo(): array
    {
        return [
            'code' => 'sr_en_14688_2018',
            'name' => 'SR EN ISO 14688-2',
            'version' => '2018',
            'country' => 'RO',
            'description' => 'Investigaţii şi încercări geotehnice. Identificarea şi clasificarea pământurilor. Partea 2: Principii pentru o clasificare',
        ];
    }

    public function isApplicable(AtterbergLimit $plasticity): bool
    {
        return true;
    }



    private function buildMetadata(AtterbergLimit $plasticity, array $result): array
    {
        $baseMetadata = [
            'liquid_limit' => $plasticity->liquid_limit,
            'plasticity_index' => $plasticity->liquid_limit - $plasticity->plastic_limit,
            'plastic_limit' => $plasticity->plastic_limit,
        ];

        $baseMetadata = array_merge($baseMetadata, $result['metadata']);

        return $baseMetadata;
    }

    protected function getChartDomains($lim): array
    {
        return [
            [
                'code' => 'SiL',
                'points' => [
                    [10, 0],
                    [10, 4],
                    [(4 + 0.73 * 20) / 0.73, 4],
                    [35, 0.73 * (35 - 20)],
                    [35, 0]
                ],
                'plasticity' => 'redusă',
                'name' => 'silt',
                'color' => '#FDE272',
                'labelOffset' => [0, 1],
            ],
            [
                'code' => 'ClL-SiL',
                'points' => [
                    [10, 4],
                    [10, 7],
                    [(7 + 0.73 * 20) / 0.73, 7],
                    [(4 + 0.73 * 20) / 0.73, 4]
                ],
                'plasticity' => 'redusă',
                'name' => 'clay-silt',
                'color' => '#8f6b29',
            ],
            [
                'code' => 'ClL',
                'points' => [
                    [(7 + 0.9 * 8) / 0.9, 7],
                    [35, 0.9 * (35 - 8)],
                    [35, 0.73 * (35 - 20)],
                    [(7 + 0.73 * 20) / 0.73, 7]
                ],
                'plasticity' => 'redusă',
                'name' => 'clay',
                'color' => '#D7D3D0',
            ],
            [
                'code' => 'SiM',
                'points' => [
                    [35, 0],
                    [35, 0.73 * (35 - 20)],
                    [50, 0.73 * (50 - 20)],
                    [50, 0]
                ],
                'plasticity' => 'medie',
                'name' => 'silt',
                'color' => '#FAC515',
            ],
            [
                'code' => 'ClM',
                'points' => [
                    [35, 0.73 * (35 - 20)],
                    [35, 0.9 * (35 - 8)],
                    [50, 0.9 * (50 - 8)],
                    [50, 0.73 * (50 - 20)]
                ],
                'plasticity' => 'medie',
                'name' => 'clay',
                'color' => '#A9A29D',
            ],
            [
                'code' => 'SiH',
                'points' => [
                    [50, 0],
                    [50, 0.73 * (50 - 20)],
                    [70, 0.73 * (70 - 20)],
                    [70, 0]
                ],
                'plasticity' => 'mare',
                'name' => 'silt',
                'color' => '#EAAA08',
            ],
            [
                'code' => 'ClH',
                'points' => [
                    [50, 0.73 * (50 - 20)],
                    [50, 0.9 * (50 - 8)],
                    [70, 0.9 * (70 - 8)],
                    [70, 0.73 * (70 - 20)]
                ],
                'plasticity' => 'mare',
                'name' => 'clay',
                'color' => '#79716B',
            ],
            [
                'code' => 'SiV',
                'points' => [
                    [70, 0],
                    [70, 0.73 * (70 - 20)],
                    [$lim, 0.73 * ($lim - 20)],
                    [$lim, 0]
                ],
                'plasticity' => 'foarte mare',
                'name' => 'silt',
                'color' => '#CA8504',
            ],
            [
                'code' => 'ClV',
                'points' => [
                    [70, 0.73 * (70 - 20)],
                    [70, 0.9 * (70 - 8)],
                    [$lim, 0.9 * ($lim - 8)],
                    [$lim, 0.73 * ($lim - 20)]
                ],
                'plasticity' => 'foarte mare',
                'name' => 'clay',
                'color' => '#57534E',
            ],
        ];
    }
}
