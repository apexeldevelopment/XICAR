<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateBusesTable extends Migration
{

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('buses', function (Blueprint $table) {
			$table->increments('id');

			$table->integer('merchant_id')->unsigned()->nullable();
            $table->foreign('merchant_id')->references('id')->on('merchants')->onUpdate('RESTRICT')->onDelete('CASCADE');

			// $table->integer('driver_id')->nullable();
			$table->integer('owner_id')->unsigned()->nullable();
			$table->foreign('owner_id')->references('id')->on('drivers')->onUpdate('RESTRICT')->onDelete('CASCADE');

			$table->integer('ownerType')->nullable()->default(1)->comment('1 own vehicle, 2: other person vehicle');

			$table->string('shareCode', 191)->nullable();

            $table->integer('vehicle_type_id')->unsigned();
            $table->foreign('vehicle_type_id')->references('id')->on('vehicle_types')->onUpdate('RESTRICT')->onDelete('CASCADE');

			$table->integer('vehicle_make_id')->unsigned();
			$table->foreign('vehicle_make_id')->references('id')->on('vehicle_makes')->onUpdate('RESTRICT')->onDelete('CASCADE');

			$table->integer('vehicle_model_id')->unsigned();
			$table->foreign('vehicle_model_id')->references('id')->on('vehicle_models')->onUpdate('RESTRICT')->onDelete('CASCADE');

			$table->integer('country_area_id')->unsigned();
			$table->foreign('country_area_id')->references('id')->on('country_areas')->onUpdate('RESTRICT')->onDelete('CASCADE');

            $table->string('bus_name', 191)->comment('bus_name');
            $table->string('traveller_name', 191)->nullable()->comment('traveller_name');
			$table->string('vehicle_number', 191);
			$table->date('vehicle_register_date')->nullable();
			$table->date('vehicle_expire_date')->nullable();
			$table->string('vehicle_color', 191);
			$table->string('vehicle_image')->default('');
			$table->string('vehicle_number_plate_image')->default('');
			// no need of this column
			//$table->integer('vehicle_active_status')->default(2)->comment('1: Active,2: Deactive ');
			$table->integer('vehicle_verification_status')->default(2)->comment('1: Pending,2: Verified,3:rejected,4: Expired');
			$table->integer('reject_reason_id')->nullable();
			$table->integer('ac_nonac')->nullable();
			$table->integer('baby_seat')->nullable();
			$table->integer('wheel_chair')->nullable();
			$table->integer('total_expire_document')->nullable();

            $table->enum('type', ["LOWER", "LOWER_UPPER"])->default("LOWER");
            $table->tinyInteger('design_type')->default(1)->nullable();
            $table->string('map_image_one')->nullable();
            $table->string('map_image_two')->nullable();
            $table->tinyInteger('total_seats')->nullable();
            $table->longText('additional_info')->nullable();

            $table->integer('vehicle_delete')->nullable();
            $table->integer('rating')->nullable();
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
		Schema::drop('buses');
	}
}
