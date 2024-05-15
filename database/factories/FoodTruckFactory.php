<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\FoodTruck;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FoodTruck>
 */
class FoodTruckFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'cuisine' => $this->faker->catchPhrase(),
            'latitude' => $this->faker->latitude(37, 38),
            'longitude' => $this->faker->longitude(-123, -122),
            'name' => $this->faker->company(),
            'truck_id' => (string)$this->faker->randomNumber(7, true),
        ];
    }
}
