<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBusPriceCardBusStopsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('bus_price_card_bus_stops', function (Blueprint $table) {
            $table->unsignedInteger("bus_price_card_id");
            $table->foreign("bus_price_card_id")->on("bus_price_cards")->references("id")->onUpdate("RESTRICT")->onDelete("CASCADE");
            $table->unsignedInteger("bus_stop_id");
            $table->foreign("bus_stop_id")->on("bus_stops")->references("id")->onUpdate("RESTRICT")->onDelete("CASCADE");
            $table->tinyInteger("price");
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
