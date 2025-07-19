<?php

namespace App\Http\Controllers;

use App\Libraries\PointInPolygon;
use App\Libraries\SoilClassification\Granulometry\GranulometryClassificationFactory;
use App\Libraries\SoilClassification\Granulometry\Implementations\SR_EN_ISO_14688_2018;
use App\Libraries\SoilClassification\SoilClassificationFactory;
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
use Inertia\Inertia;
use App\Libraries\SoilClassification\Granulometry\Implementations\STAS_1243___1988;
use App\Libraries\SoilClassification\Services\CasagrandeChartService;
use App\Libraries\SoilClassification\Services\GranulometryService;
use App\Services\GeometryService;
use App\Services\Granulometry\GranulometryAnalysisService;
use App\Libraries\SoilClassification\Plasticity\Classifiers\CasagrandeClassifier;
use App\Libraries\SoilClassification\Plasticity\PlasticityClassificationFactory;
use App\Libraries\SoilNaming\SoilNamingFactory;
use App\Libraries\SoilNaming\SoilNamingResolver;

class SampleController extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    public function index()
    {

        $samples = Sample::all();
        return Inertia::render('Samples', [
            'samples' => $samples,
        ]);
    }

    public function granulometry(Sample $sample)
    {
        $sample->load('granulometry');
        // $sample = Sample::with('granulometry')->find(12);
        // $point = array($sample->granulometry->silt + 0.5 * $sample->granulometry->clay, sqrt(3) * 0.5 * $sample->granulometry->clay);
        // $ternaryPlot = new TernaryPlot();
        // $point_ = $ternaryPlot->toCartesianCoordinates(array($sample->granulometry->clay, $sample->granulometry->sand, $sample->granulometry->silt));
        // // dd($point, $point_);
        // $pointLocation = new PointInPolygon(true);
        // foreach (config('geotilus.soilsByNP074') as $key => $value) {
        //     // foreach (config('geotilus.soilsBySTAS1243') as $key => $value) {
        //     $soil_type = "Utilizați un criteriu suplimentar pentru clasificarea pământului";
        //     if ($pointLocation->pointInPolygon($point_, array_map(array($ternaryPlot, 'toCartesianCoordinates'), $value['points'])) === "inside") {
        //         // if ($pointLocation->pointInPolygon($point, $value['points']) === "inside") {
        //         $soil_type = $key;
        //         break;
        //     }
        // }
        $clay = $sample->granulometry->clay;
        $silt = $sample->granulometry->silt;
        $sand = $sample->granulometry->sand;
        $soilType = new SoilTypeIdentifier();
        $soil_type = $soilType->identify($clay, $silt, $sand);
        // dd($soil_type);

        $sample->soil_type = $soil_type;
        return Inertia::render('Granulometry', [
            'sample' => $sample,
        ]);
    }

    public function phaseIndices(Request $request, $sampleId)
    {
        $sample = Sample::with('waterContent', 'bulkDensity', 'particleDensity')->findOrFail($sampleId);
        // dd($sample);
        $water_content = $sample->waterContent->water_content;
        $bulk_density = $sample->bulkDensity->bulk_density;
        $particle_density = $sample->particleDensity->particle_density;
        $phaseRelationships = new SoilPhaseCalculator();
        try {
            $phaseRelationships->compute($water_content, $bulk_density, $particle_density);
        } catch (Exception $e) {
            echo "There was an error inserting the row - " . $e->getMessage();
        }
        // dd($phaseRelationships->getVoidsRatio());


        DerivedSoilPhaseIndex::updateOrCreate(
            [
                'sample_id' => $request->sample
            ],
            [
                'dry_density' => $phaseRelationships->getDryDensity(),
                'porosity' => $phaseRelationships->getPorosity(),
                'voids_ratio' => $phaseRelationships->getVoidsRatio(),
                'moisture_content_at_saturation' => $phaseRelationships->getMoistureContentAtSaturation(),
                'degree_of_saturation' => $phaseRelationships->getDegreeOfSaturation(),
                'saturated_density' => $phaseRelationships->getSaturatedDensity(),
                'submerged_density' => $phaseRelationships->getSubmergedDensity()
            ]
        );
        return back(303);
    }
    // public function cartezianeLaTermare($x, $y)
    // {
    //     $sqrt3 = sqrt(3);

    //     // Calculam procentele pentru fiecare componenta
    //     $praf = $x - ($y / $sqrt3);
    //     $argila = (2 * $y) / $sqrt3;
    //     $nisip = 100 - $praf - $argila;

    //     // Rotunjim la 2 zecimale pentru lizibilitate
    //     return [
    //         'argila' => round($argila, 2),
    //         'nisip' => round($nisip, 2),
    //         'praf' => round($praf, 2),
    //     ];
    // }

    protected function ternaryToCartesian($a, $b = null, $c = null): array
    {
        // Suportă ambele formate: array sau parametri separați
        if (is_array($a)) {
            $ternaryCoordinates = $a;
            $a = $ternaryCoordinates[0];
            $b = $ternaryCoordinates[1];
            $c = $ternaryCoordinates[2];
        } else {
            $ternaryCoordinates = [$a, $b, $c];
        }

        // Validare că suma = 100%
        $sum = array_sum($ternaryCoordinates);
        if (abs($sum - 100) > 0.001) {
            throw new \InvalidArgumentException("The sum of the ternary coordinates is not 100. Got: {$sum}");
        }

        // Formula standard pentru conversie ternary → cartesian
        $x = 0.5 * (2 * $a + $b);
        $y = sqrt(3) * $b * 0.5;

        return [$x, $y];
    }

    protected function findSoilTypeInDiagram(float $x, float $y): ?array
    {
        $diagram = app(SR_EN_ISO_14688_2018::class)->getTernaryDiagram_();
        $polygonChecker = new PointInPolygon(
            checkPointOnVertex: false,
            checkPointOnBoundary: true,
            tolerance: 0.001
        );

        foreach ($diagram as $soilTypeName => $soilTypeData) {
            // Convertește toate punctele poligonului din ternare în carteziene
            $cartesianPoints = array_map(function ($ternaryPoint) {
                return $this->ternaryToCartesian($ternaryPoint);
            }, $soilTypeData['points']);



            $result = $polygonChecker->pointInPolygon([$x, $y], $cartesianPoints);

            if ($result === PointInPolygon::INSIDE || $result === PointInPolygon::ON_BOUNDARY) {
                return [
                    'name' => implode(',', array_keys($soilTypeData['soils'])),
                    // 'color' => $soilTypeData['color'],
                    'points' => $cartesianPoints,
                    'soils' => $soilTypeData['soils'],
                ];
            }
        }

        return null;
    }
    public function show($sampleId)
    {


        // // Datele originale
        // $domains = [
        //     "Nisip" => [
        //         "points" => [
        //             [0, 0],
        //             [5, 8.6602545],
        //             [10, 0]
        //         ],
        //         "color" => "#42a2dc",
        //     ],
        //     "Nisip prăfos" => [
        //         "points" => [
        //             [10, 0],
        //             [5, 8.6602545],
        //             [7.5, 12.990381],
        //             [50, 12.990381],
        //             [50, 0]
        //         ],
        //         "color" => "#42a2dc",
        //     ],
        //     "Praf nisipos" => [
        //         "points" => [
        //             [50, 0],
        //             [50, 12.990381],
        //             [62.5, 12.990381],
        //             [70, 0]
        //         ],
        //         "color" => "#42a2dc",
        //     ],
        //     "Praf" => [
        //         "points" => [
        //             [70, 0],
        //             [62.5, 12.990381],
        //             [92.5, 12.990381],
        //             [100, 0]
        //         ],
        //         "color" => "#42a2dc",
        //     ],
        //     "Nisip argilos" => [
        //         "points" => [
        //             [7.5, 12.990381],
        //             [15, 25.980762],
        //             [50, 25.980762],
        //             [50, 12.990381]
        //         ],
        //         "color" => "#42a2dc",
        //     ],
        //     "Praf nisipos argilos" => [
        //         "points" => [
        //             [50, 12.990381],
        //             [50, 25.980762],
        //             [55, 25.980762],
        //             [62.5, 12.990381]
        //         ],
        //         "color" => "#42a2dc",
        //     ],
        //     "Praf argilos" => [
        //         "points" => [
        //             [62.5, 12.990381],
        //             [55, 25.980762],
        //             [85, 25.980762],
        //             [92.5, 12.990381]
        //         ],
        //         "color" => "#42a2dc",
        //     ],
        //     "Argilă nisipoasă" => [
        //         "points" => [
        //             [15, 25.980762],
        //             [30, 51.961525],
        //             [40, 51.961525],
        //             [52.5, 30.310888],
        //             [45, 25.980762]
        //         ],
        //         "color" => "#42a2dc",
        //     ],
        //     "Argilă prafoasă nisipoasă" => [
        //         "points" => [
        //             [45, 25.980762],
        //             [52.5, 30.310888],
        //             [55, 25.980762]
        //         ],
        //         "color" => "#42a2dc",
        //     ],
        //     "Argilă prafoasă" => [
        //         "points" => [
        //             [55, 25.980762],
        //             [52.5, 30.310888],
        //             [75, 43.30127],
        //             [85, 25.980762]
        //         ],
        //         "color" => "#42a2dc",
        //     ],
        //     "Argilă" => [
        //         "points" => [
        //             [52.5, 30.310888],
        //             [40, 51.961525],
        //             [70, 51.961525],
        //             [75, 43.30127]
        //         ],
        //         "color" => "#42a2dc",
        //     ],
        //     "Argilă grasă" => [
        //         "points" => [
        //             [30, 51.961525],
        //             [70, 51.961525],
        //             [50, 86.60254]
        //         ],
        //         "color" => "#42a2dc",
        //     ],
        // ];

        // // Transformam toate coordonatele
        // $domains_ternare = [];

        // foreach ($domains as $nume => $domeniu) {
        //     $points_ternare = [];

        //     foreach ($domeniu['points'] as $point) {
        //         $x = $point[0];
        //         $y = $point[1];

        //         $coord_ternare = $this->cartezianeLaTermare($x, $y);
        //         $points_ternare[] = $coord_ternare;
        //     }

        //     $domains_ternare[$nume] = [
        //         'points' => $points_ternare,
        //         'color' => $domeniu['color']
        //     ];
        // }

        // // Afisam rezultatele
        // echo "Coordonate ternare (nisip%, praf%, argila%):\n\n";

        // foreach ($domains_ternare as $nume => $domeniu) {
        //     echo "\"$nume\" => [\n";
        //     echo "    \"points\" => [\n";

        //     foreach ($domeniu['points'] as $point) {
        //         printf(
        //             "        [%.2f, %.2f, %.2f], \n",
        //             $point['argila'],
        //             $point['nisip'],
        //             $point['praf'],
        //             $point['argila'],
        //             $point['nisip'],
        //             $point['praf'],
        //         );
        //     }

        //     echo "    ],\n";
        //     echo "    \"color\" => \"{$domeniu['color']}\",\n";
        //     echo "],\n\n";
        // }




        $sample = Sample::findOrFail($sampleId);
        $sample->load('granulometry', 'soilType');

        // 1. SETUP-ul serviciilor (de obicei în ServiceProvider)
        $granulometryService = new GranulometryService();
        $factory = new GranulometryClassificationFactory($granulometryService);

        // 2. OBȚINEREA DATELOR - presupun că ai un Sample cu Granulometry
        // $sample = Sample::with('granulometry')->find(1);
        $granulometry = $sample->granulometry;

        // $test = new SoilNamingResolver();

        // $chartService = new CasagrandeChartService(new GeometryService());
        $test = new SoilNamingFactory(new GranulometryClassificationFactory($granulometryService), new PlasticityClassificationFactory());
        // dd($test->create('sr_en_iso_14688_2018')->nameSoil($sample));

        // 3. VERIFICAREA STANDARDELOR APLICABILE (opțional)
        $applicableStandards = $factory->getApplicableStandards($granulometry);
        // Returnează: ['stas_1243_1988' => ['name' => 'STAS 1243', 'version' => '1988', ...]]
        // dd($applicableStandards);
        // 4. CREAREA CLASIFICATORULUI pentru standardul dorit
        $classifier_stas = $factory->create('stas_1243_1988');
        // $classifier_np = $factory->create('np_074_2022');
        $classifier_np = $factory->create('sr_en_iso_14688_2005');
        $gs = new GeometryService;
        $ccs = new CasagrandeChartService($gs);
        $classifier = new CasagrandeClassifier($ccs);
        // dd($classifier->classify($sample->plasticity));

        try {
            $result = $classifier->classify($sample->plasticity);
            $response = $result->toArray();

            // return response()->json($response);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }




        // $analysisService = app(GranulometryAnalysisService::class);
        // dd($analysisService->getPrimaryFraction($sample->granulometry));

        // $availableIdentificationSystems = app(SoilClassificationFactory::class)->getAvailableRules();
        // $granulometry = SoilClassificationFactory::granulometry($sample, 'STAS_1243___1988');

        // $availableIdentificationSystems = SoilClassificationFactory::getAvailableSystemsForRule('Granulometry');
        // Returnează: ['stas_1243_1988' => [...], 'np_074_2022' => [...]]

        // $granulometry = SoilClassificationFactory::granulometry($sample, 'stas_1243_1988');

        // $granulometry = new GranulometryRule($sample, 'STAS_1243___1988');
        // $result = $granulometry->classify();
        // $granulometry = new GranulometryRule($sample, 'STAS_1243___1988');
        // $result = $granulometry->classify();
        // $classifier = new STAS_1243___1988($sample);
        // dd($availableIdentificationSystems, $result);

        // $availableIdentificationSystems = app(SoilTypeIdentifier::class)->getAvailableSystems();
        // dd($availableIdentificationSystems);

        return Inertia::render('Sample', [
            'sample' => $sample,
            'sample.availableIdentificationSystems' => $applicableStandards
        ]);
    }

    public function identify(Request $request)
    {

        // $clay = 10;
        $clay = $request->clay;
        // $silt = 62;
        $silt = $request->silt;
        // $sand = 100 - $clay - $silt;
        $sand = $request->sand;
        $system = $request->system;
        // $sample = new Sample();
        // $granulometry = new Granulometry();
        // $granulometry->clay = $clay;
        // $granulometry->silt = $silt;
        // $granulometry->sand = $sand;
        // $granulometry->sample()->associate($sample);
        $sample = Sample::findOrFail($request->sample);
        // dd($sample->load('granulometry'), $system);
        $granulometryService = new GranulometryService();

        // $soilIdentifier = SoilClassificationFactory::granulometry($sample, $system);
        $factory = new GranulometryClassificationFactory();
        $granulometry = $sample->granulometry;
        // $soilIdentifier = $factory->create($system);

        // dd($factory->getAvailableStandards());






        // $soilIdentifier = new SoilTypeIdentifier();

        // $soil_type = new SoilType();
        // $soil_type->sample()->associate($request->sample);
        // $soil_type->name = $soil;
        // $soil_type->save();
        try {
            // $soil = $soilIdentifier->classify();
            $soilIdentifier = $factory->create($system);
            // dd($granulometry->toArray());
            dd($soilIdentifier->classify($sample->granulometry));
            SoilType::updateOrCreate(
                ['sample_id' => $request->sample],
                [
                    'name' => $soilIdentifier->classify($granulometry)->getSoilType(),
                    'method' => $system
                ]
            );
            return back(303);
        } catch (Exception $e) {
            dd($e->getMessage());
            echo "There was an error inserting the row - " . $e->getMessage();
        }
    }

    public function updateSampleData(Request $request, $sampleId)
    {
        // Validăm datele din formular
        $validatedData = $request->validate([
            'granulometry.clay' => 'required|numeric',
            'granulometry.silt' => 'required|numeric',
            'granulometry.sand' => 'required|numeric',
            'granulometry.gravel' => 'nullable|numeric',
            'water_content.water_content' => 'required|numeric',
            'water_content.plastic_limit' => 'nullable|numeric',
            'water_content.liquid_limit' => 'nullable|numeric',
            'bulk_density.bulk_density' => 'required|numeric',
            'particle_density.particle_density' => 'nullable|numeric',
        ]);

        // Găsim proba după ID
        $sample = Sample::findOrFail($sampleId);

        // Gestionăm Granulometry
        $granulometryData = $request->input('granulometry');
        // dd($granulometryData);
        $granulometry = Granulometry::updateOrCreate(
            ['sample_id' => $sample->id], // Verificăm dacă există deja granulometry pentru acest sample
            $granulometryData
        );

        // Gestionăm MoistureContent
        $waterContentData = $request->input('water_content');
        $waterContent = WaterContent::updateOrCreate(
            ['sample_id' => $sample->id], // Verificăm dacă există deja water content pentru acest sample
            $waterContentData
        );

        // Gestionăm Density
        $densityData = $request->input('bulk_density');
        $density = BulkDensity::updateOrCreate(
            ['sample_id' => $sample->id], // Verificăm dacă există deja density pentru acest sample
            $densityData
        );

        // Redirecționăm sau returnăm un răspuns
        return back(303);
    }

    public function dataEdit($sampleId)
    {
        $sample = Sample::findOrFail($sampleId);
        $sample->load('granulometry', 'waterContent', 'bulkDensity', 'particleDensity', 'plasticity', 'soilType');
        return Inertia::render('EditDataSample', [
            'sample' => $sample,
        ]);
    }
}
