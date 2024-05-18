<?php

declare(strict_types=1);

namespace App\SlashCommandHandlers;

use App\Models\HomeLocation;
use MatanYadaev\EloquentSpatial\Enums\Srid;
use MatanYadaev\EloquentSpatial\Objects\Point;
use Spatie\SlashCommand\Attachment;
use Spatie\SlashCommand\Handlers\BaseHandler;
use Spatie\SlashCommand\Request;
use Spatie\SlashCommand\Response;

use function abs;
use function count;
use function explode;
use function is_numeric;
use function sprintf;

/**
 * @psalm-suppress UnusedClass
 */
class SetHome extends BaseHandler
{
    public function canHandle(Request $request): bool
    {
        $command = explode(' ', $request->text);
        return 'set-home' === $command[0];
    }

    public function handle(Request $request): Response
    {
        $parameters = explode(' ', $request->text);
        if (3 !== count($parameters)) {
            $attachment = Attachment::create()
                ->setColor('danger')
                ->setText(
                    'Setting your home location requires latitude and '
                        . 'longitude: /foodie set-home 37.796215496594 '
                        . '-122.40375455825'
                );
            return $this->respondToSlack('')->withAttachment($attachment);
        }

        list(, $latitude, $longitude) = $parameters;
        if (!is_numeric($latitude) || !is_numeric($longitude)) {
            $attachment = Attachment::create()
                ->setColor('danger')
                ->setText(
                    'Latitude and longitude should be numbers, like '
                        . '37.796215496594 -122.40375455825'
                );
            return $this->respondToSlack('')->withAttachment($attachment);
        }

        $latitude = (float)$latitude;
        $longitude = (float)$longitude;

        if (abs($latitude) > 90) {
            $attachment = Attachment::create()
                ->setColor('danger')
                ->setText('Latitude must be between -90 and 90');
            return $this->respondToSlack('')->withAttachment($attachment);
        }
        if (abs($longitude) > 180) {
            $attachment = Attachment::create()
                ->setColor('danger')
                ->setText('Longitude must be between -180 and 180');
            return $this->respondToSlack('')->withAttachment($attachment);
        }

        $location = HomeLocation::channel($request->channelId)->first();
        if (null === $location) {
            // Setting a new home location.
            HomeLocation::create([
                'channel_id' => $request->channelId,
                'location' => new Point(
                    $latitude,
                    $longitude,
                    Srid::WGS84->value,
                ),
                'set_by' => $request->userId,
            ]);
            $attachment = Attachment::create()
                ->setColor('good')
                ->setText(sprintf(
                    '%s set this channel\'s location to %f %f',
                    $request->userName,
                    $latitude,
                    $longitude,
                ));
            return $this->respondToSlack('')
                ->withAttachment($attachment)
                ->displayResponseToEveryoneOnChannel();
        }

        $location->location = new Point(
            $latitude,
            $longitude,
            Srid::WGS84->value,
        );
        $location->set_by = $request->userId;
        $location->save();
        $attachment = Attachment::create()
            ->setColor('good')
            ->setText(sprintf(
                '%s updated this channel\'s location to %f %f',
                $request->userName,
                $latitude,
                $longitude,
            ));

        return $this->respondToSlack('')
            ->withAttachment($attachment)
            ->displayResponseToEveryoneOnChannel();
    }
}
