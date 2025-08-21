<?php

namespace App\Libraries\SoilClassification\Granulometry;

use App\Models\Granulometry;

/**
 * Rezultatul complet al procesului de clasificare granulometrică
 * 
 * Această clasă servește ca interfață standardizată între toate sistemele de clasificare
 * și modulul SoilNaming. Ea transportă nu doar rezultatul tehnic al clasificării,
 * ci și toate informațiile contextuale necesare pentru generarea denumirii finale.
 * 
 * Principii de design:
 * - Separarea clară între clasificarea tehnică și denumirea finală
 * - Transparența în privința fracțiunilor utilizate în clasificare
 * - Flexibilitatea pentru sisteme de clasificare foarte diferite
 * - Completitudinea informațiilor pentru procesarea avansată
 */
class GranulometryClassificationResult
{
    // private string $primaryClassification;
    private string $classificationSystem;
    private object $granulometry;
    private object $plasticity;
    private array $gradingParameters;
    private array $metadata;
    private array $fractions;
    // private array $primaryFraction;
    // private array $secondaryFractions;
    // private array $tertiaryFractions;

    public function __construct(
        // string $primaryClassification,
        string $classificationSystem,
        object $granulometry,
        object $plasticity,
        array $gradingParameters = [],
        array $metadata = [],
        array $fractions = [],
        // array $primaryFraction = [],
        // array $secondaryFractions = [],
        // array $tertiaryFractions = []
    ) {
        // $this->primaryClassification = $primaryClassification;
        $this->classificationSystem = $classificationSystem;
        $this->granulometry = $granulometry;
        $this->plasticity = $plasticity;
        $this->gradingParameters = $gradingParameters;
        $this->metadata = $metadata;
        $this->fractions = $fractions;
        // $this->primaryFraction = $primaryFraction;
        // $this->secondaryFractions = $secondaryFractions;
        // $this->tertiaryFractions = $tertiaryFractions;
    }

    public function getFractions(): array
    {
        return $this->fractions;
    }

    /**
     * Returnează rezultatul tehnic brut al clasificării
     * 
     * Aceasta este denumirea pe care sistemul de clasificare a generat-o
     * conform regulilor sale specifice, fără nicio îmbogățire sau interpretare.
     * Exemplu: "nisip prăfos", "CL", "argilă cu plasticitate mare"
     */
    public function getPrimaryClassification(): string
    {
        return $this->primaryClassification;
    }

    /**
     * Returnează codul sistemului de clasificare utilizat
     * 
     * Această informație permite SoilNaming să înțeleagă contextul rezultatului
     * și să aplice eventuale reguli specifice sistemului utilizat.
     * Exemplu: "STAS_1243_1988", "SR_EN_ISO_14688_2018"
     */
    public function getClassificationSystem(): string
    {
        return $this->classificationSystem;
    }

    /**
     * Returnează compoziția granulometrică completă a probei
     * 
     * Aceasta include procentele pentru toate fracțiunile prezente,
     * constituind "materia primă" pentru SoilNaming în construirea
     * denumirii secundare și terțiare.
     */
    public function getGranulometry(): Granulometry
    {
        return $this->granulometry;
    }

    /**
     * Returnează o fracțiune granulometrică specifică
     * 
     * Metodă utilitară pentru accesarea rapidă a procentului unei fracțiuni
     * particulare fără a fi nevoie să navighezi întreaga structură.
     */
    public function getFraction(string $fractionName): float
    {
        return $this->granulometry[$fractionName] ?? 0.0;
    }

    /**
     * Returnează caracteristicile de plasticitate ale pământului
     * 
     * Aceste informații sunt esențiale pentru pământurile fine și pot
     * influența denumirea finală în implementări viitoare ale SoilNaming.
     */
    public function getPlasticity(): array
    {
        return $this->plasticity;
    }

    /**
     * Returnează parametrii de gradație pentru pământurile granulare
     * 
     * Coeficienții de uniformitate și curbură, împreună cu clasificarea
     * gradației, oferă informații despre comportamentul granular care
     * poate rafina denumirea finală.
     */
    public function getGradingParameters(): array
    {
        return $this->gradingParameters;
    }

    /**
     * Returnează fracțiunile care au fost utilizate în procesul de clasificare
     * 
     * Aceasta este informația critică care permite SoilNaming să excludă
     * fracțiunile "consumate" din procesul de denominare secundară,
     * evitând redundanțele de tipul "nisip prăfos cu nisip".
     */
    public function getUsedFractions(): array
    {
        return $this->metadata['used_fractions'] ?? [];
    }

    /**
     * Returnează metoda de analiză utilizată în clasificare
     * 
     * Aceasta poate fi "ternary_diagram", "casagrande_chart", "grading_analysis"
     * sau alte tipuri de analiză care să ajute SoilNaming să înțeleagă natura rezultatului.
     */
    public function getAnalysisMethod(): string
    {
        return $this->metadata['analysis_method'] ?? 'unknown';
    }

    /**
     * Verifică dacă în clasificare a fost aplicată normalizare
     * 
     * Această informație poate fi relevantă pentru înțelegerea modului în care
     * au fost procesate fracțiunile și pentru interpretarea corectă a rezultatului.
     */
    public function wasNormalizationApplied(): bool
    {
        return $this->metadata['normalization_applied'] ?? false;
    }

    /**
     * Returnează factorul de normalizare utilizat
     * 
     * Când normalizarea a fost aplicată, acest factor oferă informații despre
     * amploarea ajustărilor făcute în procesul de clasificare.
     */
    public function getNormalizationFactor(): float
    {
        return $this->metadata['normalization_factor'] ?? 1.0;
    }

    /**
     * Returnează toate metadata despre procesul de clasificare
     * 
     * Această informație completă permite analize avansate și debugging
     * al procesului de clasificare pentru dezvoltatori și experți.
     */
    public function getClassificationMetadata(): array
    {
        return $this->metadata;
    }

    /**
     * Returnează reprezentarea completă ca array
     * 
     * Această metodă creează structura JSON pe care ai specificat-o,
     * fiind ideală pentru serializare, debugging sau integrare cu alte sisteme.
     */
    public function toArray(): array
    {
        return [
            'primary_classification' => $this->primaryClassification,
            'classification_system' => $this->classificationSystem,
            'granulometry' => $this->granulometry,
            'plasticity' => $this->plasticity,
            'grading_parameters' => $this->gradingParameters,
            'classification_metadata' => $this->metadata
        ];
    }



    /**
     * Metodă pentru compatibilitatea cu codul existent
     * 
     * Menține compatibilitatea cu interfața anterioară pentru a permite
     * tranziția graduală către noua arhitectură fără a rupe codul existent.
     * 
     * @deprecated Folosiți getPrimaryClassification() în schimb
     */
    public function getSoilType(): string
    {
        return $this->primaryClassification;
    }
}
