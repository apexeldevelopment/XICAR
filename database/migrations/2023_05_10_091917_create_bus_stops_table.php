<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBusStopsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('bus_stops', function (Blueprint $table) {
            $table->increments('id');

            $table->unsignedInteger("merchant_id");
            $table->foreign("merchant_id")->on("merchants")->references("id")->onUpdate("RESTRICT")->onDelete("CASCADE");

            $table->unsignedInteger("segment_id");
            $table->foreign("segment_id")->on("segments")->references("id")->onUpdate("RESTRICT")->onDelete("CASCADE");

            $table->unsignedInteger("service_type_id");
            $table->foreign("service_type_id")->on("service_types")->references("id")->onUpdate("RESTRICT")->onDelete("CASCADE");

            $table->tinyInteger('status');
            $table->string('latitude');
            $table->string('longitude');
            $table->string('address');
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
