<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use MatanYadaev\EloquentSpatial\Objects\Point;
use MatanYadaev\EloquentSpatial\Traits\HasSpatial;

class HomeLocation extends Model
{
    use HasFactory;
    use HasSpatial;

    protected $casts = [
        'location' => Point::class,
    ];

    protected $fillable = [
        'channel_id',
        'location',
        'set_by',
    ];

    /**
     * @param Builder<HomeLocation> $query
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function scopeChannel(Builder $query, string $channelId): void
    {
        $query->where('channel_id', $channelId);
    }
}
