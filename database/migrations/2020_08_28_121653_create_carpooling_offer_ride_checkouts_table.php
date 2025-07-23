<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCarpoolingOfferRideCheckoutsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('carpooling_offer_ride_checkouts', function (Blueprint $table) {
            $table->increments('id');

            $table->unsignedInteger('merchant_id');
            $table->foreign('merchant_id')->references('id')->on('merchants')->onUpdate('RESTRICT')->onDelete('CASCADE');

            $table->unsignedInteger('segment_id');
            $table->foreign('segment_id')->references('id')->on('segments')->onUpdate('RESTRICT')->onDelete('CASCADE');

            $table->unsignedInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users')->onUpdate('RESTRICT')->onDelete('CASCADE');

            $table->unsignedInteger('user_vehicle_id')->nullable();
            $table->foreign('user_vehicle_id')->references('id')->on('user_vehicles')->onUpdate('RESTRICT')->onDelete('CASCADE');

            $table->unsignedInteger('country_area_id');
            $table->foreign('country_area_id')->references('id')->on('country_areas')->onUpdate('RESTRICT')->onDelete('CASCADE');

            $table->string("start_latitude");
            $table->string("start_longitude");
            $table->text("start_location");

            $table->string("end_latitude");
            $table->string("end_longitude");
            $table->text("end_location");

            $table->string('ride_timestamp');
            $table->string('return_ride')->default(0);
            $table->string('return_ride_timestamp')->nullable();

            $table->tinyInteger('ac_ride')->default(0);
            $table->tinyInteger('female_ride')->default(0);
            $table->tinyInteger('payment_type')->default(0);

            $table->string('available_seats')->default(0);
            $table->string('no_of_stops')->default(1);

//            $table->string('estimate_bill')->nullable();
//            $table->string('estimate_distance')->nullable();
//            $table->string('total_amount')->nullable();
            $table->string('additional_notes')->nullable();
            $table->text('map_image')->nullable();

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
        Schema::dropIfExists('carpooling_offer_ride_checkouts');
    }
}
