<?php

namespace App\Libraries;

use Exception;

class SoilTypeIdentifier
{



    public function identify(float $clay, float $silt, float $sand): string
    {
        $point = $this->toCartesianCoordinates(array($clay, $sand, $silt));
        $pointLocation = new PointInPolygon(true);

        foreach (config('geotilus.soilsByNP074') as $key => $value) {
            $soil_type = "Utilizați un criteriu suplimentar pentru clasificarea pământului";

            $cartesianCoordinates = array_map(function ($ternaryPoint) {
                return $this->toCartesianCoordinates($ternaryPoint);
            }, $value['points']);

            if ($pointLocation->pointInPolygon($point, $cartesianCoordinates) === "inside") {
                $soil_type = $key;
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
