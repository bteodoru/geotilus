<?php

namespace App\Libraries;

use Exception;

class TernaryPlot
{

    public function toCartesianCoordinates($ternaryCoordinates)
    {
        $sum = array_sum($ternaryCoordinates);
        if ($sum !== 100) {
            throw new Exception("The sum of the ternary coordinates is not 100.");
        }
        $x = 0.5 * (2 * $ternaryCoordinates[2] + $ternaryCoordinates[0]);
        $y = sqrt(3) * $ternaryCoordinates[0] * 0.5;
        return [$x, $y];
    }
}
