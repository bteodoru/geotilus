<?php

namespace App\Libraries\SoilClassification\Granulometry;

use App\Models\Granulometry;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use ReflectionClass;

/**
 * Factory pentru crearea clasificatorilor granulometrici cu auto-discovery
 */
class GranulometryClassificationFactory
{
    private const CACHE_KEY = 'granulometry_classifiers_registry';
    private const CLASSIFIERS_PATH = __DIR__ . '/Classifiers';

    private array $classifiers = [];
    private array $classifierMetadata = [];

    public function __construct()
    {
        $this->registerClassifiers();
    }

    /**
     * Creează un clasificator pentru un standard specific
     */
    public function create(string $standardCode): GranulometryClassifier
    {
        if (!isset($this->classifiers[$standardCode])) {
            throw new \InvalidArgumentException("Unknown granulometry standard: {$standardCode}");
        }

        $classifierFactory = $this->classifiers[$standardCode];
        return $classifierFactory();
    }

    /**
     * Găsește standardele aplicabile pentru datele respective
     */
    public function getApplicableSystems(Granulometry $granulometry): array
    {
        $applicable = [];

        foreach ($this->classifiers as $code => $classifierFactory) {
            $classifier = $classifierFactory();
            $applicable[$code] = $classifier->getSystemInfo();
        }

        return $applicable;
    }

    /**
     * Returnează toate system code-urile disponibile
     */
    public function getAvailableSystemCodes(): array
    {
        return array_keys($this->classifiers);
    }

    /**
     * Înregistrează clasificatorii folosind auto-discovery cu cache
     */
    private function registerClassifiers(): void
    {
        // Cache-ează doar metadata-ul (nume clase), nu closure-urile
        $this->classifierMetadata = Cache::rememberForever(self::CACHE_KEY, function () {
            return $this->discoverClassifiersMetadata();
        });

        // Recreează closure-urile din metadata
        $this->classifiers = $this->createClassifiersFromMetadata($this->classifierMetadata);
    }

    /**
     * Descoperă automat clasificatorii și returnează doar metadata
     */
    private function discoverClassifiersMetadata(): array
    {
        $classifiersMetadata = [];
        $classifierFiles = File::glob(self::CLASSIFIERS_PATH . '/*.php');

        foreach ($classifierFiles as $file) {
            $className = $this->getClassNameFromFile($file);

            if (!$className || !$this->isValidClassifier($className)) {
                continue;
            }

            $systemCode = $this->extractSystemCodeFromClassName($className);

            if (!$systemCode) {
                continue;
            }

            // Salvează doar numele clasei, nu closure-ul
            $classifiersMetadata[$systemCode] = $className;
        }

        return $classifiersMetadata;
    }

    /**
     * Creează closure-urile din metadata
     */
    private function createClassifiersFromMetadata(array $metadata): array
    {
        $classifiers = [];

        foreach ($metadata as $systemCode => $className) {
            $classifiers[$systemCode] = $this->createClassifierFactory($className);
        }

        return $classifiers;
    }

    /**
     * Extrage numele clasei din fișier
     */
    private function getClassNameFromFile(string $filePath): ?string
    {
        $fileName = basename($filePath, '.php');
        $namespace = 'App\\Libraries\\SoilClassification\\Granulometry\\Classifiers\\';

        return $namespace . $fileName;
    }

    /**
     * Verifică dacă clasa este un clasificator valid
     */
    private function isValidClassifier(string $className): bool
    {
        if (!class_exists($className)) {
            return false;
        }

        $reflection = new ReflectionClass($className);

        // Verifică că nu e abstractă și extinde GranulometryClassifier
        return !$reflection->isAbstract() &&
            $reflection->isSubclassOf(GranulometryClassifier::class);
    }

    /**
     * Extrage system code din numele clasei
     * Ex: SR_EN_ISO_14688_2018GranulometryClassifier -> sr_en_iso_14688_2018
     */
    private function extractSystemCodeFromClassName(string $className): ?string
    {
        // Extrage doar numele clasei fără namespace
        $shortName = class_basename($className);

        // Elimină sufixul 'GranulometryClassifier'
        if (!str_ends_with($shortName, 'GranulometryClassifier')) {
            return null;
        }

        $systemPart = substr($shortName, 0, -strlen('GranulometryClassifier'));

        // Convertește din PascalCase în snake_case
        return $this->convertToSnakeCase($systemPart);
    }

    /**
     * Convertește din PascalCase în snake_case
     */
    private function convertToSnakeCase(string $input): string
    {
        // Inserează underscore înaintea cifrelor precedate de litere
        $result = preg_replace('/([a-zA-Z])(\d)/', '$1_$2', $input);

        // Inserează underscore înaintea literelor mari precedate de litere mici
        $result = preg_replace('/([a-z])([A-Z])/', '$1_$2', $result);

        return strtolower($result);
    }

    /**
     * Creează factory pentru un clasificator specific
     */
    private function createClassifierFactory(string $className): \Closure
    {
        return function () use ($className) {
            $dependencies = $this->resolveDependencies($className);
            return new $className(...$dependencies);
        };
    }

    /**
     * Rezolvă dependențele pentru un clasificator
     */
    private function resolveDependencies(string $className): array
    {
        $dependencyClasses = $className::getDependencies();
        $resolvedDependencies = [];

        foreach ($dependencyClasses as $dependencyClass) {
            $resolvedDependencies[] = app($dependencyClass);
        }

        return $resolvedDependencies;
    }

    /**
     * Invalidează cache-ul clasificatorilor
     * Apelat prin artisan cache:clear sau manual
     */
    public static function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
