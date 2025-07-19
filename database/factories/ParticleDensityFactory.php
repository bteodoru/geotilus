<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ParticleDensity>
 */
class ParticleDensityFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'sample_id' => function () {
                return \App\Models\Sample::factory()->create()->id;
            },
            'particle_density' => $this->faker->randomFloat(2, 2.65, 2.72),
        ];
    }
}
