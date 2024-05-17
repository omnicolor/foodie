<?php

declare(strict_types=1);

namespace Tests\Feature\Models;

use App\Models\FoodTruck;
use MatanYadaev\EloquentSpatial\Enums\Srid;
use MatanYadaev\EloquentSpatial\Objects\Point;
use Tests\TestCase;

/**
 * @small
 */
final class FoodTruckTest extends TestCase
{
    public function testLocation(): void
    {
        $truck = new FoodTruck([
            'location' => new Point(
                37.796215496594,
                -122.40375455825,
                Srid::WGS84->value,
            ),
        ]);

        $location = $truck->location;
        self::assertInstanceOf(Point::class, $location);
        self::assertSame(37.796215496594, $location->latitude);
        self::assertSame(-122.40375455825, $location->longitude);
    }

    public function testLocationWithDistance(): void
    {
        FoodTruck::factory()->create([
            'location' => new Point(
                37.796215496594,
                -122.40375455825,
                Srid::WGS84->value,
            ),
            'truck_id' => '999999',
        ]);

        $origin = new Point(0, 0, Srid::WGS84->value);
        $home = new Point(37.7879974, -122.409801, Srid::WGS84->value);
        // @phpstan-ignore-next-line
        $truck = FoodTruck::where('truck_id', '999999')
            ->withDistance('location', $origin, 'distance_from_origin')
            ->withDistance('location', $home, 'distance_from_home')
            ->first();
        // The coffee shop is really far (in meters) from the origin.
        // @phpstan-ignore-next-line
        self::assertSame(12_803_412, (int)$truck->distance_from_origin);
        // Much closer to home.
        // @phpstan-ignore-next-line
        self::assertSame(1056, (int)$truck->distance_from_home);
    }
}
