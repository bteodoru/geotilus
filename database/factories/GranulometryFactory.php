<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Granulometry>
 */
class GranulometryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        $sand_ratio = $this->faker->randomFloat(2, 0, 100);
        $silt_ratio = $this->faker->randomFloat(2, 0, 100 - $sand_ratio);
        $clay_ratio = 100 - ($sand_ratio + $silt_ratio);

        return [
            'sample_id' => function () {
                return \App\Models\Sample::factory()->create()->id;
            },
            'sand' => round($sand_ratio, 2),  // Asigură precizia la 2 zecimale
            'silt' => round($silt_ratio, 2),  // Asigură precizia la 2 zecimale
            'clay' => round($clay_ratio, 2),  // Ajustăm pentru a completa până la 100
            'gravel' => $this->faker->randomFloat(2, 0, 50),
        ];
    }
}
