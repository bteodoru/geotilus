<?php

namespace App\Http\Controllers;

use App\Libraries\PointInPolygon;
use App\Libraries\TernaryPlot;
use App\Models\Sample;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Inertia\Inertia;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    public function granulometry()
    {
        $sample = Sample::with('granulometry')->find(12);
        $point = array($sample->granulometry->silt + 0.5 * $sample->granulometry->clay, sqrt(3) * 0.5 * $sample->granulometry->clay);
        $ternaryPlot = new TernaryPlot();
        $point_ = $ternaryPlot->toCartesianCoordinates(array($sample->granulometry->clay, $sample->granulometry->sand, $sample->granulometry->silt));
        // dd($point, $point_);
        $pointLocation = new PointInPolygon(true);
        foreach (config('geotilus.soilsByNP074') as $key => $value) {
            // foreach (config('geotilus.soilsBySTAS1243') as $key => $value) {
            $soil_type = "Utilizați un criteriu suplimentar pentru clasificarea pământului";
            if ($pointLocation->pointInPolygon($point_, array_map(array($ternaryPlot, 'toCartesianCoordinates'), $value['points'])) === "inside") {
                // if ($pointLocation->pointInPolygon($point, $value['points']) === "inside") {
                $soil_type = $key;
                break;
            }
        }
        $sample->soil_type = $soil_type;
        return Inertia::render('Granulometry', [
            'sample' => $sample,
        ]);
    }
}
