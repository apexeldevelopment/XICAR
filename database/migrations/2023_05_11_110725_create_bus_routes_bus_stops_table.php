<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBusRoutesBusStopsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('bus_routes_bus_stops', function (Blueprint $table) {

            $table->increments("id");
            $table->unsignedInteger("bus_route_id");
            $table->foreign("bus_route_id")->on("bus_routes")->references("id")->onUpdate("RESTRICT")->onDelete("CASCADE");

            $table->unsignedInteger("bus_stop_id");
            $table->foreign("bus_stop_id")->on("bus_stops")->references("id")->onUpdate("RESTRICT")->onDelete("CASCADE");

            $table->unsignedInteger("end_bus_stop_id")->nullable();
            $table->foreign("end_bus_stop_id")->on("bus_stops")->references("id")->onUpdate("RESTRICT")->onDelete("CASCADE");

            $table->integer('time')->default(00)->comment("minutes time take between two points");
            $table->integer('sequence')->default(1);

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('bus_routes_bus_stops');
    }
}
