<?php

namespace App\Http\Controllers;

use App\Libraries\PointInPolygon;
use App\Libraries\SoilPhaseCalculator;
use App\Libraries\SoilTypeIdentifier;
use App\Libraries\Stratigrapher;
use App\Libraries\TernaryPlot;
use App\Models\Borehole;
use App\Models\BulkDensity;
use App\Models\Density;
use App\Models\DerivedSoilPhaseIndex;
use App\Models\Granulometry;
use App\Models\Sample;
use App\Models\SoilType;
use App\Models\Stratum;
use App\Models\WaterContent;
use Exception;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class BoreholeController extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    public function show($boreholeId)
    {
        $borehole = Borehole::with('samples.soilType')->findOrFail($boreholeId);
        $samples = $borehole->samples;
        // dd($samples);
        $stratigrapher = new Stratigrapher($samples, 0.5); // Pragul minim de grosime
        $granulometricStrata = [];
        $granulometricStrata = $stratigrapher->generateStrata();
        $strata = Borehole::withCount('strata')->findOrFail($boreholeId)->strata_count;
        // dd($strata);
        //         if ($strata > 0) {
        // $strataData = Borehole::with('strata')->findOrFail($boreholeId)->strata->toArray();
        //         }
        $strataData = $strata > 0 ? Borehole::with('strata')->findOrFail($boreholeId)->strata->toArray() : $stratigrapher->generateStrata();
        // dd($strataData);
        return Inertia::render('Boreholes/Show', [
            'borehole' => $borehole,
            'strataData' => $strataData,
            'granulometricStrata' => $granulometricStrata
        ]);
    }

    public function generateStratification($boreholeId)
    {
        $borehole = Borehole::with('samples.soilType')->findOrFail($boreholeId);
        $samples = $borehole->samples;

        // dd($samples);
        $stratigrapher = new Stratigrapher($samples, 0.5); // Pragul minim de grosime
        $strataData = $stratigrapher->generateStrata();
        // dd($strataData);
        foreach ($strataData as $stratumData) {
            $borehole->strata()->create($stratumData);
        }

        DB::transaction(function () use ($boreholeId, $strataData) {
            // Șterge straturile existente
            $borehole = Borehole::findOrFail($boreholeId);
            $borehole->strata()->delete();

            // Creează straturile noi
            foreach ($strataData as $stratumData) {
                $borehole->strata()->create($stratumData);
            }
        });

        // foreach ($strataData as $stratumData) {
        //     Stratum::updateOrCreate(
        //         [
        //             'borehole_id' => $borehole->id,
        //             'start_depth' => $stratumData['start_depth'],
        //         ],
        //         $stratumData + ['borehole_id' => $borehole->id]
        //     );
        // }
        return back(303);
        // return redirect()->route('boreholes.show', $borehole->id)->with('success', 'Stratificația a fost generată cu succes.');
    }

    public function getStratification($boreholeId)
    {

        $borehole = Borehole::with('samples.soilType')->findOrFail($boreholeId);
        $samples = $borehole->samples;

        $stratigrapher = new Stratigrapher($samples, 0.5); // Pragul minim de grosime
        $strataData = $stratigrapher->generateStrata();

        if ($strataData === []) {
            // Dacă nu există straturi generate, returnăm un mesaj de eroare
            // return response()->json(['message' => 'Nu există stratificație pentru acest foraj.'], 404);
            // Redirectăm înapoi cu un mesaj de eroare
            // return redirect()->back()->with('error', 'Nu există stratificație pentru acest foraj.');
            // return redirect()->route('borehole.show', $boreholeId)->with('error', 'Nu există stratificație pentru acest foraj.');
            // return redirect()->back()->with('error', 'Nu există stratificație pentru acest foraj.');
            // return redirect()->route('borehole.show', $boreholeId)->with('error', 'Nu există stratificație pentru acest foraj.');
            return response()->json(['message' => 'Nu există stratificație pentru acest foraj.'], 404);
        }

        return response()->json($strataData);
    }

    public function updateStratification_(Request $request, $boreholeId)
    {
        $validatedData = $request->validate([
            'strata' => 'required|array|min:1',
            'strata.*.depth_from' => 'required|numeric|min:0',
            'strata.*.depth_to' => 'required|numeric|gt:strata.*.depth_from',
            'strata.*.soil_type' => 'required|string',
        ]);

        DB::transaction(function () use ($boreholeId, $validatedData) {
            // Șterge straturile existente
            $borehole = Borehole::findOrFail($boreholeId);
            $borehole->strata()->delete();

            // Creează straturile noi
            foreach ($validatedData['strata'] as $stratumData) {
                $borehole->strata()->create($stratumData);
            }
        });

        return redirect()->back()->with('success', 'Stratificația a fost actualizată cu succes.');
    }
    public function updateStratification(Request $request, $boreholeId)
    {
        $validatedData = $request->validate([
            'strata' => 'required|array|min:1',
            'strata.*.depth_from' => 'required|numeric|min:0',
            'strata.*.depth_to' => 'required|numeric|gt:strata.*.depth_from',
            'strata.*.soil_type' => 'required|string',
        ]);

        DB::transaction(function () use ($boreholeId, $validatedData) {
            // Șterge straturile existente
            $borehole = Borehole::findOrFail($boreholeId);
            $borehole->strata()->delete();

            // Sortăm straturile după adâncimea de început
            $sortedStrata = collect($validatedData['strata'])
                ->sortBy('depth_from')
                ->values()
                ->toArray();

            // Procesăm și combinăm straturile adiacente de același tip
            $mergedStrata = [];
            $currentStratum = null;

            foreach ($sortedStrata as $stratumData) {
                if ($currentStratum === null) {
                    // Primul strat
                    $currentStratum = $stratumData;
                } elseif (
                    $currentStratum['soil_type'] === $stratumData['soil_type'] &&
                    abs($currentStratum['depth_to'] - $stratumData['depth_from']) < 0.001
                ) {
                    // Straturi adiacente de același tip - le combinăm
                    // Folosim o toleranță mică pentru comparația între numere cu virgulă mobilă
                    $currentStratum['depth_to'] = $stratumData['depth_to'];
                } else {
                    // Strat diferit sau gap între straturi - salvăm stratul curent și începem unul nou
                    $mergedStrata[] = $currentStratum;
                    $currentStratum = $stratumData;
                }
            }

            // Adăugăm ultimul strat procesat
            if ($currentStratum !== null) {
                $mergedStrata[] = $currentStratum;
            }

            // Creează straturile noi după procesul de combinare
            foreach ($mergedStrata as $stratumData) {
                $borehole->strata()->create($stratumData);
            }
        });

        return redirect()->back()->with('success', 'Stratificația a fost actualizată cu succes. Straturile adiacente de același tip au fost combinate automat.');
    }

    public function updateStratification__(Request $request, $boreholeId)
    {
        $validatedData = $request->validate([
            'strata' => 'required|array|min:1',
            'strata.*.depth_from' => 'required|numeric|min:0',
            'strata.*.depth_to' => 'required|numeric',
            'strata.*.soil_type' => 'required|string',
        ]);

        // Extragem datele de straturi și eliminăm câmpurile care nu ne interesează
        $strata = [];
        foreach ($validatedData['strata'] as $layer) {
            $strata[] = [
                'soil_type' => $layer['soil_type'],
                'depth_from' => (float) $layer['depth_from'],
                'depth_to' => (float) $layer['depth_to'],
                'note' => $layer['note'] ?? null,
            ];
        }

        // Sortăm straturile după adâncimea de început
        usort($strata, function ($a, $b) {
            return $a['depth_from'] <=> $b['depth_from'];
        });

        // Procesăm straturile pentru a elimina suprapunerile și a combina straturile adiacente
        $processedStrata = $this->processStrata($strata);
        // dd($strata);

        DB::transaction(function () use ($boreholeId, $processedStrata) {
            // Șterge straturile existente
            $borehole = Borehole::findOrFail($boreholeId);
            $borehole->strata()->delete();

            // Creăm noile straturi
            foreach ($processedStrata as $stratumData) {
                $borehole->strata()->create($stratumData);
            }
        });

        return redirect()->back()->with('success', 'Stratificația a fost actualizată cu succes.');
    }

    private function processStrata(array $inputStrata)
    {
        // First, extract only the data we need and convert to proper types
        $strata = [];
        foreach ($inputStrata as $layer) {
            // Extract directly if it's a simple array
            if (isset($layer['soil_type']) && isset($layer['depth_from']) && isset($layer['depth_to'])) {
                $strata[] = [
                    'soil_type' => $layer['soil_type'],
                    'depth_from' => (float) $layer['depth_from'],
                    'depth_to' => (float) $layer['depth_to'],
                    'note' => $layer['note'] ?? null,
                ];
            }
            // Extract from nested reactive structure if that's what we received
            else if (isset($layer['_custom']['value']['soil_type'])) {
                $value = $layer['_custom']['value'];
                $strata[] = [
                    'soil_type' => $value['soil_type'],
                    'depth_from' => (float) $value['depth_from'],
                    'depth_to' => (float) $value['depth_to'],
                    'note' => $value['note'] ?? null,
                ];
            }
        }

        // Sort by depth_from to ensure proper ordering
        usort($strata, function ($a, $b) {
            return $a['depth_from'] <=> $b['depth_from'];
        });

        // Step 1: Create all possible depth boundaries
        $boundaries = [];
        foreach ($strata as $layer) {
            $boundaries[] = $layer['depth_from'];
            $boundaries[] = $layer['depth_to'];
        }
        $boundaries = array_unique($boundaries);
        sort($boundaries);

        // Step 2: Create segments between each boundary
        $segments = [];
        for ($i = 0; $i < count($boundaries) - 1; $i++) {
            $start = $boundaries[$i];
            $end = $boundaries[$i + 1];

            // Skip zero-length segments
            if (abs($end - $start) < 0.0001) {
                continue;
            }

            // Find all layers that cover this segment
            $coveringLayers = [];
            foreach ($strata as $index => $layer) {
                if ($layer['depth_from'] <= $start && $layer['depth_to'] >= $end) {
                    $coveringLayers[] = [
                        'index' => $index,
                        'layer' => $layer,
                        'priority' => -$index // Negative to prioritize more recent layers (those with higher index)
                    ];
                }
            }

            // Sort covering layers by priority
            usort($coveringLayers, function ($a, $b) {
                return $b['priority'] <=> $a['priority']; // Descending priority
            });

            // Take the top priority layer (if any)
            if (!empty($coveringLayers)) {
                $topLayer = $coveringLayers[0]['layer'];
                $segments[] = [
                    'depth_from' => $start,
                    'depth_to' => $end,
                    'soil_type' => $topLayer['soil_type'],
                    'note' => $topLayer['note']
                ];
            }
        }

        // Step 3: Merge adjacent segments with the same soil type
        $result = [];
        $current = null;

        foreach ($segments as $segment) {
            if ($current === null) {
                $current = $segment;
            } else if (
                $current['soil_type'] === $segment['soil_type'] &&
                abs($current['depth_to'] - $segment['depth_from']) < 0.0001
            ) {
                // Merge this segment with the current one
                $current['depth_to'] = $segment['depth_to'];
            } else {
                // Add the current segment to results and start a new one
                $result[] = $current;
                $current = $segment;
            }
        }

        // Add the last segment
        if ($current !== null) {
            $result[] = $current;
        }

        return $result;
    }
}
