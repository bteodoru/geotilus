<?php

namespace App\Libraries\SoilClassification\Contracts;

use App\Libraries\SoilClassification\Granulometry\GranulometryClassificationResult;
use App\Models\Granulometry;

interface GranulometryClassifierInterface
{
    /**
     * Clasifică pământul pe baza datelor granulometrice
     * 
     * @param Granulometry $granulometry Datele granulometrice de clasificat
     * @return GranulometryClassificationResult Rezultatul clasificării
     * @throws \InvalidArgumentException pentru date invalide
     * @throws \RuntimeException pentru probleme de clasificare
     */
    public function classify(Granulometry $granulometry): GranulometryClassificationResult;

    /**
     * Returnează informațiile despre standardul de clasificare
     * 
     * @return array ['code' => string, 'name' => string, 'version' => string, 'country' => string, 'description' => string]
     */
    public function getStandardInfo(): array;

    /**
     * Verifică dacă acest clasificator se poate aplica pentru datele respective
     * 
     * @param Granulometry $granulometry Datele de verificat
     * @return bool True dacă standardul se poate aplica
     */
    public function isApplicable(Granulometry $granulometry): bool;

    /**
     * Returnează cerințele minime pentru aplicarea acestui standard
     * 
     * @return array Lista de câmpuri necesare
     */
    public function getRequiredFields(): array;

    /**
     * Returnează tipurile de sol pe care le poate identifica acest standard
     * 
     * @return array Lista tipurilor de sol cu denumirile lor
     */
    public function getAvailableSoilTypes(): array;

    /**
     * Verifică dacă standardul suportă o anumită categorie de pământ
     * 
     * @param string $category 'fine', 'coarse', 'very_coarse', 'mixed'
     * @return bool
     */
    public function supportsSoilCategory(string $category): bool;
}
