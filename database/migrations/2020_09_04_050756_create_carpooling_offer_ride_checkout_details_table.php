<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCarpoolingOfferRideCheckoutDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('carpooling_offer_ride_checkout_details', function (Blueprint $table) {
            $table->increments('id');

            $table->unsignedInteger('carpooling_offer_ride_checkout_id');
            $table->foreign('carpooling_offer_ride_checkout_id','cporc_id_foreign')->references('id')->on('carpooling_offer_ride_checkouts')->onUpdate('RESTRICT')->onDelete('CASCADE');

            $table->unsignedInteger('price_card_id')->nullable();
            $table->foreign('price_card_id')->references('id')->on('price_cards')->onUpdate('RESTRICT')->onDelete('CASCADE');

            $table->integer("drop_no");

            $table->string("from_latitude");
            $table->string("from_longitude");
            $table->text("from_location");

            $table->string("to_latitude");
            $table->string("to_longitude");
            $table->text("to_location");

            $table->string('ride_timestamp');
            $table->string('end_timestamp')->nullable();
            $table->tinyInteger('is_return')->nullable();

            $table->integer("estimate_charges")->nullable();
            $table->integer("estimate_distance")->nullable();
            $table->string("estimate_distance_text")->nullable();

            $table->integer("final_charges")->nullable();

            $table->text("map_image")->nullable();
            $table->text("bill_details")->nullable();

            $table->text("eta")->nullable();
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
        Schema::dropIfExists('carpooling_offer_ride_checkout_details');
    }
}
