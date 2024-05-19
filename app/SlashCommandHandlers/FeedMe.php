<?php

declare(strict_types=1);

namespace App\SlashCommandHandlers;

use App\Models\FoodTruck;
use App\Models\HomeLocation;
use Illuminate\Support\Str;
use Spatie\SlashCommand\Attachment;
use Spatie\SlashCommand\Handlers\BaseHandler;
use Spatie\SlashCommand\Request;
use Spatie\SlashCommand\Response;

use function explode;
use function is_numeric;

/**
 * @psalm-suppress UnusedClass
 */
class FeedMe extends BaseHandler
{
    public function canHandle(Request $request): bool
    {
        $command = explode(' ', $request->text);
        return 'feed-me' === $command[0];
    }

    public function handle(Request $request): Response
    {
        $parameters = explode(' ', $request->text);
        $distance = 10.0;
        if (2 === count($parameters)) {
            if (!is_numeric($parameters[1])) {
                $attachment = Attachment::create()
                    ->setColor('danger')
                    ->setText('Distance must be a number, like 0.5');
                return $this->respondToSlack('')->withAttachment($attachment);
            }
            $distance = (float)$parameters[1];
        }

        $home = HomeLocation::channel($request->channelId)->first();
        if (null === $home) {
            $attachment = Attachment::create()
                ->setColor('danger')
                ->setText(
                    'Before we can feed you, you need to set your home '
                        . 'location: `/foodie set-home 37.123456 -122.123456`',
                );
            return $this->respondToSlack('')->withAttachment($attachment);
        }

        // @phpstan-ignore-next-line
        $allTrucks = FoodTruck::query()
            ->withDistance('location', $home->location)
            ->get();
        $trucks = $allTrucks->filter(
            function (FoodTruck $truck) use ($distance): bool {
                // @phpstan-ignore-next-line
                $toTruck = round((float)$truck->distance * 0.00062137119, 2);
                return $toTruck < $distance;
            }
        );
        $truck = $trucks->random();
        // @phpstan-ignore-next-line
        $distanceToTruck = round((float)$truck->distance * 0.00062137119, 2);
        return $this->respondToSlack(sprintf(
            'Found %d trucks within %01.2f %s. Try out %s, which is %01.2f %s away.',
            count($trucks),
            $distance,
            Str::plural('mile', (int)$distance),
            Str::title($truck->name),
            $distanceToTruck,
            Str::plural('mile', (int)$distanceToTruck),
        ))
            ->displayResponseToEveryoneOnChannel();
    }
}
