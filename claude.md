# Aplicație Web pentru Proiectare și Analiză Geotehnică

## Prezentare Generală

Aplicație web modulară pentru clasificarea și analiza pământurilor în domeniul geotehnic, dezvoltată în Laravel cu Jetstream, Inertia.js și Tailwind CSS.

## Stack Tehnologic

- **Backend**: Laravel 10/11
- **Frontend**: Vue 3 + Inertia.js
- **CSS**: Tailwind CSS
- **Auth/Teams**: Laravel Jetstream
- **Database**: MySQL/PostgreSQL
- **Testing**: PHPUnit + Pest

## Arhitectura Sistemului

### Module Principale

1. **Modul Clasificare Pământuri** (în dezvoltare)

   - Clasificare granulometrică
   - Clasificare după plasticitate
   - Suport multi-standard (STAS, SR EN ISO, NP, USCS, AASHTO)

2. **Module Planificate**
   - Fișe de foraj
   - Determinarea valorilor caracteristice
   - Rapoarte geotehnice
   - Managementul probelor

## Structura Bazei de Date

### Entități Principale

```
Project
  └── Borehole (foraj)
       └── Sample (probă)
            ├── Granulometry (analiză granulometrică)
            ├── AtterbergLimit (limite Atterberg/plasticitate)
            ├── WaterContent (umiditate)
            ├── BulkDensity (densitate în masă)
            ├── ParticleDensity (densitate particule)
            └── SoilType (tip de pământ - rezultat clasificare)
```

### Model Sample - Entitatea Centrală

```php
class Sample extends Model {
    // Relații
    - belongsTo: Borehole
    - hasOne: Granulometry
    - hasOne: AtterbergLimit (plasticity)
    - hasOne: WaterContent
    - hasOne: BulkDensity
    - hasOne: ParticleDensity
    - hasOne: SoilType
}
```

## Arhitectura Modulului de Clasificare

### Principii de Design

1. **Separarea Responsabilităților**

   - Clasificatori: determină doar fracțiunile (fără naming)
   - Naming Service: construiește denumirea pământului
   - Repository: accesează datele și configurările

2. **Flexibilitate**
   - Suport pentru multiple sisteme de clasificare
   - Configurare per sistem în `config/soil_classification/systems/`
   - Opțiuni de naming configurabile de utilizator

### Structura Directoarelor

```
app/Libraries/SoilClassification/
├── Classifiers/
│   ├── BaseClassifier.php
│   ├── STAS_1243_1988_Classifier.php
│   ├── SR_EN_ISO_14688_2018_Classifier.php
│   └── USCS_Classifier.php
├── Services/
│   ├── GranulometryAnalysisService.php
│   ├── TernaryDiagramService.php
│   ├── SoilNamingService.php
│   └── CasagrandeChartService.php
├── Repositories/
│   ├── ClassificationSystemRepository.php
│   └── TernaryDiagramRepository.php
├── DTOs/
│   ├── ClassificationResult.php
│   └── Fraction.php
└── Support/
    ├── GeometryService.php (point-in-polygon)
    └── NormalizationService.php
```

## Logica de Clasificare

### Flux Principal

```
1. INPUT: Sample (probă cu date de laborator)

2. PROCESARE:
   a. Dacă sistemul folosește metodă grafică (diagramă ternară/Casagrande):
      - Normalizare fracțiuni pentru diagramă (dacă suma ≠ 100%)
      - Ray-casting pentru determinare zonă în diagramă
      - Rezultat: denumire compusă (ex: "nisip prăfos")

   b. Adăugare fracțiuni simple nefolosite în diagramă

   c. Sortare toate fracțiunile după procent (descrescător)

3. OUTPUT: ClassificationResult
   - fractions[] - array sortat cu toate fracțiunile
   - metadata - date despre procesare
```

### Clasificare Pământuri Compuse

Pentru pământuri cu fracțiuni fine + grosiere:

```
Exemplu: clay=10%, silt=20%, sand=30%, gravel=40%

1. Calculează suma fracțiunilor fine: 60%
2. Fine > 50% → Pământ fin dominant
3. Normalizare pentru diagramă: clay=16.7%, silt=33.3%, sand=50%
4. Diagramă ternară → "nisip"
5. Rezultat:
   - Primary: {soil: "nisip", percentage: 60, type: "composite"}
   - Secondary: {soil: "pietriș", percentage: 40, type: "simple"}
```

### Reguli de Dominanță

- **Fracțiuni > 50%**: Devin dominante în denumire
- **Fracțiuni fine totale > 50%**: Denumirea din diagramă e principală
- **Fracțiuni grosiere > 50%**: Fracțiunea grosieră dominantă e principală

## Configurare Sisteme de Clasificare

### Structura Fișier Config

```php
// config/soil_classification/systems/stas_1243_1988.php
return [
    'system_info' => [
        'code' => 'stas_1243_1988',
        'name' => 'STAS 1243',
        'version' => '1988',
        'country' => 'RO',
    ],

    'supported_classification_criteria' => [
        'granulometry' => [
            'applicable_granulometric_classes' => ['fine', 'coarse', 'very_coarse'],
            'graphical_method' => 'ternary_diagram', // sau 'casagrande_chart', 'none'
        ],
        'plasticity' => [
            'graphical_method' => 'none',
        ],
    ],

    'fractions' => [
        'ternary_diagram' => ['clay', 'silt', 'sand'], // fracțiuni folosite în diagramă
        'consideration_threshold' => 5.0,  // prag minim pentru considerare
        'adjective_threshold' => 20.0,     // prag pentru adjectivizare
    ],
];
```

### Metode Grafice Suportate

- `ternary_diagram` - Diagrama ternară pentru clasificare (STAS, SR EN ISO)
- `casagrande_chart` - Graficul Casagrande pentru plasticitate (USCS, AASHTO)
- `none` - Fără metodă grafică

## Serviciul de Naming

### Opțiuni de Configurare

```php
class NamingOptions {
    float $considerationThreshold = 5.0;   // Sub acest prag, fracțiunea e ignorată
    float $adjectiveThreshold = 20.0;      // Sub acest prag, devine adjectiv ("argilos", "prăfos")
    float $dominanceThreshold = 50.0;      // Peste acest prag, inversează ordinea
    string $conjunction = 'cu';            // "cu" sau "și"
    bool $includePercentages = false;      // Afișează procentele
    string $language = 'ro';               // Limba pentru denumire
}
```

### Reguli de Construcție Denumire

1. **Pământuri simple**: Fracțiunea dominantă + secundare cu "cu"
2. **Pământuri compuse**: Denumire din diagramă + fracțiuni grosiere
3. **Adjectivizare**: Pentru fracțiuni simple sub prag → "argilos", "prăfos", "nisipos"
4. **Inversare**: Când secundara > 50% → devine principală

## Algoritmi Importanți

### Point-in-Polygon (Ray-Casting)

Folosit pentru determinarea zonei în diagrama ternară:

```php
// GeometryService::pointInPolygon()
// Determină dacă un punct se află într-un poligon
// Folosit pentru clasificare în diagrama ternară
```

### Normalizare Fracțiuni

```php
// Pentru diagramă ternară când suma ≠ 100%
$factor = 100 / ($clay + $silt + $sand);
$normalized = [
    'clay' => $clay * $factor,
    'silt' => $silt * $factor,
    'sand' => $sand * $factor
];
```

## Convenții de Cod

### Naming

- **Clasificatori**: `{SystemCode}Classifier.php`
- **Servicii**: `{Domain}Service.php`
- **DTOs**: Simple, fără interfețe complexe
- **Config**: `config/soil_classification/systems/{system_code}.php`

### Principii

- **KISS**: Evitați over-engineering (fără interfețe nejustificate)
- **DRY**: Logica comună în BaseClassifier
- **SRP**: Fiecare clasă cu o singură responsabilitate
- **Testabil**: Servicii injectate, nu hardcodate

## API Public Principal

```php
// Clasificare simplă
$result = $classifier->classify($sample);

// Rezultat
$result->fractions;     // Array sortat de fracțiuni
$result->metadata;      // Date despre procesare

// Construire nume
$name = $namingService->buildName($result, $options);
```

## Testing

### Cazuri de Test Importante

1. **Pământuri simple** (100% fracțiuni fine)
2. **Pământuri compuse** (fine + grosiere)
3. **Normalizare** (când suma ≠ 100%)
4. **Cazuri limită** (fracțiuni egale, valori la praguri)
5. **Sisteme diferite** (STAS vs SR EN ISO vs USCS)

## Probleme Cunoscute / TODO

1. **Clasificator SR_EN_ISO_14688_2018**: Metodă `classify()` incompletă
2. **Casagrande Chart Service**: De implementat pentru USCS
3. **Caching**: De adăugat pentru diagrame și clasificări
4. **UI**: De dezvoltat interfața pentru configurare opțiuni naming
5. **Validări**: De îmbunătățit validările pentru date incomplete

## Note pentru Dezvoltare

- Clasificatorii procesează doar `Sample`, nu `Granulometry` direct
- Rezultatele din diagramă pot fi simple ("argilă") sau compuse ("argilă nisipoasă")
- Adjectivizarea se face doar pentru denumiri simple sub prag
- Fracțiunile sunt întotdeauna sortate descrescător în rezultat
- Metadata conține informații despre normalizare și metodă folosită

## Resurse și Standarde

- **STAS 1243/1988**: Standard românesc (abrogat dar încă folosit)
- **SR EN ISO 14688**: Standard european
- **NP 074/2022**: Normativ românesc recent
