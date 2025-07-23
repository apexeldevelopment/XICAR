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
        Schema::create('booking_bidding_drivers', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('booking_id');
            $table->foreign('booking_id')->on("bookings")->references("id")->onUpdate('RESTRICT')->onDelete("CASCADE");

            $table->unsignedInteger('driver_id');
            $table->foreign('driver_id')->on("drivers")->references("id")->onUpdate('RESTRICT')->onDelete("CASCADE");

            $table->unsignedInteger('driver_vehicle_id')->nullable();
            $table->foreign('driver_vehicle_id')->on("driver_vehicles")->references("id")->onUpdate('RESTRICT')->onDelete("CASCADE");

            $table->tinyInteger("status")->default(0)->comment("0-pending, 1-accept, 2-counter, 3-reject, 4-finialize");
            $table->decimal("amount",10,2)->nullable();
            $table->string("description")->nullable();
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
        Schema::dropIfExists('booking_bidding_drivers');
    }
};
