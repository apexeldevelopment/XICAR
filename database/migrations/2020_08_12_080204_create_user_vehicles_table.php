<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUserVehiclesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_vehicles', function (Blueprint $table) {
            $table->increments('id');

            $table->integer('merchant_id')->unsigned()->nullable();
            $table->foreign('merchant_id')->references('id')->on('merchants')->onUpdate('RESTRICT')->onDelete('CASCADE');

            $table->integer('user_id')->unsigned()->nullable();
            $table->foreign('user_id')->references('id')->on('users')->onUpdate('RESTRICT')->onDelete('CASCADE');

            $table->integer('owner_id')->nullable();
            $table->integer('ownerType')->nullable()->default(1)->comment('1 own vehicle, 2: other person vehicle');

            $table->integer('vehicle_type_id')->unsigned()->nullable();
            $table->foreign('vehicle_type_id')->references('id')->on('vehicle_types')->onUpdate('RESTRICT')->onDelete('CASCADE');

            $table->integer('vehicle_make_id')->unsigned()->nullable();
            $table->foreign('vehicle_make_id')->references('id')->on('vehicle_makes')->onUpdate('RESTRICT')->onDelete('CASCADE');

            $table->integer('vehicle_model_id')->unsigned()->nullable();
            $table->foreign('vehicle_model_id')->references('id')->on('vehicle_models')->onUpdate('RESTRICT')->onDelete('CASCADE');

            $table->string('shareCode', 191)->nullable();
            $table->string('vehicle_number', 191);
            $table->integer('no_of_seats')->nullable();
            $table->date('vehicle_register_date')->nullable();
            $table->string('vehicle_color', 191);
            $table->string('vehicle_image')->default('');
            $table->string('vehicle_number_plate_image')->default('');
            $table->integer('vehicle_verification_status')->default(1)->comment('1: Pending,2: Verified,3:rejected,4: Expired');

            $table->integer('reject_reason_id')->unsigned()->nullable();
            $table->foreign('reject_reason_id')->references('id')->on('reject_reasons')->onUpdate('RESTRICT')->onDelete('CASCADE');

            $table->integer('ac_nonac')->nullable();
            $table->integer('vehicle_delete')->nullable();
            $table->integer('total_expire_document')->nullable();
            $table->tinyInteger('active_default_vehicle')->default(2)->comment('1:default, 2:normal');
            $table->string('other_vehicle_model')->nullable();
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
        Schema::dropIfExists('user_vehicles');
    }
}
