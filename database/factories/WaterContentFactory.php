<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\WaterContent>
 */
class WaterContentFactory extends Factory
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
            'water_content' => $this->faker->randomFloat(2, 5, 35),
        ];
    }
}
