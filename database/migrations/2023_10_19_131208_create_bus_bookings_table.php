<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('bus_bookings', function (Blueprint $table) {
            $table->id();

            $table->string('merchant_bus_booking_id')->nullable();

            $table->unsignedInteger('merchant_id')->index('merchant_id');
            $table->foreign("merchant_id")->on("merchants")->references("id")->onUpdate("RESTRICT")->onDelete("CASCADE");

            $table->unsignedInteger('user_id')->index('user_id');
            $table->foreign("user_id")->on("users")->references("id")->onUpdate("RESTRICT")->onDelete("CASCADE");

            $table->unsignedBigInteger('bus_booking_master_id')->index('bus_booking_master_id');
            $table->foreign("bus_booking_master_id")->on("bus_booking_masters")->references("id")->onUpdate("RESTRICT")->onDelete("CASCADE");

            $table->unsignedInteger('country_area_id')->index('country_area_id')->nullable();
            $table->foreign("country_area_id")->on("country_areas")->references("id")->onUpdate("RESTRICT")->onDelete("CASCADE");

            $table->unsignedInteger('bus_stop_id')->index('bus_stop_id');
            $table->foreign("bus_stop_id")->on("bus_stops")->references("id")->onUpdate("RESTRICT")->onDelete("CASCADE");

            $table->unsignedInteger('end_bus_stop_id')->index('end_bus_stop_id');
            $table->foreign("end_bus_stop_id")->on("bus_stops")->references("id")->onUpdate("RESTRICT")->onDelete("CASCADE");

            $table->unsignedBigInteger('pickup_point_id')->nullable();
            $table->foreign("pickup_point_id")->on("bus_pickup_drop_points")->references("id")->onUpdate("RESTRICT")->onDelete("CASCADE");

            $table->unsignedBigInteger('drop_point_id')->nullable();
            $table->foreign("drop_point_id")->on("bus_pickup_drop_points")->references("id")->onUpdate("RESTRICT")->onDelete("CASCADE");

            $table->tinyInteger("total_seats")->nullable();
            $table->longText("coordinates")->nullable();
            $table->integer("tax")->nullable();
            $table->integer("total_amount")->nullable()->comment("tax included");

            $table->unsignedInteger('payment_method_id')->nullable();
            $table->foreign("payment_method_id")->on("payment_methods")->references("id")->onUpdate("RESTRICT")->onDelete("CASCADE");

            $table->tinyInteger("payment_status")->nullable();
            $table->tinyInteger("status")->default(1)->comment("1-New Booking,2-Started,3-Completed,4-Cancelled,5-Admin Cancelled");
            $table->tinyInteger("notify_status")->nullable();

            $table->float("rating")->nullable();
            $table->string("comments")->nullable();

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
        Schema::dropIfExists('bus_bookings');
    }
};
