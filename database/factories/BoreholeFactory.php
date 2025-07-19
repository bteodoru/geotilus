<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Borehole>
 */
class BoreholeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'project_id' => function () {
                return \App\Models\Project::factory()->create()->id;
            },
            'name' => $this->faker->randomElement(['F', 'f', 'S']) . $this->faker->numberBetween(1, 500),
            'depth' => $this->faker->randomFloat(2, 5, 100),
            'diameter' => $this->faker->randomFloat(2, 0.1, 1),
            'hydrostatic_level' => $this->faker->randomFloat(2, 0, 10),
            'elevation' => $this->faker->randomFloat(2, 100, 2000),
            'latitude' => $this->faker->latitude(44, 48),
            'longitude' => $this->faker->longitude(22, 28),
            'equipment' => $this->faker->text(10),
            'note' => $this->faker->sentence(5),
            'start_date' => $this->faker->date(),
            'end_date' => $this->faker->date(),
        ];
    }
}
