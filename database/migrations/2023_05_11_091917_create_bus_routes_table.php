<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBusRoutesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('bus_routes', function (Blueprint $table) {
            $table->increments('id');

            $table->unsignedInteger("merchant_id");
            $table->foreign("merchant_id")->on("merchants")->references("id")->onUpdate("RESTRICT")->onDelete("CASCADE");

            $table->unsignedInteger("segment_id");
            $table->foreign("segment_id")->on("segments")->references("id")->onUpdate("RESTRICT")->onDelete("CASCADE");

            $table->unsignedInteger("service_type_id");
            $table->foreign("service_type_id")->on("service_types")->references("id")->onUpdate("RESTRICT")->onDelete("CASCADE");

            $table->unsignedInteger("country_area_id");
            $table->foreign("country_area_id")->on("country_areas")->references("id")->onUpdate("RESTRICT")->onDelete("CASCADE");

            $table->unsignedInteger('start_point');
            $table->foreign("start_point")->on("bus_stops")->references("id")->onUpdate("RESTRICT")->onDelete("CASCADE");
            $table->unsignedInteger('end_point');
            $table->foreign("end_point")->on("bus_stops")->references("id")->onUpdate("RESTRICT")->onDelete("CASCADE");

            $table->tinyInteger('status');
            $table->tinyInteger('is_configured')->default(2);

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
