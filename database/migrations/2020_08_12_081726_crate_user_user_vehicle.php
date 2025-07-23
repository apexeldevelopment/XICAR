<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CrateUserUserVehicle extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_user_vehicle', function (Blueprint $table) {
            $table->integer('user_id')->unsigned()->index('user_user_vehicle_user_id_foreign');
            $table->foreign('user_id')->references('id')->on('users')->onUpdate('RESTRICT')->onDelete('CASCADE');

            $table->integer('user_vehicle_id')->unsigned()->index('user_user_vehicle_user_vehicle_id_foreign');
            $table->foreign('user_vehicle_id')->references('id')->on('user_vehicles')->onUpdate('RESTRICT')->onDelete('CASCADE');

            $table->tinyInteger('vehicle_active_status')->default(2)->comment('1: Active,2: Deactive ');
            $table->tinyInteger('user_default_vehicle')->default(2)->comment('1: Default 2:Normal');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('user_user_vehicle', function (Blueprint $table) {
            //
        });
    }
}
