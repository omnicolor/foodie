<?php

declare(strict_types=1);

namespace Tests\Feature\Models;

use App\Models\HomeLocation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @small
 */
class HomeLocationTest extends TestCase
{
    use RefreshDatabase;

    public function testScopeForChannel(): void
    {
        $location = HomeLocation::factory()->create();
        // @phpstan-ignore-next-line
        self::assertTrue(HomeLocation::channel($location->channel_id)->exists());
    }
}
