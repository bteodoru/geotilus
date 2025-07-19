<?php

namespace App\Http\Controllers;

use App\Libraries\PointInPolygon;
use App\Libraries\TernaryPlot;
use App\Models\Project;
use App\Models\Sample;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Client\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class ProjectController extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    public function index()
    {

        //    $projects = Project::all();

        $projects = auth()->user()->projects()
            ->with(['client' => function ($query) {
                $query->select('id', 'name'); // Select only necessary columns
            }])
            ->get();
        return Inertia::render('Projects', [
            'projects' => $projects,
            'currentProjectId' => auth()->user()->current_project_id,
        ]);
    }

    public function show($projectId)
    {
        $project = Project::with([
            'boreholes' => function ($query) {
                $query->select('id', 'project_id', 'name', 'depth');  // Selectează coloanele din boreholes
            },
            'client' => function ($query) {
                $query->select('id', 'name'); // Select only necessary columns from client
            },
            // 'boreholes.samples' => function ($query) {
            //     $query->select('id', 'borehole_id', 'name');  // Selectează coloanele din samples
            // }
        ])->findOrFail($projectId);


        return Inertia::render('ProjectShow', [
            'project' => $project,
        ]);
    }
}
