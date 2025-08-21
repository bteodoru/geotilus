<?php

namespace App\Libraries\SoilNaming;

use App\Libraries\SoilClassification\Granulometry\GranulometryClassificationResult;
use App\Services\Granulometry\GranulometryAnalysisService;
use App\Models\Granulometry;



/**
 * Serviciul principal pentru denumirea pământurilor
 */
class SoilNamingService
{
    public function __construct(
        private GranulometryAnalysisService $granulometryService,
        private NamingConfiguration $config
    ) {}


    public function buildSoilName(GranulometryClassificationResult $classificationResult): SoilNamingResult
    {
        // dd($classificationResult);
        $primaryClassification = $classificationResult->getPrimaryClassification();
        $usedFractions = $this->extractUsedFractions($classificationResult);
        $granulometryModel = $classificationResult->getGranulometry();

        $availableFractions = $this->getAvailableFractions($granulometryModel, $usedFractions);

        $sortedFractions = $this->sortFractionsByPercentage($availableFractions);

        $secondaryFractions = $this->categorizeSecondaryFractions($sortedFractions, $primaryClassification);
        $tertiaryFractions = $this->categorizeTertiaryFractions($sortedFractions);

        $shouldInvert = $this->shouldInvertDominance($sortedFractions);

        $finalName = $this->constructFinalName(
            $primaryClassification,
            $secondaryFractions,
            $shouldInvert,
            $granulometryModel
        );

        return new SoilNamingResult(
            finalName: $finalName,
            primaryClassification: $primaryClassification,
            secondaryFractions: $secondaryFractions,
            tertiaryFractions: $tertiaryFractions,
            metadata: [
                'used_fractions' => $usedFractions,
                'available_fractions' => $availableFractions,
                'inversion_applied' => $shouldInvert,
                'configuration' => $this->config
            ]
        );
    }


    private function extractUsedFractions(GranulometryClassificationResult $result): array
    {
        $metadata = $result->getClassificationMetadata();

        if (!isset($metadata['used_fractions'])) {
            return [];
        }
        return $metadata['used_fractions'];
    }

    private function getAvailableFractions(Granulometry $granulometry, array $usedFractions): array
    {

        $allFractionNames = array_keys($this->granulometryService->getAllFractionNames());
        $availableFractionNames = array_diff($allFractionNames, $this->granulometryService->expandGranulometricFractions($usedFractions));

        return $this->granulometryService->extractGranulometricFractions(
            $granulometry,
            $availableFractionNames
        );
    }


    private function sortFractionsByPercentage(array $fractions): array
    {
        // Filtrează fracțiunile cu valoare 0
        $nonZeroFractions = array_filter($fractions, fn($value) => $value > 0);
        arsort($nonZeroFractions);
        return $nonZeroFractions;
    }


    private function categorizeSecondaryFractions(
        array $sortedFractions,
        string $primaryClassification
    ): array {
        $secondary = [];

        foreach ($sortedFractions as $fraction => $percentage) {
            if ($percentage >= $this->config->getMentionThreshold()) {
                $secondary[$fraction] = [
                    'percentage' => $percentage,
                    'type' => $percentage >= $this->config->getAdjectiveThreshold() ? 'adjective' : 'mention',
                    'rare' => $percentage < $this->config->getRareThreshold(),
                    'name' => $this->granulometryService->getFractionName($fraction),
                    'adjective' => $this->getContextualAdjective($fraction, $primaryClassification)
                ];
            }
        }

        return $secondary;
    }


    private function getContextualAdjective(string $fraction, string $primaryClassification): string
    {
        // Încearcă să determine genul pe baza clasificării primare
        $baseGender = $this->inferGenderFromClassification($primaryClassification);
        return $this->granulometryService->getAdjective($fraction, $baseGender);
    }

    /**
     * Inferează genul pe baza clasificării primare
     */
    private function inferGenderFromClassification(string $classification): string
    {
        $lowerClassification = strtolower($classification);

        if (str_contains($lowerClassification, 'argil')) {
            return 'clay'; // feminin
        }

        // Default: masculin (nisip, pietriș, etc.)
        return 'sand';
    }

    /**
     * Categorisează fracțiunile terțiare
     */
    private function categorizeTertiaryFractions(array $sortedFractions): array
    {
        $tertiary = [];

        foreach ($sortedFractions as $fraction => $percentage) {
            if ($percentage > 0 && $percentage < $this->config->getMentionThreshold()) {
                $tertiary[$fraction] = [
                    'percentage' => $percentage,
                    'name' => $this->granulometryService->getFractionName($fraction)
                ];
            }
        }

        return $tertiary;
    }

    /**
     * Verifică dacă trebuie inversată dominanța
     */
    private function shouldInvertDominance(array $sortedFractions): bool
    {
        if (empty($sortedFractions)) {
            return false;
        }

        $highestPercentage = reset($sortedFractions);
        return $highestPercentage >= $this->config->getInversionThreshold();
    }

    /**
     * Construiește denumirea finală
     */
    private function constructFinalName(
        string $primaryClassification,
        array $secondaryFractions,
        bool $shouldInvert,
        Granulometry $granulometry
    ): string {
        if (empty($secondaryFractions)) {
            return $primaryClassification;
        }

        if ($shouldInvert) {
            return $this->constructInvertedName($primaryClassification, $secondaryFractions);
        }

        return $this->constructStandardName($primaryClassification, $secondaryFractions);
    }

    /**
     * Construiește denumirea inversată
     */
    private function constructInvertedName(string $primaryClassification, array $secondaryFractions): string
    {
        $dominantFraction = array_key_first($secondaryFractions);
        $dominantData = reset($secondaryFractions);

        $newPrimary = $dominantData['name'];
        $connector = $this->config->getConnectors()['with'];

        if ($dominantData['rare']) {
            $rareModifier = $this->config->getConnectors()['rare'];
            $newPrimary = ucfirst($rareModifier) . ' ' . strtolower($newPrimary);
        }

        $remaining = array_slice($secondaryFractions, 1, null, true);
        $result = $newPrimary . ' ' . $connector . ' ' . strtolower($primaryClassification);

        if (!empty($remaining)) {
            $remainingNames = $this->buildFractionNamesList($remaining);
            $result .= ' ' . $connector . ' ' . $remainingNames;
        }

        return $result;
    }


    private function constructStandardName(string $primaryClassification, array $secondaryFractions): string
    {
        $adjectives = [];
        $mentions = [];
        foreach ($secondaryFractions as $fraction => $data) {
            if ($data['type'] === 'adjective') {
                $adjective = $data['adjective'];
                if ($data['rare']) {
                    $adjective = $this->config->getConnectors()['rare'] . ' ' . $adjective;
                }
                $adjectives[] = $adjective;
            } else {
                $name = $data['rare']
                    ? $this->config->getConnectors()['rare'] . ' ' . strtolower($data['name'])
                    : strtolower($data['name']);
                $mentions[] = $name;
            }
        }

        $result = $primaryClassification;

        // Adaugă adjectivele
        if (!empty($adjectives)) {
            $result .= ' ' . implode(' ', $adjectives);
        }

        // Adaugă mențiunile
        if (!empty($mentions)) {
            $connector = $this->config->getConnectors()['with'];
            $result .= ' ' . $connector . ' ' . $this->formatMentionsList($mentions);
        }

        return $result;
    }


    private function buildFractionNamesList(array $fractions): string
    {
        $names = [];
        foreach ($fractions as $fraction => $data) {
            $name = $data['rare']
                ? $this->config->getConnectors()['rare'] . ' ' . strtolower($data['name'])
                : strtolower($data['name']);
            $names[] = $name;
        }

        return $this->formatMentionsList($names);
    }


    private function formatMentionsList(array $mentions): string
    {
        if (count($mentions) === 1) {
            return $mentions[0];
        } elseif (count($mentions) === 2) {
            return $mentions[0] . ' ' . $this->config->getConnectors()['and'] . ' ' . $mentions[1];
        } else {
            $last = array_pop($mentions);
            return implode(', ', $mentions) . ' ' . $this->config->getConnectors()['and'] . ' ' . $last;
        }
    }
}
