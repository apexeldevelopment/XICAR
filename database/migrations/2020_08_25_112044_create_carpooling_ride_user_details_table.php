<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCarpoolingRideUserDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('carpooling_ride_user_details', function (Blueprint $table) {
            $table->increments('id');

            $table->unsignedInteger('merchant_id');
            $table->foreign('merchant_id')->references('id')->on('merchants')->onUpdate('RESTRICT')->onDelete('CASCADE');

            $table->unsignedInteger('carpooling_ride_id');
            $table->foreign('carpooling_ride_id')->references('id')->on('carpooling_rides')->onUpdate('RESTRICT')->onDelete('CASCADE');

            $table->unsignedInteger('carpooling_ride_detail_id');
            $table->foreign('carpooling_ride_detail_id')->references('id')->on('carpooling_ride_details')->onUpdate('RESTRICT')->onDelete('CASCADE');

            $table->unsignedInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users')->onUpdate('RESTRICT')->onDelete('CASCADE');

            $table->unsignedInteger('end_point_id')->nullable();

            $table->unsignedInteger('pickup_id')->nullable();

            $table->unsignedInteger('drop_id')->nullable();
            $table->unsignedInteger('end_ride_id')->nullable();

            $table->string("user_rating")->default(0);
            $table->text("user_comment")->nullable();
            $table->string("driver_rating")->default(0);
            $table->string("driver_comment")->nullable();

            $table->string("pickup_location");
            $table->string("pickup_latitude");
            $table->string("pickup_longitude");

            $table->string("drop_location");
            $table->string("drop_latitude");
            $table->string("drop_longitude");

            $table->string('ride_timestamp');
            $table->string('end_timestamp');
            $table->string('return_ride_timestamp')->nullable();

            $table->string("ride_status")->default(1);
            $table->string('booked_seats')->default(0);

            $table->unsignedInteger('payment_method_id')->nullable();
            $table->foreign('payment_method_id')->references('id')->on('payment_methods')->onUpdate('RESTRICT')->onDelete('CASCADE');

            $table->unsignedInteger('user_card_id')->nullable();
            $table->foreign('user_card_id')->references('id')->on('user_cards')->onUpdate('RESTRICT')->onDelete('CASCADE');

            $table->unsignedInteger('cancel_reason_id')->nullable();
            $table->foreign('cancel_reason_id')->references('id')->on('cancel_reasons')->onUpdate('RESTRICT')->onDelete('CASCADE');

            $table->text('cancel_reason_text')->nullable();

            $table->unsignedInteger('promo_code_id')->nullable();
            $table->foreign('promo_code_id')->references('id')->on('promo_codes')->onUpdate('RESTRICT')->onDelete('CASCADE');

            $table->string('ride_booking_otp')->nullable();
            $table->tinyInteger('payment_action')->default(0)->nullable();
            $table->tinyInteger('payment_status')->default(0)->nullable();
             $table->string('is_return_ride')->default(0)->nullable();

            $table->string('ride_amount')->default(0)->nullable();
            $table->string('commission')->default(0)->nullable();
            $table->string('service_charges')->default(0)->nullable();
            $table->string('discount_amount')->default(0)->nullable();
            $table->string('total_amount')->default(0)->nullable();
            $table->string('driver_payable_amount')->default(0)->nullable();
            $table->string('merchant_amount')->default(0)->nullable();
            $table->string('cancel_amount')->default(0)->nullable();
            $table->string('ac_ride')->default(0)->nullable();
            $table->string('female_ride')->default(0)->nullable();
            $table->string('cancel_refund_amount')->default(0)->nullable();
            //$table->string('reject_refund_amount')->default(0)->nullable();
            $table->text('carpooling_logs')->nullable();
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
        Schema::dropIfExists('carpooling_ride_user_details');
    }
}
