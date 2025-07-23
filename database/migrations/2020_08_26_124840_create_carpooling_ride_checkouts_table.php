<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCarpoolingRideCheckoutsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('carpooling_ride_checkouts', function (Blueprint $table) {
            $table->increments('id');

            $table->unsignedInteger('merchant_id');
            $table->foreign('merchant_id')->references('id')->on('merchants')->onUpdate('RESTRICT')->onDelete('CASCADE');

            $table->unsignedInteger('segment_id');
            $table->foreign('segment_id')->references('id')->on('segments')->onUpdate('RESTRICT')->onDelete('CASCADE');

            $table->unsignedInteger('carpooling_ride_id');
            $table->foreign('carpooling_ride_id')->references('id')->on('carpooling_rides')->onUpdate('RESTRICT')->onDelete('CASCADE');

            $table->unsignedInteger('pickup_id');
            $table->foreign('pickup_id')->references('id')->on('carpooling_ride_details')->onUpdate('RESTRICT')->onDelete('CASCADE');

            $table->unsignedInteger('drop_id');
            $table->foreign('drop_id')->references('id')->on('carpooling_ride_details')->onUpdate('RESTRICT')->onDelete('CASCADE');

            $table->unsignedInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users')->onUpdate('RESTRICT')->onDelete('CASCADE');

            $table->unsignedInteger('promo_code_id')->nullable();
            $table->foreign('promo_code_id')->references('id')->on('promo_codes')->onUpdate('RESTRICT')->onDelete('CASCADE');

            $table->string("pickup_location");
            $table->string("pickup_latitude");
            $table->string("pickup_longitude");

            $table->string("drop_location");
            $table->string("drop_latitude");
            $table->string("drop_longitude");

            $table->string('ride_timestamp');
            $table->string('end_timestamp');
            $table->tinyInteger('return_ride')->default(0);
            $table->string('return_ride_timestamp')->nullable();

            $table->tinyInteger('ac_ride')->default(0);
            $table->tinyInteger('payment_action')->default(0);
            $table->string('booked_seats')->default(0);

            $table->integer('ride_amount')->default(0);
            $table->integer('commission')->default(0);
            $table->integer('service_charges')->default(0);
            $table->integer('discount_amount')->default(0);
            $table->integer('total_amount')->default(0);
            $table->integer('driver_payable_amount')->default(0);
            $table->integer('merchant_amount')->default(0);
            $table->tinyInteger('female_ride')->default(0)->nullable();

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
        Schema::dropIfExists('carpooling_ride_checkouts');
    }
}
