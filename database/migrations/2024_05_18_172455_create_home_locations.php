<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use MatanYadaev\EloquentSpatial\Enums\Srid;

return new class() extends Migration {
    public function up(): void
    {
        Schema::create('home_locations', function (Blueprint $table): void {
            $table->id();
            $table->string('channel_id', 20);
            $table->geography(
                'location',
                subtype: 'point',
                srid: Srid::WGS84->value
            );
            $table->string('set_by', 20);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('home_locations');
    }
};
