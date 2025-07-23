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
        Schema::create('bus_booking_masters', function (Blueprint $table) {
            $table->id();

            $table->unsignedInteger('merchant_id')->index('merchant_id');
            $table->foreign("merchant_id")->on("merchants")->references("id")->onUpdate("RESTRICT")->onDelete("CASCADE");

            $table->unsignedInteger('segment_id')->index('segment_id');
            $table->foreign("segment_id")->on("segments")->references("id")->onUpdate("RESTRICT")->onDelete("CASCADE");

            $table->unsignedInteger('service_type_id')->index('service_type_id');
            $table->foreign("service_type_id")->on("service_types")->references("id")->onUpdate("RESTRICT")->onDelete("CASCADE");

            $table->unsignedInteger('bus_id')->index('bus_id');
            $table->foreign("bus_id")->on("buses")->references("id")->onUpdate("RESTRICT")->onDelete("CASCADE");

            $table->unsignedInteger('driver_id')->nullable();
            $table->foreign("driver_id")->on("drivers")->references("id")->onUpdate("RESTRICT")->onDelete("CASCADE");

            $table->unsignedInteger('bus_route_id')->index('bus_route_id');
            $table->foreign("bus_route_id")->on("bus_routes")->references("id")->onUpdate("RESTRICT")->onDelete("CASCADE");

            $table->date("booking_date");

            $table->unsignedInteger('service_time_slot_detail_id')->index('service_time_slot_detail_id');
            $table->foreign("service_time_slot_detail_id")->on("service_time_slot_details")->references("id")->onUpdate("RESTRICT")->onDelete("CASCADE");

            $table->tinyInteger("status")->default(1)->comment("1-New Booking,2-Started,3-Completed,4-Cancelled,5-Expired");
            $table->tinyInteger("notify_status")->nullable();

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
        Schema::dropIfExists('bus_booking_masters');
    }
};
