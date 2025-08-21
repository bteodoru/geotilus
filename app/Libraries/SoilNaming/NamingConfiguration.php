<?php

namespace App\Libraries\SoilNaming;

class NamingConfiguration
{
    public function __construct(
        private array $mentionThreshold = ['fine' => 5, 'coarse' => 15, 'very_coarse' => 15],
        private array $adjectiveThreshold = ['fine' => 15, 'coarse' => 30.0, 'very_coarse' => 30.0],
        private array $connectors = [
            'with' => 'cu',
            'and' => 'și',
            'rare' => 'rar',
        ]
    ) {}

    public function getMentionThreshold($class): float
    {
        return $this->mentionThreshold[$class] ?? 0.0;
    }
    public function getAdjectiveThreshold($class): float
    {
        return $this->adjectiveThreshold[$class] ?? 0.0;
    }
    public function getConnectors(): array
    {
        return $this->connectors;
    }
}
