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

class ClientController extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    public function index()
    {


        $clients = Auth::user()->clients;
        return Inertia::render('Clients', [
            'clients' => $clients,
        ]);
    }
}
