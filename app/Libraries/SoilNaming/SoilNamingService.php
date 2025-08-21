<?php

namespace App\Libraries\SoilNaming;

use App\Libraries\DTOs\GranulometricFraction;
use App\Libraries\SoilClassification\Granulometry\GranulometryClassificationResult;
use App\Services\Granulometry\GranulometryAnalysisService;
use InvalidArgumentException;

class SoilNamingService
{
    public function __construct(
        private GranulometryAnalysisService $granulometryService,
        private NamingConfiguration $config
    ) {}

    public function buildSoilName(GranulometryClassificationResult $result): SoilNamingResult
    {
        $fractions = $result->getFractions();

        if (empty($fractions)) {
            throw new InvalidArgumentException("No fractions available for naming");
        }

        // Sortează fracțiunile descrescător după procent
        usort($fractions, fn($a, $b) => $b->getPercentage() <=> $a->getPercentage());

        // Limitează la maxim 3 fracțiuni
        $topFractions = array_slice($fractions, 0, 3);

        // Construiește denumirea
        return $this->buildNameFromFractions($topFractions);
    }

    private function buildNameFromFractions(array $fractions): SoilNamingResult
    {
        $primary = $fractions[0];
        // $finalName = $primary->getName();
        $adjectives = [];
        $mentions = [];

        for ($i = 1; $i < count($fractions); $i++) {
            $fraction = $fractions[$i];
            $percentage = $fraction->getPercentage();
            $class = $fraction->getClass();

            if ($this->shouldAdjectivize($fraction, $percentage, $class)) {
                $adjective = $this->getFractionAdjective($fraction, $primary);
                $adjectives[] = $adjective;
            } elseif ($this->shouldMention($percentage, $class)) {
                $mention = $this->formatMention($fraction, $percentage, $class);
                $mentions[] = $mention;
            }
        }

        $finalName = $this->buildFinalName($primary->getName(), $adjectives, $mentions);

        return new SoilNamingResult(
            finalName: $finalName,
            primaryFraction: $primary,
            furtherFractions: array_slice($fractions, 1),
            metadata: [
                'sorted_fractions' => array_map(fn($fraction) => [
                    $fraction->getLabel() => $fraction->getPercentage()
                ], $fractions),
                'configuration' => $this->config
            ]
        );
    }

    private function shouldAdjectivize(GranulometricFraction $fraction, float $percentage, string $class): bool
    {
        // Doar fracțiuni simple care depășesc pragul de adjectivizare
        // și au adjective propriu-zise (nu "cu...")
        if (!$fraction->isSimple() || $percentage < $this->config->getAdjectiveThreshold($class)) {
            return false;
        }

        $fractionConfig = config('granulometry.fractions.simple_fractions');
        $label = $fraction->getLabel();
        $adjective = $fractionConfig[$label]['adjective'][0] ?? '';

        // Verifică dacă adjectivul nu începe cu "cu" (deci e adjective propriu-zis)
        return !str_starts_with($adjective, 'cu ');
    }

    private function shouldMention(float $percentage, string $class): bool
    {
        return $percentage >= $this->config->getMentionThreshold($class);
    }

    private function formatMention(GranulometricFraction $fraction, float $percentage, string $class): string
    {
        $name = strtolower($fraction->getName());

        // Pentru coarse/very_coarse între mention și adjective threshold → "rar"
        if (
            in_array($class, ['coarse', 'very_coarse']) &&
            $percentage < $this->config->getAdjectiveThreshold($class)
        ) {
            return $this->config->getConnectors()['rare'] . ' ' . $name;
        }

        return $name;
    }

    private function buildFinalName(string $primary, array $adjectives, array $mentions): string
    {
        $finalName = $primary;

        if (!empty($adjectives)) {
            $finalName .= ' ' . implode(' ', $adjectives);
        }

        if (!empty($mentions)) {
            $finalName .= ' ' . $this->config->getConnectors()['with'] . ' ' .
                $this->formatMentionsList($mentions);
        }

        return $finalName;
    }

    private function getFractionAdjective(GranulometricFraction $fraction, GranulometricFraction $primaryFraction): string
    {
        $fractionConfig = config('granulometry.fractions.simple_fractions');

        $label = $fraction->getLabel();
        return $fractionConfig[$label]['adjective'][$primaryFraction->getGender()];
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
