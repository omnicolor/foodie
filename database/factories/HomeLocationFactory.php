<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\HomeLocation;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use MatanYadaev\EloquentSpatial\Enums\Srid;
use MatanYadaev\EloquentSpatial\Objects\Point;

/**
 * @extends Factory<HomeLocation>
 */
class HomeLocationFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'channel_id' => 'C' . Str::random(19),
            'location' => new Point(
                $this->faker->randomFloat(5, -90, 90),
                $this->faker->randomFloat(5, -180, 180),
                Srid::WGS84->value,
            ),
            'set_by' => 'U' . Str::random(19),
        ];
    }
}
