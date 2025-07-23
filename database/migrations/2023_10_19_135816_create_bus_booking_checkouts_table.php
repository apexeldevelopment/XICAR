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
        Schema::create('bus_booking_checkouts', function (Blueprint $table) {
            $table->id();

            $table->unsignedInteger('merchant_id')->index('merchant_id');
            $table->foreign("merchant_id")->on("merchants")->references("id")->onUpdate("RESTRICT")->onDelete("CASCADE");

            $table->unsignedInteger('user_id')->index('user_id');
            $table->foreign("user_id")->on("users")->references("id")->onUpdate("RESTRICT")->onDelete("CASCADE");

            $table->unsignedInteger('segment_id')->index('segment_id');
            $table->foreign("segment_id")->on("segments")->references("id")->onUpdate("RESTRICT")->onDelete("CASCADE");

            $table->unsignedInteger('service_type_id')->index('service_type_id');
            $table->foreign("service_type_id")->on("service_types")->references("id")->onUpdate("RESTRICT")->onDelete("CASCADE");

            $table->unsignedInteger('country_area_id')->index('country_area_id')->nullable();
            $table->foreign("country_area_id")->on("country_areas")->references("id")->onUpdate("RESTRICT")->onDelete("CASCADE");

            $table->unsignedInteger('bus_id')->index('bus_id');
            $table->foreign("bus_id")->on("buses")->references("id")->onUpdate("RESTRICT")->onDelete("CASCADE");

            $table->unsignedInteger('bus_route_id')->index('bus_route_id');
            $table->foreign("bus_route_id")->on("bus_routes")->references("id")->onUpdate("RESTRICT")->onDelete("CASCADE");

            $table->unsignedInteger('bus_stop_id')->index('bus_stop_id');
            $table->foreign("bus_stop_id")->on("bus_stops")->references("id")->onUpdate("RESTRICT")->onDelete("CASCADE");

            $table->unsignedInteger('end_bus_stop_id')->index('end_bus_stop_id');
            $table->foreign("end_bus_stop_id")->on("bus_stops")->references("id")->onUpdate("RESTRICT")->onDelete("CASCADE");

            $table->unsignedBigInteger('pickup_point_id')->nullable();
            $table->foreign("pickup_point_id")->on("bus_pickup_drop_points")->references("id")->onUpdate("RESTRICT")->onDelete("CASCADE");

            $table->unsignedBigInteger('drop_point_id')->nullable();
            $table->foreign("drop_point_id")->on("bus_pickup_drop_points")->references("id")->onUpdate("RESTRICT")->onDelete("CASCADE");

            $table->date("booking_date");

            $table->unsignedInteger('service_time_slot_detail_id')->index('service_time_slot_detail_id');
            $table->foreign("service_time_slot_detail_id")->on("service_time_slot_details")->references("id")->onUpdate("RESTRICT")->onDelete("CASCADE");

            $table->longText("seat_details")->nullable();
            $table->longText("coordinates")->nullable();

            $table->tinyInteger("total_seats")->nullable();
            $table->integer("tax")->nullable();
            $table->integer("total_amount")->nullable()->comment("tax included");

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
        Schema::dropIfExists('bus_booking_checkouts');
    }
};
