<?php

namespace App\Libraries;

use App\Models\Sample;
use Illuminate\Support\Collection;

class Stratigrapher
{
    protected Collection $samples;
    protected float $minThickness;

    public function __construct(Collection $samples, float $minThickness = 0.5)
    {
        // Asigură că probele sunt sortate după adâncime
        $this->samples = $samples->sortBy(function ($sample) {
            return (float) $sample->depth;
        })->values();

        $this->minThickness = $minThickness;
    }

    public function generateStrata(): array
    {
        // Dacă nu avem probe, returnăm un array gol
        if ($this->samples->isEmpty()) {
            return [];
        }

        $strata = [];
        $currentSoilType = $this->samples->first()->soilType?->name;
        $startDepth = 0; // Începem mereu de la suprafață
        $lastDepth = 0;

        // Procesăm toate probele începând cu a doua
        // (prima probă deja a stabilit tipul de sol inițial)
        foreach ($this->samples as $sample) {
            $depth = (float) $sample->depth;
            $soilType = $sample->soilType?->name;

            // Verificăm dacă tipul de sol s-a schimbat
            if ($soilType !== $currentSoilType) {
                // Calculăm grosimea potențialului strat
                $thickness = $depth - $lastDepth;

                // Adăugăm un strat nou doar dacă grosimea depășește pragul minim
                if ($thickness >= $this->minThickness) {
                    // Finalizăm stratul curent
                    $strata[] = [
                        'soil_type' => $currentSoilType,
                        'depth_from' => $startDepth,
                        'depth_to' => $lastDepth,
                    ];

                    // Începem un strat nou
                    $currentSoilType = $soilType;
                    $startDepth = $lastDepth;
                }
            }

            // Actualizăm adâncimea ultimei probe procesate
            $lastDepth = $depth;
        }

        // Adăugăm ultimul strat (dacă mai este ceva de adăugat)
        if ($startDepth < $lastDepth) {
            $strata[] = [
                'soil_type' => $currentSoilType,
                'depth_from' => $startDepth,
                'depth_to' => $lastDepth,
            ];
        }

        return $strata;
    }
}

// namespace App\Libraries;

// use App\Models\Sample;
// use Illuminate\Support\Collection;

// class Stratigrapher
// {
//     protected Collection $samples;
//     protected float $minThickness;

//     public function __construct(Collection $samples, float $minThickness = 0.5)
//     {
//         $this->samples = $samples->sortBy('depth')->values();
//         $this->minThickness = $minThickness;
//     }

//     public function generateStrata(): array
//     {
//         $strata = [];
//         $currentSoilType = null;
//         $startDepth = 0;
//         $previousDepth = 0;
//         $isFirstSample = true;  // Flag pentru prima probă

//         for ($i = 0; $i < $this->samples->count(); $i++) {
//             $sample = $this->samples[$i];
//             $depth = $sample->depth;
//             $soilType = $sample->soilType?->name;

//             if ($isFirstSample) {
//                 $currentSoilType = $soilType;
//                 $startDepth = 0;  // Forțează începerea de la suprafață
//                 $isFirstSample = false;  // Resetează flag-ul
//             } elseif ($soilType !== $currentSoilType) {
//                 // Calculăm grosimea stratului potențial nou
//                 $thickness = $depth - $previousDepth;
//                 if ($thickness >= $this->minThickness) {
//                     // Încheiem stratul anterior
//                     $strata[] = [
//                         'soil_type' => $currentSoilType,
//                         'depth_from' => $startDepth,
//                         'depth_to' => $previousDepth,
//                     ];
//                     // Începem un nou strat
//                     $currentSoilType = $soilType;
//                     $startDepth = $previousDepth;
//                 }
//             }
//             $previousDepth = $depth;
//         }

//         // Adăugăm ultimul strat
//         $strata[] = [
//             'soil_type' => $currentSoilType,
//             'depth_from' => $startDepth,
//             'depth_to' => $previousDepth,
//         ];

//         return $strata;
//     }

//     public function generateStrata_(): array
//     {
//         // dd($this->samples);
//         dd($this->samples->pluck('depth', 'id')->toArray());
//         $strata = [];
//         $currentSoilType = null;
//         $startDepth = 0;
//         $previousDepth = 0;

//         for ($i = 0; $i < $this->samples->count(); $i++) {
//             $sample = $this->samples[$i];
//             $depth = $sample->depth;
//             // $soilType = $sample->soilType->name;
//             $soilType = $sample->soilType?->name;

//             // Dacă este primul eșantion, inițializăm stratul curent
//             if ($currentSoilType === null) {
//                 $currentSoilType = $soilType;
//                 $startDepth = $previousDepth;
//             } elseif ($soilType !== $currentSoilType) {
//                 // Calculăm grosimea stratului potențial nou
//                 $thickness = $depth - $previousDepth;
//                 if ($thickness < $this->minThickness) {
//                     // Ignorăm stratul subțire și continuăm cu stratul curent
//                 } else {
//                     // Încheiem stratul anterior
//                     $strata[] = [
//                         'soil_type' => $currentSoilType,
//                         'depth_from' => $startDepth,
//                         'depth_to' => $previousDepth,
//                     ];
//                     // Începem un nou strat
//                     $currentSoilType = $soilType;
//                     $startDepth = $previousDepth;
//                 }
//             }
//             $previousDepth = $depth;
//         }

//         // Adăugăm ultimul strat
//         $strata[] = [
//             'soil_type' => $currentSoilType,
//             'depth_from' => $startDepth,
//             'depth_to' => $previousDepth,
//         ];

//         return $strata;
//     }
// }
