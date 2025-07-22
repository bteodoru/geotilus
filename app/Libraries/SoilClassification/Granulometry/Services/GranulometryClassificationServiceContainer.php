<?php

namespace App\Libraries\SoilClassification\Granulometry\Services;

use App\Libraries\SoilClassification\Granulometry\TernaryDiagramRepository;
use App\Libraries\SoilClassification\Repositories\ClassificationSystemRepository;
use App\Libraries\SoilClassification\Services\GranulometryService;
use App\Services\GeometryService;

/**
 * Service container specializat pentru operațiunile de clasificare granulometrică
 * Grupează toate serviciile necesare pentru procesul de clasificare
 * într-un container coerent și ușor de gestionat
 */
class GranulometryClassificationServiceContainer
{
    public function __construct(
        private GranulometryService $granulometryService,
        private TernaryDiagramService $ternaryDiagramService,
        private GeometryService $geometryService,
        private SoilNameService $soilNameService,
        private TernaryDiagramRepository $diagramRepository,
        private ClassificationSystemRepository $systemRepository
    ) {}


    public function granulometry(): GranulometryService
    {
        return $this->granulometryService;
    }

    public function ternaryDiagram(): TernaryDiagramService
    {
        return $this->ternaryDiagramService;
    }

    public function geometry(): GeometryService
    {
        return $this->geometryService;
    }

    public function soilName(): SoilNameService
    {
        return $this->soilNameService;
    }

    public function diagramRepository(): TernaryDiagramRepository
    {
        return $this->diagramRepository;
    }

    public function systemRepository(): ClassificationSystemRepository
    {
        return $this->systemRepository;
    }
    /**
     * Factory method pentru crearea container-ului din Laravel's DI container
     * Encapsulează complexitatea rezolvării dependențelor
     */
    public static function create(): self
    {
        return new self(
            app(GranulometryService::class),
            app(TernaryDiagramService::class),
            app(GeometryService::class),
            app(SoilNameService::class),
            app(TernaryDiagramRepository::class),
            app(ClassificationSystemRepository::class)
        );
    }

    /**
     * Metodă utilitară pentru debugging - arată ce servicii conține container-ul
     */
    public function getServiceSummary(): array
    {
        return [
            'data_processing' => [GranulometryService::class, TernaryDiagramService::class],
            'geometric_operations' => [GeometryService::class],
            'naming_logic' => [SoilNameService::class],
            'data_access' => [TernaryDiagramRepository::class, ClassificationSystemRepository::class]
        ];
    }
}
