<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\Request;

class CurrentProjectController extends Controller
{
    public function update(Request $request)
    {
        $request->validate([
            'current_project_id' => 'required|exists:projects,id',
        ]);

        $project = Project::find($request->input('current_project_id'));
        // dd($project);

        if (! $request->user()->switchProject($project)) {
            abort(403);
        }

        return back(303);

        // return redirect()->back()->with('success', 'Current project updated successfully.');
    }
}
