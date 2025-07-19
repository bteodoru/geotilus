<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\BulkDensity>
 */
class BulkDensityFactory extends Factory
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
            'bulk_density' => $this->faker->randomFloat(2, 1.5, 2.1),
        ];
    }
}
