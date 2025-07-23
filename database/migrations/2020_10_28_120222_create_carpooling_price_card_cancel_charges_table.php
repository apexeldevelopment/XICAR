<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCarpoolingPriceCardCancelChargesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('carpooling_price_card_cancel_charges', function (Blueprint $table) {
            $table->increments('id');


            $table->integer('price_card_id')->unsigned();
            $table->foreign('price_card_id')->references('id')->on('price_cards')->onUpdate('RESTRICT')->onDelete('CASCADE');


            $table->string('offer_ride_city_radius')->nullable();
            $table->string('short_offer_ride_cancel_time')->nullable();
            $table->string('long_offer_ride_cancel_time')->nullable();

            $table->string('taken_ride_city_radius')->nullable();
            $table->string('short_taken_ride_cancel_time')->nullable();
            $table->string('long_taken_ride_cancel_time')->nullable();
            //paynow
            //offer ride cancel
            $table->string('short_offer_ride_cancel_amount')->nullable();
            $table->string('long_offer_ride_cancel_amount')->nullable();

            //taken ride cancel

            $table->string('short_taken_ride_cancel_company_cut')->nullable();
            $table->string('long_taken_ride_cancel_company_cut')->nullable();
            $table->string('short_taken_ride_cancel_user_cut')->nullable();
            $table->string('long_taken_ride_cancel_user_cut')->nullable();

            $table->string('no_show_taken_user_company_cut')->nullable();
            $table->string('no_show_taken_user_offer_user_cut')->nullable();
            $table->string('no_show_offer_user_company_cut')->nullable();


            //paylater
            $table->string('paylater_short_offer_ride_cancel_amount')->nullable();
            $table->string('paylater_long_offer_ride_cancel_amount')->nullable();
            $table->string('paylater_short_taken_ride_cancel_company_cut')->nullable();
            $table->string('paylater_long_taken_ride_cancel_company_cut')->nullable();
            $table->string('paylater_short_taken_ride_cancel_user_cut')->nullable();
            $table->string('paylater_long_taken_ride_cancel_user_cut')->nullable();
            $table->string('paylater_no_show_taken_user_company_cut')->nullable();
            $table->string('paylater_no_show_taken_user_offer_user_cut')->nullable();
            $table->string('paylater_no_show_offer_user_company_cut')->nullable();
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
        Schema::dropIfExists('carpooling_price_card_cancel_charges');
    }
}
