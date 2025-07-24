<?php

namespace App\Libraries\SoilClassification\Repositories;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;


class ClassificationSystemRepository
{

    public function all(): array
    {
        return Cache::rememberForever('classification_systems', function () {
            return Config::get('soil_classification.systems', []);
        });
        return Config::get('soil_classification.systems', []);
    }

    public function findByCode(string $systemCode): array
    {
        return Cache::rememberForever("classification_system_info.{$systemCode}", function () use ($systemCode) {
            $configPath = "soil_classification.systems.{$systemCode}";

            if (!Config::has($configPath)) {
                throw new \InvalidArgumentException("System configuration not found: {$systemCode}");
            }

            return Config::get($configPath);
        });
    }

    public function getSupportedClassificationCriteria(string $systemCode): array
    {
        $systemConfig = $this->findByCode($systemCode);
        return $systemConfig['supported_classification_criteria'] ?? [];
    }


    public function getCriterionConfiguration(string $systemCode, string $criterion): array
    {
        $supportedCriteria = $this->getSupportedClassificationCriteria($systemCode);
        return $supportedCriteria[$criterion] ?? [];
    }

    public function allByCriterion(string $criterionName): array
    {
        $availableImplementations = [];

        // $systems = Config::get('soil_classification.systems', []);
        $systems = $this->all();

        foreach ($systems as $systemCode => $systemConfig) {
            $supportedCriteria = $systemConfig['supported_classification_criteria'] ?? [];

            if (isset($supportedCriteria[$criterionName])) {
                $availableImplementations[$systemCode] = [
                    'code' => $systemCode,
                    'name' => $systemConfig['system_info']['name'],
                    'version' => $systemConfig['system_info']['version'],
                    'criterion' => $criterionName,
                ];
            }
        }

        return $availableImplementations;
    }


    public function supportsCriterion(string $systemCode, string $criterion): bool
    {
        $supportedCriteria = $this->getSupportedClassificationCriteria($systemCode);
        return isset($supportedCriteria[$criterion]);
    }


    public function getAllAvailableSystems(): array
    {
        // Scanează configurațiile disponibile
        return ['stas_1243_1988', 'np_074_2022', 'sr_en_iso_14688_2005'];
    }


    public function systemExists(string $systemCode): bool
    {
        return Config::has("classification_systems.{$systemCode}");
    }


    public function clearCache(string $systemCode): void
    {
        Cache::forget("classification_system_info.{$systemCode}");
    }
}
