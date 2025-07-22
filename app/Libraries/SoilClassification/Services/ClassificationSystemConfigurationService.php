<?php

namespace App\Libraries\SoilClassification\Services;

use Illuminate\Support\Facades\Config;

/**
 * Service pentru gestionarea configurațiilor sistemelor de clasificare
 * Înțelege distincția dintre sisteme, criterii și metode de clasificare
 */
class ClassificationSystemConfigurationService
{
    /**
     * Încarcă informațiile generale despre un sistem de clasificare
     * Un sistem poate fi standard, normativ, cod local, etc.
     */
    public function getClassificationSystemInfo(string $systemCode): array
    {
        $config = Config::get("soil_classification.systems.{$systemCode}");

        if (!$config) {
            throw new \InvalidArgumentException(
                "Sistemul de clasificare '{$systemCode}' nu este configurat. " .
                    "Verificați că fișierul de configurare există și este corect structurat."
            );
        }

        return $config['system_info'];
    }

    /**
     * Verifică dacă un sistem suportă un anumit criteriu cu o metodă specifică
     * Aceasta înlocuiește vechea verificare confuză de "metode"
     */
    public function systemSupportsCriterionMethod(
        string $systemCode,
        string $criterionName,
        string $methodName
    ): bool {
        $systemConfig = Config::get("soil_classification.systems.{$systemCode}");

        if (!$systemConfig) {
            return false;
        }

        $supportedCriteria = $systemConfig['supported_classification_criteria'] ?? [];
        $criterion = $supportedCriteria[$criterionName] ?? null;

        if (!$criterion) {
            return false;
        }

        return in_array($methodName, $criterion['available_methods'] ?? []);
    }

    /**
     * Încarcă configurația pentru aplicarea unui criteriu specific 
     * printr-o metodă specifică în contextul unui sistem particular
     */
    public function getCriterionMethodConfiguration(
        string $systemCode,
        string $criterionName,
        string $methodName
    ): array {
        // Verificăm că combinația este validă
        if (!$this->systemSupportsCriterionMethod($systemCode, $criterionName, $methodName)) {
            throw new \InvalidArgumentException(
                "Sistemul '{$systemCode}' nu suportă criteriul '{$criterionName}' " .
                    "cu metoda '{$methodName}'. Verificați combinația și configurațiile disponibile."
            );
        }

        // Construim calea către configurația specifică
        $configPath = "soil_classification.criteria.{$criterionName}.methods.{$methodName}.{$systemCode}_{$methodName}";
        $methodConfig = Config::get($configPath);

        if (!$methodConfig) {
            throw new \InvalidArgumentException(
                "Configurația pentru criteriul '{$criterionName}' cu metoda '{$methodName}' " .
                    "din sistemul '{$systemCode}' nu a fost găsită la calea '{$configPath}'."
            );
        }

        // Îmbogățim configurația cu informațiile despre sistem
        $methodConfig['classification_system_info'] = $this->getClassificationSystemInfo($systemCode);

        return $methodConfig;
    }

    /**
     * Returnează toate implementările disponibile pentru un criteriu specific
     * Util pentru construirea interfețelor de selecție
     */
    public function getAvailableCriterionImplementations(string $criterionName): array
    {
        $availableImplementations = [];

        // Citim toate sistemele configurate
        $systems = Config::get('soil_classification.systems', []);

        foreach ($systems as $systemCode => $systemConfig) {
            $supportedCriteria = $systemConfig['supported_classification_criteria'] ?? [];

            if (isset($supportedCriteria[$criterionName])) {
                $criterionConfig = $supportedCriteria[$criterionName];

                foreach ($criterionConfig['available_methods'] as $methodName) {
                    $availableImplementations[$systemCode] = [
                        'code' => $systemCode,
                        'name' => $systemConfig['system_info']['name'],
                        'country' => $systemConfig['system_info']['country'],
                        'version' => $systemConfig['system_info']['version'],
                        'criterion_name' => $criterionName,
                        'method_name' => $methodName,
                        'is_primary_method' => ($methodName === $criterionConfig['primary_method']),
                        'is_mandatory_criterion' => $criterionConfig['mandatory'],
                        'full_identifier' => "{$systemCode}_{$criterionName}_{$methodName}"
                        // 'system_code' => $systemCode,
                        // 'system_name' => $systemConfig['system_info']['name'],
                        // 'criterion_name' => $criterionName,
                        // 'method_name' => $methodName,
                        // 'is_primary_method' => ($methodName === $criterionConfig['primary_method']),
                        // 'is_mandatory_criterion' => $criterionConfig['mandatory'],
                        // 'full_identifier' => "{$systemCode}_{$criterionName}_{$methodName}"
                    ];
                }
            }
        }

        return $availableImplementations;
    }


    /**
     * Obține ierarhia de aplicare a criteriilor pentru un sistem specific
     * Útil pentru implementarea logicii de clasificare multi-criteriu
     */
    public function getClassificationHierarchy(string $systemCode): array
    {
        $systemConfig = Config::get("soil_classification.systems.{$systemCode}");

        if (!$systemConfig) {
            throw new \InvalidArgumentException("Sistemul '{$systemCode}' nu este configurat.");
        }

        return $systemConfig['classification_rules'] ?? [];
    }
}
