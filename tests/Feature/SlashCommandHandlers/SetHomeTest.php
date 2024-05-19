<?php

declare(strict_types=1);

namespace Tests\Feature\SlashCommandHandlers;

use App\Models\HomeLocation;
use App\SlashCommandHandlers\SetHome;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use MatanYadaev\EloquentSpatial\Enums\Srid;
use MatanYadaev\EloquentSpatial\Objects\Point;
use Spatie\SlashCommand\Request;
use Tests\TestCase;

/**
 * @medium
 */
final class SetHomeTest extends TestCase
{
    use RefreshDatabase;

    protected function getUniqueChannelId(): string
    {
        do {
            $channelId = 'C' . Str::random(19);
            // @phpstan-ignore-next-line
        } while (HomeLocation::where('channel_id', $channelId)->exists());
        return $channelId;
    }

    public function testCanHandleOtherCommand(): void
    {
        $request = new Request();
        $request->text = 'help';
        $command = new SetHome($request);
        self::assertFalse($command->canHandle($request));
    }

    public function testCanHandleSetHome(): void
    {
        $request = new Request();
        $request->text = 'set-home';
        $command = new SetHome($request);
        self::assertTrue($command->canHandle($request));
    }

    public function testHandleWithIncorrectParameterCount(): void
    {
        $request = new Request();
        $request->text = 'set-home';
        $command = new SetHome($request);
        $response = $command->handle($request);
        $attachment = $this->getAttachmentFromResponse($response);
        self::assertSame(
            'Setting your home location requires latitude and longitude: '
                . '/foodie set-home 37.796215496594 -122.40375455825',
            $attachment['text'],
        );
    }

    public function testHandleWithInvalidTypeLatitude(): void
    {
        $request = new Request();
        $request->text = 'set-home a -122';
        $command = new SetHome($request);
        $response = $command->handle($request);
        $attachment = $this->getAttachmentFromResponse($response);
        self::assertSame(
            'Latitude and longitude should be numbers, like 37.796215496594 '
                . '-122.40375455825',
            $attachment['text'],
        );
    }

    public function testHandleWithInvalidTypeLongitude(): void
    {
        $request = new Request();
        $request->text = 'set-home 38 b';
        $command = new SetHome($request);
        $response = $command->handle($request);
        $attachment = $this->getAttachmentFromResponse($response);
        self::assertSame(
            'Latitude and longitude should be numbers, like 37.796215496594 '
                . '-122.40375455825',
            $attachment['text'],
        );
    }

    public function testHandleWithLatitudeOutOfRange(): void
    {
        $request = new Request();
        $request->text = 'set-home -91 -122';
        $command = new SetHome($request);
        $response = $command->handle($request);
        $attachment = $this->getAttachmentFromResponse($response);
        self::assertSame(
            'Latitude must be between -90 and 90',
            $attachment['text'],
        );
    }

    public function testHandleWithLongitudeOutOfRange(): void
    {
        $request = new Request();
        $request->text = 'set-home 38 -181';
        $command = new SetHome($request);
        $response = $command->handle($request);
        $attachment = $this->getAttachmentFromResponse($response);
        self::assertSame(
            'Longitude must be between -180 and 180',
            $attachment['text'],
        );
    }

    public function testHandleSettingNewChannel(): void
    {
        $channelId = $this->getUniqueChannelId();

        $request = new Request();
        $request->channelId = $channelId;
        $request->text = 'set-home 38 -122';
        $request->userName = 'Test User';
        $request->userId = 'U' . Str::random(19);

        $command = new SetHome($request);
        $response = $command->handle($request);
        $attachment = $this->getAttachmentFromResponse($response);
        self::assertSame(
            'Test User set this channel\'s location to 38.000000 -122.000000',
            $attachment['text'],
        );

        self::assertDatabaseHas(
            'home_locations',
            [
                'channel_id' => $channelId,
                'set_by' => $request->userId,
            ],
        );
    }

    public function testHandleUpdatingChannel(): void
    {
        $originalUser = 'U123';
        $location = HomeLocation::create([
            'channel_id' => $this->getUniqueChannelId(),
            'location' => new Point(0, 0, Srid::WGS84->value),
            'set_by' => $originalUser,
        ]);

        $request = new Request();
        $request->channelId = $location->channel_id;
        $request->text = 'set-home 38 -122';
        $request->userName = 'Food User';
        $request->userId = 'U' . Str::random(19);

        $command = new SetHome($request);
        $response = $command->handle($request);
        $attachment = $this->getAttachmentFromResponse($response);
        self::assertSame(
            'Food User updated this channel\'s location to 38.000000 -122.000000',
            $attachment['text'],
        );

        $location->refresh();
        self::assertNotSame($originalUser, $location->set_by);
        self::assertSame(38.0, $location->location->latitude);
    }
}
