<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Sample>
 */
class SampleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'borehole_id' => function () {
                return \App\Models\Borehole::factory()->create()->id;
            },
            'name' => $this->faker->randomElement(['P', 'p', 'N', 'n']) . $this->faker->numberBetween(1, 25),
            'depth' => $this->faker->randomFloat(2, 0.5, 50),
            'type' => $this->faker->randomElement(['disturbed', 'undisturbed']),
            'note' => $this->faker->sentence,
        ];
    }
}
