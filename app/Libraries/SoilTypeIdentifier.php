<?php

namespace App\Libraries;

use Exception;

class SoilTypeIdentifier
{

    private array $availableSystems;

    public function __construct()
    {
        $this->loadAvailableSystems();
    }

    private function loadAvailableSystems(): void
    {
        $configPath = config_path('soil_identification');
        $systemFiles = glob($configPath . '/*.php');

        foreach ($systemFiles as $file) {
            $systemCode = basename($file, '.php');
            $this->availableSystems[$systemCode] = require $file;
        }
    }

    public function getAvailableSystems(): array
    {
        return array_map(function ($system) {
            return $system['meta'];
        }, $this->availableSystems);
    }

    public function getSystemByCode(string $code): ?array
    {
        return $this->availableSystems[$code] ?? null;
    }


    public function identify(float $clay, float $silt, float $sand, string $code): string
    {
        $system = $this->getSystemByCode($code);
        if (!$system) {
            throw new Exception("Soil identification system with code {$code} not found.");
        }
        $point = $this->toCartesianCoordinates(array($clay, $sand, $silt));
        $pointLocation = new PointInPolygon(true);

        foreach ($system['domains'] as $soilType => $definition) {

            $soil_type = "Utilizați un criteriu suplimentar pentru clasificarea pământului";

            $cartesianCoordinates = array_map(function ($ternaryPoint) {
                return $this->toCartesianCoordinates($ternaryPoint);
            }, $definition['points']);

            if ($pointLocation->pointInPolygon($point, $cartesianCoordinates) === "inside") {
                $soil_type = $soilType;
                break;
            }
        }
        // dd($soil_type);
        return $soil_type;
    }


    protected function toCartesianCoordinates($ternaryCoordinates)
    {
        $sum = array_sum($ternaryCoordinates);
        if (abs($sum - 100) > 0.001) {
            throw new Exception("The sum of the ternary coordinates is not 100.");
        }
        $x = 0.5 * (2 * $ternaryCoordinates[2] + $ternaryCoordinates[0]);
        $y = sqrt(3) * $ternaryCoordinates[0] * 0.5;
        return [$x, $y];
    }
}


class SoilClassifier
{
    private array $availableSystems;

    public function __construct()
    {
        $this->loadAvailableSystems();
    }

    private function loadAvailableSystems(): void
    {
        $configPath = config_path('soil_classification');
        $systemFiles = glob($configPath . '/*.php');

        foreach ($systemFiles as $file) {
            $systemCode = basename($file, '.php');
            $this->availableSystems[$systemCode] = require $file;
        }
    }

    public function getAvailableSystems(): array
    {
        return array_map(function ($system) {
            return $system['meta'];
        }, $this->availableSystems);
    }

    public function getSystemByCode(string $code): ?array
    {
        return $this->availableSystems[$code] ?? null;
    }
}
