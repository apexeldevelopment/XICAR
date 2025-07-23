<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCarpoolingConfigurationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('carpooling_configurations', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('merchant_id')->unsigned();
            $table->foreign('merchant_id')->references('id')->on('merchants')->onUpdate('RESTRICT')->onDelete('CASCADE');
            $table->string('hold_money_before_ride_start')->nullable();
            $table->string('drop_location_radius')->nullable();
            $table->string('number_of_drops')->nullable();
            $table->string('no_of_rides_to_show_user')->nullable();
            $table->string('transfer_money_to_user')->nullable();
            $table->string('user_ride_start_time')->nullable();
            $table->string('user_document_reminder_time')->nullable();
            $table->string('offer_ride_cancel_time')->nullable();
            $table->string('offer_ride_cancel_time_for_long_ride')->nullable();
            $table->string('offer_ride_cancel_radius')->nullable();
            $table->string('offer_ride_cancel_deduct_amount_from_user')->nullable();
            $table->string('taken_ride_cancel_time_for_local_ride')->nullable();
            $table->string('taken_ride_cancel_time_for_long_ride')->nullable();
            $table->string('taken_ride_cancel_radius')->nullable();
            $table->string('taken_ride_cancel_company_commission')->nullable();
            $table->string('taken_ride_cancel_user_commission')->nullable();

            $table->string('taken_ride_cancel_company_cut')->nullable();
            $table->string('taken_ride_cancel_user_cut')->nullable();
            $table->string('taken_ride_cancel_time')->nullable();
            $table->string('amount_deduct_in_cancel_offer_ride')->nullable();
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
        Schema::dropIfExists('carpooling_configurations');
    }
}
