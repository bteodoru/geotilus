<?php

namespace App\Http\Controllers;

use App\Libraries\PointInPolygon;
use App\Libraries\TernaryPlot;
use App\Models\Project;
use App\Models\Sample;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class ProjectSamplesController extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    public function show($projectId)
    {


        $samples = Sample::select('samples.*')
            ->join('boreholes', 'samples.borehole_id', '=', 'boreholes.id')
            ->where('boreholes.project_id', $projectId)
            ->get();

        // $samples = Sample::whereHas('borehole', function ($query) use ($projectId) {
        //     $query->where('project_id', $projectId);
        // })->get();

        return Inertia::render('ProjectSamples', [
            'samples' => $samples,
        ]);
    }
}
