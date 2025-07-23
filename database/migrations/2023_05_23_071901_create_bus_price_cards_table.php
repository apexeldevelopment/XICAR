<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBusPriceCardsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('bus_price_cards', function (Blueprint $table) {
            $table->increments('id');

            $table->unsignedInteger("merchant_id");
            $table->foreign("merchant_id")->on("merchants")->references("id")->onUpdate("RESTRICT")->onDelete("CASCADE");

            $table->unsignedInteger("country_area_id");
            $table->foreign("country_area_id")->on("country_areas")->references("id")->onUpdate("RESTRICT")->onDelete("CASCADE");

            $table->unsignedInteger("vehicle_type_id");
            $table->foreign("vehicle_type_id")->on("vehicle_types")->references("id")->onUpdate("RESTRICT")->onDelete("CASCADE");

//            $table->unsignedInteger('route_config_id')->nullable();
//            $table->foreign("route_config_id")->on("route_configs")->references("id")->onUpdate("RESTRICT")->onDelete("CASCADE");

            $table->unsignedInteger('bus_route_id')->nullable();
            $table->foreign("bus_route_id")->on("bus_routes")->references("id")->onUpdate("RESTRICT")->onDelete("CASCADE");

            $table->string('base_fare');
            $table->string('start_stop_fare');
            $table->string('end_stop_fare');

            $table->tinyInteger('status');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('route_config');
    }
}
