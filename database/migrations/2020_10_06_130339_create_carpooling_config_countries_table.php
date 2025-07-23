<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCarpoolingConfigCountriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('carpooling_config_countries', function (Blueprint $table) {
            $table->increments('id');

            $table->integer('merchant_id')->unsigned();
            $table->foreign('merchant_id')->references('id')->on('merchants')->onUpdate('RESTRICT')->onDelete('CASCADE');

            $table->unsignedInteger('country_id')->nullable();
            $table->foreign('country_id')->references('id')->on('countries')->onUpdate('RESTRICT')->onDelete('CASCADE');



            //paylater
            $table->string('ride_confirm_time')->nullable();


            $table->tinyInteger('status')->default(1)->comment('1:active , 2:deactive');
            $table->string('user_cashout_min_amount')->nullable();


            $table->string('hold_money_before_ride_start')->nullable();
            $table->string('drop_location_radius')->nullable();
            $table->string('number_of_drops')->nullable();
            $table->string('no_of_rides_to_show_user')->nullable();
            $table->string('transfer_money_to_user')->nullable();
            $table->string('user_ride_start_time')->nullable();
            $table->string('user_document_reminder_time')->nullable();
            $table->string('short_ride')->nullable();
            $table->string('short_ride_time')->nullable();
            $table->string('long_ride_time')->nullable();
            $table->string('start_location_radius')->nullable();
            $table->tinyInteger('offer_ride_seat_config')->comment('1:manual, 2:automatic')->nullable();
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
        Schema::dropIfExists('carpooling_config_countries');
    }
}
