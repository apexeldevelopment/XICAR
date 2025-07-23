<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CarpoolingCoordinates extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        Schema::create('carpooling_coordinates', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('carpooling_ride_id');
            $table->foreign('carpooling_ride_id')->references('id')->on('carpooling_rides')->onUpdate('RESTRICT')->onDelete('CASCADE');
            $table->string("start_location");
            $table->string("end_location");
            $table->text("coordinates")->nullable();
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
        //
    }
}
