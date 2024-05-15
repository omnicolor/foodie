<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FoodTruck extends Model
{
    use HasFactory;

    public const STATUS_APPROVED = 'APPROVED';
    public const STATUS_EXPIRED = 'EXPIRED';
    public const STATUS_ISSUED = 'ISSUED';
    public const STATUS_REQUESTED = 'REQUESTED';
    public const STATUS_SUSPENDED = 'SUSPEND';

    public const TYPE_PUSH_CART = 'Push Cart';
    public const TYPE_TRUCK = 'Truck';

    protected $fillable = [
        'cuisine',
        'latitude',
        'longitude',
        'name',
        'truck_id',
    ];
}
