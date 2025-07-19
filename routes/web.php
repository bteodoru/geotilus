<?php

use App\Http\Controllers\BoreholeController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\Controller;
use App\Http\Controllers\CurrentProjectController;
use App\Http\Controllers\ProjectBoreholesController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ProjectSamplesController;
use App\Http\Controllers\SampleController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/dashboard', function () {
        return Inertia::render('Dashboard');
    })->name('dashboard');
});

Route::get('/granulometry', function () {
    return Inertia::render('Granulometry');
})->middleware(['auth:sanctum', 'verified']);

Route::group([
    'middleware' => ['auth:sanctum', 'verified']
], function () {
    Route::put('/current-project', [CurrentProjectController::class, 'update'])->name('current-project.update');

    Route::get('/projects', [ProjectController::class, 'index'])->name('projects.index');
    Route::get('/projects/{project}', [ProjectController::class, 'show'])->name('projects.show');
    Route::get('/projects/{project}/boreholes', [ProjectBoreholesController::class, 'show'])->name('projects.boreholes.show');
    Route::get('/projects/{project}/samples', [ProjectSamplesController::class, 'show'])->name('projects.samples.show');
    Route::get('/clients', [ClientController::class, 'index'])->name('clients.index');
    Route::get('/samples/{sample}/phase-relationships', [SampleController::class, 'phaseRelationships'])->name('sample.phaseRelationships');
    Route::get('/samples/{sample}', [SampleController::class, 'show'])->name('sample.show');
    Route::post('/samples/{sample}/identify', [SampleController::class, 'identify'])->name('sample.identify');
    Route::post('/samples/{sample}/derive-soil-phase-indices', [SampleController::class, 'phaseIndices'])->name('sample.phase.indices');
    Route::get('/samples/{sample}/data-edit', [SampleController::class, 'dataEdit'])->name('sample.data.edit');
    Route::post('/samples/{sample}/data-update', [SampleController::class, 'updateSampleData'])->name('sample.data.update');
    Route::get('/boreholes/{borehole}', [BoreholeController::class, 'show'])->name('borehole.show');
    Route::post('/boreholes/{borehole}/stratigraphy', [BoreholeController::class, 'generateStratification'])->name('borehole.stratigraphy');
    // Route::get('/boreholes/{borehole}/stratigraphy', [BoreholeController::class, 'getStratification'])->name('get.borehole.stratigraphy');
    Route::post('/boreholes/{borehole}/update-stratigraphy', [BoreholeController::class, 'updateStratification'])->name('borehole.update.stratigraphy');
});

Route::get('/granulometry', [Controller::class, 'granulometry'])->name('granulometry.show');
Route::get('/samples', [SampleController::class, 'index'])->name('sample.index');
Route::get('/samples/{sample}/granulometry', [SampleController::class, 'granulometry'])->name('sample.granulometry');
