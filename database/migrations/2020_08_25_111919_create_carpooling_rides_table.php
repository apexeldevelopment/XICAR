<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCarpoolingRidesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('carpooling_rides', function (Blueprint $table) {
            $table->increments('id');

            $table->unsignedInteger('merchant_id');
            $table->foreign('merchant_id')->references('id')->on('merchants')->onUpdate('RESTRICT')->onDelete('CASCADE');

            $table->unsignedInteger('segment_id');
            $table->foreign('segment_id')->references('id')->on('segments')->onUpdate('RESTRICT')->onDelete('CASCADE');

            $table->unsignedInteger('merchant_ride_id');

            $table->unsignedInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users')->onUpdate('RESTRICT')->onDelete('CASCADE');

            $table->unsignedInteger('user_vehicle_id')->nullable();
            $table->foreign('user_vehicle_id')->references('id')->on('user_vehicles')->onUpdate('RESTRICT')->onDelete('CASCADE');

            $table->unsignedInteger('country_area_id')->nullable();
            $table->foreign('country_area_id')->references('id')->on('country_areas')->onUpdate('RESTRICT')->onDelete('CASCADE');

            $table->string("start_latitude");
            $table->string("start_longitude");
            $table->text("start_location");

            $table->string("end_latitude");
            $table->string("end_longitude");
            $table->text("end_location");

            $table->string('ride_timestamp');
            $table->string('return_ride')->default(0)->nullable();
            $table->string('return_ride_timestamp')->nullable();

            $table->tinyInteger('ac_ride')->default(0)->nullable();
            $table->tinyInteger('female_ride')->default(0)->nullable();
            $table->tinyInteger('payment_type')->default(0)->nullable();

            $table->tinyInteger('available_seats')->default(0)->nullable();
            $table->tinyInteger('booked_seats')->default(0)->nullable();
            $table->tinyInteger('total_booked_seats')->default(0)->nullable();
            $table->tinyInteger('no_of_stops')->default(1)->nullable();

//            $table->string('estimate_bill')->nullable();
//            $table->string('estimate_distance')->nullable();
            $table->string('total_amount')->default(0)->nullable();
            $table->string('final_paid_amount')->default(0)->nullable();
            $table->string('driver_earning')->default(0)->nullable();
            $table->string('company_commission')->default(0)->nullable();
            $table->string('service_charges')->default(0)->nullable();
            $table->string('cancel_amount')->default(0)->nullable();
            $table->text('carpooling_logs')->nullable();
            $table->string('additional_notes')->nullable();
            $table->text('map_image')->nullable();

            $table->string("ride_status")->default(1);
            $table->string("ride_status_history")->nullable();

            $table->unsignedInteger('cancel_reason_id')->nullable();
            $table->foreign('cancel_reason_id')->references('id')->on('cancel_reasons')->onUpdate('RESTRICT')->onDelete('CASCADE');

            $table->string('current_latitude')->nullable();
            $table->string('current_longitude')->nullable();
            $table->text('cancel_reason_text')->nullable();

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
        Schema::dropIfExists('carpooling_rides');
    }
}
