<?php

namespace App\Libraries\SoilClassification\Granulometry;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;

/**
 * Repository pentru gestionarea diagramelor ternare
 * Oferă acces cached la datele statice ale diagramelor
 * Permite modificarea configurațiilor fără redeployment
 */
class TernaryDiagramRepository
{
    private const CACHE_PREFIX = 'ternary_diagram_';

    /**
     * Încarcă diagrama ternară pentru un sistem specific
     * Folosește cache pentru performanță optimă
     */
    public function getDiagramForSystem(string $systemCode): array
    {
        $cacheKey = self::CACHE_PREFIX . $systemCode;

        return Cache::rememberForever($cacheKey, function () use ($systemCode) {
            return $this->loadDiagramFromConfig($systemCode);
        });
    }

    /**
     * Încarcă diagrama din configurație cu validare
     * Această metodă poate fi extinsă pentru validarea structurii diagramei
     */
    private function loadDiagramFromConfig(string $systemCode): array
    {
        $configPath = "soil_classification.ternary_diagrams.{$systemCode}";
        $diagram = Config::get($configPath);

        if (!$diagram) {
            throw new \InvalidArgumentException(
                "Diagrama ternară pentru sistemul '{$systemCode}' nu este configurată. " .
                    "Verificați existența fișierului config/soil_classification/ternary_diagrams/{$systemCode}.php"
            );
        }

        $this->validateDiagramStructure($diagram, $systemCode);

        return $diagram;
    }

    /**
     * Validează că diagrama are structura necesară
     * Previne erorile runtime prin validarea timpurie
     */
    private function validateDiagramStructure(array $diagram, string $systemCode): void
    {
        if (!is_array($diagram) || empty($diagram)) {
            throw new \InvalidArgumentException(
                "Diagrama pentru sistemul '{$systemCode}' este goală sau invalidă"
            );
        }

        foreach ($diagram['domains'] as $index => $domain) {
            if (!isset($domain['points'])) {
                throw new \InvalidArgumentException(
                    "Domeniul #{$index} din diagrama '{$systemCode}' lipsește 'points' sau 'name'"
                );
            }
        }
    }

    /**
     * Golește cache-ul pentru un sistem specific
     * Util după actualizarea configurațiilor
     */
    public function clearCacheForSystem(string $systemCode): void
    {
        $cacheKey = self::CACHE_PREFIX . $systemCode;
        Cache::forget($cacheKey);
    }

    /**
     * Golește tot cache-ul pentru diagrame
     * Util pentru maintenance sau după actualizări majore
     */
    public function clearAllDiagramCache(): void
    {
        $systems = ['stas_1243_1988', 'np_074_2022', 'sr_en_iso_14688_2005'];

        foreach ($systems as $system) {
            $this->clearCacheForSystem($system);
        }
    }
}
