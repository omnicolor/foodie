<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\FoodTruck;
use Illuminate\Console\Command;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

use function array_filter;
use function array_walk;
use function in_array;

/**
 * @psalm-suppress UnusedClass
 */
class ImportFoodTruckData extends Command
{
    /**
     * @var string
     */
    protected $signature = 'foodie:import-food-truck-data {--force-update}';

    /**
     * @var string
     */
    protected $description = 'Imports food truck data from sfgov.org
        {--force-update : (Re)load data even if it is up to date}';

    /**
     * Given the columns definition from the server's metadata, determine what
     * position the fields we care about are in.
     * @param array<int, array<string, mixed>> $columns
     * @return array<string, int>
     */
    protected function loadFieldMap(array $columns): array
    {
        $desiredColumns = [
            'applicant',
            'facilitytype', // Truck or Push Cart
            'fooditems',
            'latitude',
            'longitude',
            'objectid',
            'status', // approved, expired, requested, suspend, issued
        ];
        $map = [];
        foreach ($columns as $position => $column) {
            if (!in_array($column['fieldName'], $desiredColumns, true)) {
                continue;
            }

            $map[$column['fieldName']] = $position;
        }
        return $map;
    }

    /**
     * @param array<int, array<int, array<int, bool|null|string>|int|null|string>> $data
     * @param array<string, int> $fieldMap
     * @return array<int, array<string, float|string>>
     */
    protected function filterData(array $data, array $fieldMap): array
    {
        $type = $fieldMap['facilitytype'];
        $status = $fieldMap['status'];

        $approvedTrucks = array_filter(
            $data,
            function (array $vendor) use ($type, $status): bool {
                if (FoodTruck::TYPE_TRUCK !== $vendor[$type]) {
                    // Ignore Push Carts or any other non-food truck options
                    // that may be added in the future.
                    return false;
                }

                if (FoodTruck::STATUS_APPROVED !== $vendor[$status]) {
                    // Ignore any food trucks that haven't been approved for
                    // a permit to operate.
                    return false;
                }

                return true;
            }
        );

        array_walk(
            $approvedTrucks,
            function (array &$truck) use ($fieldMap): void {
                $truck = [
                    'cuisine' => $truck[$fieldMap['fooditems']],
                    'name' => $truck[$fieldMap['applicant']],
                    'latitude' => $truck[$fieldMap['latitude']],
                    'longitude' => $truck[$fieldMap['longitude']],
                    'truck_id' => $truck[$fieldMap['objectid']],
                ];
            }
        );

        // PHPstan can't tell that the above array_walk call limited the
        // possible return values.
        // @phpstan-ignore-next-line
        return array_values($approvedTrucks);
    }

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $headers = [];
        $etag = Cache::get('sfgov-etag');
        if (!$this->option('force-update') && null !== $etag) {
            $headers['If-None-Match'] = $etag;
        }

        $response = Http::withHeaders($headers)
            ->get(config('services.truck_provider.uri'));

        if (Response::HTTP_NOT_MODIFIED === $response->status()) {
            $this->info(sprintf(
                'Server\'s data has not changed, kept %d trucks',
                number_format(FoodTruck::count()),
            ));
            return;
        }

        if (null !== $response->header('Etag')) {
            Cache::put('sfgov-etag', $response->header('Etag'));
        }

        $fieldMap = $this->loadFieldMap($response->json('meta.view.columns'));
        $trucks = $this->filterData($response->json('data'), $fieldMap);
        FoodTruck::truncate();
        FoodTruck::insert($trucks);
        $this->info(sprintf('Loaded %d food trucks', number_format(count($trucks))));
    }
}
