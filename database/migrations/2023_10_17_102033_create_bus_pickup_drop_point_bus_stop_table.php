<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('bus_pickup_drop_point_bus_stop', function (Blueprint $table) {
            $table->unsignedBigInteger('bus_pickup_drop_point_id');
            $table->foreign("bus_pickup_drop_point_id")->on("bus_pickup_drop_points")->references("id")->onUpdate("RESTRICT")->onDelete("CASCADE");

            $table->unsignedInteger('bus_stop_id')->index('language_bus_pickup_drop_point');
            $table->foreign("bus_stop_id")->on("bus_stops")->references("id")->onUpdate("RESTRICT")->onDelete("CASCADE");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('bus_pickup_drop_point_bus_stop');
    }
};
