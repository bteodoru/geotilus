<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\AtterbergLimit>
 */
class AtterbergLimitFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        $shrinkage_limit = $this->faker->randomFloat(2, 5, 15);
        $plastic_limit = $this->faker->randomFloat(2, $shrinkage_limit + 1, 40);
        $liquid_limit = $this->faker->randomFloat(2, $plastic_limit + 1, 60);
        return [
            'sample_id' => function () {
                return \App\Models\Sample::factory()->create()->id;
            },
            'shrinkage_limit' => $shrinkage_limit,
            'plastic_limit' => $plastic_limit,
            'liquid_limit' => $liquid_limit,
        ];
    }
}
