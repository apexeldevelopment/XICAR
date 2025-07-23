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
        Schema::create('bus_booking_details', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('bus_booking_id')->index('bus_booking_id');
            $table->foreign("bus_booking_id")->on("bus_bookings")->references("id")->onUpdate("RESTRICT")->onDelete("CASCADE");

            $table->unsignedBigInteger('bus_seat_detail_id')->index('bus_seat_detail_id');
            $table->foreign("bus_seat_detail_id")->on("bus_seat_details")->references("id")->onUpdate("RESTRICT")->onDelete("CASCADE");

            $table->string("name")->nullable();
            $table->integer("age")->nullable();
            $table->tinyInteger("gender")->default(1)->comment("1:Male,2:Female");
            $table->integer("amount")->nullable();

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
        Schema::dropIfExists('bus_booking_details');
    }
};
