<?php

namespace App\Libraries\DTOs;

class GranulometricFraction
{
    private string $name;
    private ?string $symbol;
    private ?string $label;
    // private float $percentage;
    private array $components;
    private ?string $source;
    private ?string $class;
    // private ?array $metadata; code


    public function __construct(
        string $name,
        ?string $symbol = null,
        // float $percentage,
        array $components = [],
        ?string $source = null,
        ?string $class = null,
        ?string $label = null,
        // ?array $metadata = []
    ) {
        $this->name = $name;
        $this->symbol = $symbol;
        // $this->percentage = $this->calculatePercentage($components);
        $this->components =  array_filter($components, function ($element) {
            return $element > 0;
        });
        $this->source = $source;
        $this->class = $class ?? $this->resolveClass();
        $this->label = $label;
        // $this->metadata = $metadata;
    }

    private function getFractions(): array
    {
        return config('granulometry.fractions');
    }

    public function getGender(): int
    {
        return $this->getFractions()['simple_fractions'][$this->label]['gender'] ?? 1;
    }

    // Getters
    public function getName(): string
    {
        return $this->name;
    }
    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function getSymbol(): string
    {
        return $this->symbol;
    }

    public function getPercentage(): float
    {
        return $this->calculatePercentage();
    }

    private function calculatePercentage(): float
    {
        if (empty($this->components)) {
            return 0.0;
        }

        $total = array_sum($this->components);
        return $total > 0 ? round($total, 2) : 0.0;
    }

    public function getComponents(): array
    {
        return $this->components;
    }

    public function getSource(): ?string
    {
        return $this->source;
    }

    // public function getMetadata(): array
    // {
    //     return $this->metadata;
    // }

    // Metode helper
    public function isComposite(): bool
    {
        return count($this->components) > 1;
    }

    public function isSimple(): bool
    {
        if ($this->source === 'casagrande_chart') {
            return true; // Casagrande chart always returns a simple fraction
        }
        return count($this->components) === 1;
    }

    public function hasComponent(string $component): bool
    {
        return isset($this->components[$component]);
    }

    public function getComponentPercentage(string $component): ?float
    {
        return $this->components[$component] ?? null;
    }

    public function getPrimaryComponent(): ?string
    {
        if ($this->isSimple()) {
            return array_key_first($this->components);
        }
        return null;
    }

    public function getDominantComponent(): ?string
    {
        if (empty($this->components)) {
            return null;
        }

        $maxValue = max($this->components);
        return array_search($maxValue, $this->components);
    }

    private function resolveClass(): string
    {
        return $this->getFractions()['simple_fractions'][$this->getDominantComponent()]['class'] ?? 'unknown';
    }

    public function getClass(): string
    {
        return $this->class;
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'symbol' => $this->symbol,
            'percentage' => $this->getPercentage(),
            'components' => $this->components,
            'is_composite' => $this->isComposite(),
            'source' => $this->source,
            'is_simple' => $this->isSimple(),
            'primary_component' => $this->getPrimaryComponent(),
            'dominant_component' => $this->getDominantComponent(),
            'class' => $this->getClass(),
            // 'metadata' => $this->metadata
        ];
    }
}
