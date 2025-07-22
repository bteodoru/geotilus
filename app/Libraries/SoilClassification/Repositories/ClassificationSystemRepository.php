<?php

namespace App\Libraries\SoilClassification\Repositories;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;


class ClassificationSystemRepository
{

    public function getClassificationSystem(string $systemCode): array
    {
        return Cache::rememberForever("classification_system_info.{$systemCode}", function () use ($systemCode) {
            $configPath = "soil_classification.systems.{$systemCode}";

            if (!Config::has($configPath)) {
                throw new \InvalidArgumentException("System configuration not found: {$systemCode}");
            }

            return Config::get($configPath);
        });
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
