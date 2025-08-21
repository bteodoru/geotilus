<?php

namespace App\Libraries\SoilClassification\Services;

use App\Libraries\PointInPolygon;
use App\Services\GeometryService;


class CasagrandeChartService
{
    public function __construct(private GeometryService $geometryService) {}

    public function findSoilType_(float $x, float $y, array $chartDomains): ?array
    {
        foreach ($chartDomains as $domain) {

            $result = $this->geometryService->pointInPolygon([$x, $y], $domain['points']);
            $r = $this->geometryService->analyzePointPosition([$x, $y], $domain['points']);
            echo "Result:" . json_encode($r) . "\n";
            if ($result === PointInPolygon::INSIDE) {
                return [
                    'code' => $domain['code'] ?? null,
                    'name' => $domain['name'],
                    'color' => $domain['color'],
                    'plasticity' => $domain['plasticity'] ?? null,
                    'points' => $domain['points']
                ];
            }
            if ($result === PointInPolygon::ON_VERTEX || $result === PointInPolygon::ON_BOUNDARY) {
                return [
                    'code' => $domain['code'] ?? null,
                    'name' => "#N/A",
                    'color' => "none"
                ];
            }
        }

        return null;
    }

    public function findSoilType(float $x, float $y, array $chartDomains): ?array
    {
        $ambiguousDomains = [];
        $definiteDomain = null;

        foreach ($chartDomains as $domain) {
            $analysis = $this->geometryService->analyzePointPosition([$x, $y], $domain['points']);

            // echo "Analysis for domain {$domain['name']} at point ({$x}, {$y}): " . json_encode($analysis) . "\n";

            if ($analysis['is_inside']) {
                // $definiteDomain = [
                //     'classification_certainty' => 'definite',
                //     'code' => $domain['code'] ?? null,
                //     'name' => $domain['name'],
                //     // 'color' => $domain['color'],
                //     'plasticity' => $domain['plasticity'] ?? null,
                //     // 'points' => $domain['points'],
                // ];
                $definiteDomain = $this->handleDefiniteResult($domain, $x, $y);
                break;
            } elseif ($analysis['is_ambiguous']) {
                $ambiguousDomains[] = [
                    'domain' => [
                        'code' => $domain['code'] ?? null,
                        'name' => $domain['name'],
                        // 'color' => $domain['color'],
                        'plasticity' => $domain['plasticity'] ?? null,
                    ],
                    'boundary_status' => $analysis['status'],
                ];
            }
        }

        if ($definiteDomain) {
            return $definiteDomain;
        }

        if (!empty($ambiguousDomains)) {
            return $this->handleAmbiguousResult($ambiguousDomains, $x, $y);
            // return $this->handleAmbiguousPlasticityClassification($ambiguousDomains, $x, $y);
        }

        return null;
    }

    private function handleAmbiguousPlasticityClassification(array $ambiguousDomains, float $x, float $y): array
    {
        // $domainNames = array_map(fn($item) => $item['domain']['name'] . " cu plasticitate " . $item['domain']['plasticity'] . " (" . $item['domain']['code'] . ")", $ambiguousDomains);
        $domainCodes = array_map(fn($item) => $item['domain']['code'], $ambiguousDomains);

        return [
            'classification_certainty' => 'ambiguous',
            'code' => implode('/', $domainCodes),
            'name' => 'Poziție ambiguă',
            // 'possible_domains' => $domainNames,
            'ambiguous_domains' => $ambiguousDomains,
            // 'coordinates' => [$x, $y],
            // 'color' => '#FF6B6B',
            'requires_verification' => true,
            'user_message' => 'Poziție la limita dintre domeniile: ' . implode(', ', $domainCodes) . '. Verificați valorile wL și IP.'
            // 'user_message' => 'Poziție la limita dintre domeniile: ' . implode(', ', $domainNames) . '. Verificați valorile LL și IP.'
        ];
    }

    public function getChartLimit(float $liquidLimit): float
    {
        return $liquidLimit < 90 ? 90 : floor($liquidLimit / 10) * 10 + 10;
    }

    private function handleDefiniteResult(array $domain, float $x, float $y): array
    {
        return [
            'code' => $domain['code'] ?? null,
            'name' => $domain['name'],
            // 'color' => $domain['color'],
            'plasticity' => $domain['plasticity'] ?? null,
            // 'points' => $domain['points'],
            'classification_certainty' => 'definite',
            // Metadata simplă pentru rezultate definitive
            'metadata' => [
                // 'coordinates' => [$x, $y],
                // 'method' => 'casagrande_chart_classification',
                // 'liquid_limit' => $x,
                // 'plasticity_index' => $y
            ]
        ];
    }

    private function handleAmbiguousResult(array $ambiguousDomains, float $x, float $y): array
    {
        $domainNames = array_map(fn($item) => $item['domain']['name'], $ambiguousDomains);
        $domainCodes = array_map(fn($item) => $item['domain']['code'], $ambiguousDomains);

        return [
            'code' => implode('/', $domainCodes),
            'name' => 'Poziție ambiguă',
            // 'color' => '#FF6B6B',
            'points' => [],
            'classification_certainty' => 'ambiguous',
            // Metadata extinsă pentru rezultate ambigue
            'metadata' => [
                // 'coordinates' => [$x, $y],
                // 'method' => 'casagrande_chart_classification',
                // 'liquid_limit' => $x,
                // 'plasticity_index' => $y,
                // 'possible_domains' => $domainNames,
                // 'possible_codes' => $domainCodes,
                'ambiguous_domains' => array_map(fn($item) => [
                    'code' => $item['domain']['code'],
                    'name' => $item['domain']['name'],
                    'plasticity' => $item['domain']['plasticity'],
                    'boundary_status' => $item['boundary_status'],
                    // 'distance' => $item['distance']
                ], $ambiguousDomains),
                'requires_verification' => true,
                'user_message' => 'Poziție la limita dintre domeniile: ' . implode(', ', $domainCodes) . '. Verificați valorile wL și IP.'
                // 'suggested_action' => 'Repetați încercările de plasticitate sau verificați procedura de laborator'
            ]
        ];
    }
}
