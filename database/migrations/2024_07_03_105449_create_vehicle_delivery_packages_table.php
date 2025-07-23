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
        Schema::create('vehicle_delivery_packages', function (Blueprint $table) {
            $table->id();
            $table->integer('vehicle_type_id')->unsigned();
            $table->foreign('vehicle_type_id')->references('id')->on('vehicle_types')->onUpdate('RESTRICT')->onDelete('CASCADE');
            $table->string('volumetric_capacity')->nullable();
            $table->string('weight')->nullable();
            $table->double('price')->nullable();
            $table->string('description')->nullable();
            $table->string('package_customize_data')->nullable();
            $table->string('package_length')->nullable();
            $table->string('package_width')->nullable();
            $table->string('package_height')->nullable();
            $table->string('image')->nullable();
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
