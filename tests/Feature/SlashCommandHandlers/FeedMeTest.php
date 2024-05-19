<?php

declare(strict_types=1);

namespace Tests\Feature\SlashCommandHandlers;

use App\SlashCommandHandlers\FeedMe;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\SlashCommand\Request;
use Tests\TestCase;

/**
 * @medium
 */
final class FeedMeTest extends TestCase
{
    use RefreshDatabase;

    public function testCanHandleOtherCommand(): void
    {
        $request = new Request();
        $request->text = 'set-home';
        $command = new FeedMe($request);
        self::assertFalse($command->canHandle($request));
    }

    public function testCanHandleFeedMe(): void
    {
        $request = new Request();
        $request->text = 'feed-me';
        $command = new FeedMe($request);
        self::assertTrue($command->canHandle($request));
    }

    public function testNonNumericDistance(): void
    {
        $request = new Request();
        $request->text = 'feed-me a';
        $command = new FeedMe($request);
        $response = $command->handle($request);
        $attachment = $this->getAttachmentFromResponse($response);
        self::assertSame(
            'Distance must be a number, like 0.5',
            $attachment['text'],
        );
    }

    public function testNoHomeLocation(): void
    {
        $request = new Request();
        $request->text = 'feed-me';
        $request->channelId = 'A123';
        $command = new FeedMe($request);
        $response = $command->handle($request);
        $attachment = $this->getAttachmentFromResponse($response);
        self::assertSame(
            'Before we can feed you, you need to set your home location: '
                . '`/foodie set-home 37.123456 -122.123456`',
            $attachment['text'],
        );
    }
}
