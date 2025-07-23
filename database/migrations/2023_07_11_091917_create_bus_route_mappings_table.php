<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBusRouteMappingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        /*
        Mapping b/w route, Bus & time slot
        */
        Schema::create('bus_route_mappings', function (Blueprint $table) {

            $table->increments('id'); // don't use this id as foriegn key in any other table

            $table->unsignedInteger('bus_id');
            $table->foreign("bus_id")->on("buses")->references("id")->onUpdate("RESTRICT")->onDelete("CASCADE");

            $table->unsignedInteger('bus_route_id');
            $table->foreign("bus_route_id")->on("bus_routes")->references("id")->onUpdate("RESTRICT")->onDelete("CASCADE");


            $table->integer('service_time_slot_detail_id')->unsigned();
            $table->foreign('service_time_slot_detail_id')->references('id')->on('service_time_slot_details')->onUpdate('RESTRICT')->onDelete('CASCADE');

            $table->tinyInteger('status');

            // $table->unsignedInteger("merchant_id");
            // $table->foreign("merchant_id")->on("merchants")->references("id")->onUpdate("RESTRICT")->onDelete("CASCADE");

            // $table->unsignedInteger("country_area_id");
            // $table->foreign("country_area_id")->on("country_areas")->references("id")->onUpdate("RESTRICT")->onDelete("CASCADE");

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
        Schema::dropIfExists('bus_stops');
    }
}
