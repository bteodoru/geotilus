<?php

namespace App\Libraries\SoilClassification;

use App\Libraries\SoilClassification\Granulometry\GranulometryRule;

class SoilClassificationFactory
{
    // public function getAvailableRules(): array
    // {
    //     return [
    //         'granulometry' => 'Identificarea granulometrică',
    //         'plasticity' => 'Clasificarea plasticității',
    //         'consistency' => 'Determinarea consistenței'
    //     ];
    // }

    // public static function granulometry($sample, string $systemCode): GranulometryRule
    // {
    //     return new GranulometryRule($sample, $systemCode);
    // }

    // public function getStandardsForRule(string $rule): array
    // {
    //     $rulePath = __DIR__ . "/Rules/{$rule}/Implementations";
    //     $implementations = glob($rulePath . "/*.php");

    //     $standards = [];
    //     foreach ($implementations as $file) {
    //         $className = basename($file, '.php');
    //         $fullClassName = "App\\Libraries\\SoilClassification\\Rules\\{$rule}\\Implementations\\{$className}";

    //         if (class_exists($fullClassName)) {
    //             $instance = new $fullClassName(new \stdClass()); // Mock pentru metadata
    //             $standards[$className] = $instance->getStandardInfo();
    //         }
    //     }

    //     return $standards;
    // }

    // public function makeClassifier(string $rule, string $standard, $sample)
    // {
    //     $className = "App\\Libraries\\SoilClassification\\Rules\\{$rule}\\Implementations\\{$standard}";

    //     if (!class_exists($className)) {
    //         throw new \RuntimeException("Implementation {$className} not found");
    //     }

    //     return new $className($sample);
    // }

    // public static function getAvailableSystemsForRule(string $rule): array
    // {
    //     $configPath = __DIR__ . "/{$rule}/Systems";

    //     if (!is_dir($configPath)) {
    //         return [];
    //     }

    //     $systemFiles = glob($configPath . "/*.php");
    //     $systems = [];

    //     foreach ($systemFiles as $file) {
    //         $systemCode = basename($file, '.php');
    //         $config = require $file;
    //         $systems[$systemCode] = $config['meta'];
    //     }

    //     return $systems;
    // }

    /** Creează un classifier pentru granulometrie
     */
    public static function granulometry($sample, string $systemCode): GranulometryRule
    {
        return new GranulometryRule($sample, $systemCode);
    }

    /**
     * Creează un classifier pentru plasticitate
     */
    public static function plasticity($sample, string $systemCode): PlasticityRule
    {
        return new PlasticityRule($sample, $systemCode);
    }

    /**
     * Returnează sistemele disponibile pentru o anumită regulă
     */
    public static function getAvailableSystemsForRule(string $rule): array
    {
        $implementationsPath = __DIR__ . "/{$rule}/Implementations";

        if (!is_dir($implementationsPath)) {
            return [];
        }

        $classFiles = glob($implementationsPath . "/*.php");
        $systems = [];

        foreach ($classFiles as $file) {
            $className = basename($file, '.php');
            $fullClassName = "App\\Libraries\\SoilClassification\\{$rule}\\Implementations\\{$className}";

            if (class_exists($fullClassName)) {
                try {
                    // Apelăm metoda static pentru a evita instanțierea
                    $standardInfo = $fullClassName::getStandardInfo();
                    $systems[$standardInfo['code']] = $standardInfo;
                } catch (\Exception $e) {
                    // Skip invalid implementations
                    continue;
                }
            }
        }

        return $systems;
    }

    /**
     * Returnează toate regulile disponibile
     */
    public static function getAvailableRules(): array
    {
        return [
            'granulometry' => 'Identificarea granulometrică',
            'plasticity' => 'Clasificarea plasticității',
            'consistency' => 'Determinarea consistenței'
        ];
    }
}
