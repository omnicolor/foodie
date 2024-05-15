<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up(): void
    {
        Schema::create('food_trucks', function (Blueprint $table): void {
            $table->id();
            // Longest data for cuisine and name in the current data set is 208.
            // Give it a bit of space in case a vendor is very verbose.
            $table->string('cuisine', 255);
            $table->string('name', 255);
            $table->double('latitude');
            $table->double('longitude');
            $table->string('truck_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('food_trucks');
    }
};
