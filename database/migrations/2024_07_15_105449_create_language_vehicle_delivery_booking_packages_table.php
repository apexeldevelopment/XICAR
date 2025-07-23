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
        Schema::create('vehicle_delivery_booking_packages', function (Blueprint $table) {
            $table->id();
            $table->integer('booking_id')->unsigned();
            $table->foreign('booking_id')->references('id')->on('bookings')->onUpdate('RESTRICT')->onDelete('CASCADE');
            $table->integer('booking_delivery_detail_id')->unsigned();
            $table->foreign('booking_delivery_detail_id')->references('id')->on('booking_delivery_details')->onUpdate('RESTRICT')->onDelete('CASCADE');
            $table->string('quantity')->nullable();
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
        Schema::dropIfExists('language_merchant_membership_plans');
    }
};
