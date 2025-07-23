<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateBookingCheckoutsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('booking_checkouts', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('merchant_id');
			$table->integer('segment_id');
			$table->integer('user_id');
            $table->tinyInteger('is_in_drive')->default(2);
            $table->integer('offer_amount')->nullable();
			$table->integer('country_area_id');
			$table->integer('service_type_id');
			$table->integer('vehicle_type_id');
			$table->integer('service_package_id')->nullable();
			$table->integer('is_geofence')->default(0)->comment('0 - Normal booking, 1 - Geofence booking');
			$table->unsignedInteger('base_area_id')->nullable();
			$table->integer('price_card_id');
			$table->integer('total_drop_location')->default(0);
			$table->integer('auto_upgradetion')->default(2);
			$table->integer('number_of_rider')->nullable()->default(1);
			$table->integer('payment_method_id')->nullable();
			$table->integer('card_id')->nullable();
			$table->string('pickup_latitude', 191);
			$table->string('pickup_longitude', 191);
			$table->string('pickup_location', 191);
			$table->string('drop_latitude', 191)->nullable();
			$table->string('drop_longitude', 191)->nullable();
			$table->string('drop_location', 191)->nullable()->default('');
			$table->text('waypoints')->nullable();
			$table->unsignedInteger('promo_code')->nullable();
			$table->text('map_image');
			$table->string('estimate_bill')->nullable();
            $table->string('hotel_charges')->nullable();
			$table->string('estimate_distance')->nullable();
			$table->string('estimate_time')->nullable();
			$table->string('estimate_driver_distance', 191)->nullable();
			$table->string('estimate_driver_time', 191)->nullable();
			$table->tinyInteger('booking_type')->nullable();
			$table->string('later_booking_date', 191)->nullable();
			$table->string('later_booking_time', 191)->nullable();
			$table->string('return_date', 191)->nullable();
			$table->string('return_time', 191)->nullable();
			$table->text('additional_notes')->nullable();
			$table->text('bill_details')->nullable();
			$table->integer('baby_seat_enable')->nullable()->comment("1 for yes, 0 for no");
			$table->integer('wheel_chair_enable')->nullable()->comment("1 for yes, 0 for no");
			$table->integer('ac_nonac')->nullable()->comment("1 for yes, 0 for no");
			$table->integer('no_of_person')->nullable();
			$table->integer('no_of_children')->nullable();
            $table->integer('no_of_bags')->nullable();
            $table->integer('no_of_pats')->nullable();
			$table->integer('bags_weight_kg')->nullable();
			$table->integer('manual_dispatch_ride')->nullable();
            $table->integer('gender')->nullable();
            $table->integer('payment_option_id')->nullable();
            $table->text('additional_user_details')->nullable()->comment("If user want to book a ride for other user");
			$table->double("discounted_amount")->nullable()->default(0);
			$table->tinyInteger("automatic_promo_applied")->default(0)->comment("0->NotApplied 1->Applied");
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
		Schema::drop('booking_checkouts');
	}

}
