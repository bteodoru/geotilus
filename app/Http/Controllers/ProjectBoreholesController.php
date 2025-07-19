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

class ProjectBoreholesController extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    public function show($projectId)
    {
        // $project = Project::with(['boreholes.samples' => function ($query) {
        //     $query->select('id', 'borehole_id', 'name'); // doar coloanele necesare
        // }])->findOrFail($projectId);

        $project = Project::with([
            'boreholes' => function ($query) {
                $query->select('id', 'project_id', 'name', 'depth');  // Selectează coloanele din boreholes
            },
            // 'boreholes.samples' => function ($query) {
            //     $query->select('id', 'borehole_id', 'name');  // Selectează coloanele din samples
            // }
        ])->findOrFail($projectId);


        // $project->load('boreholes.samples');
        return Inertia::render('ProjectBoreholes', [
            'project' => $project,
        ]);
    }
}
