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
        Schema::create('language_vehicle_delivery_packages', function (Blueprint $table) {
            $table->id();
            $table->integer('vehicle_delivery_package_id')->unsigned();
            $table->foreign('vehicle_delivery_package_id')->references('id')->on('vehicle_delivery_packages')->onUpdate('RESTRICT')->onDelete('CASCADE');
            $table->string('package_name')->nullable();
            $table->string('locale', 10)->index();
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
