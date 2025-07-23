<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateApplicationConfigurationsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('application_configurations', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('merchant_id');
			$table->integer('driver_commission_choice')->nullable()->default(0)->comment('1:Enable 0:Disable');
			$table->integer('vehicle_owner')->nullable();
			$table->integer('home_screen_view')->default(2)->nullable()->comment('it must be 2');
            $table->integer('delivery_app_theme')->default(1)->nullable()->comment('1-default theme, 2-other theme');
			$table->string('user_default_language', 11)->nullable();
			$table->string('driver_default_language', 11)->nullable();
			$table->integer('demo')->nullable();
			$table->integer('user_email')->nullable();
            $table->integer('user_email_visibility')->default(1)->nullable();
			$table->integer('driver_email')->nullable();
            $table->integer('driver_email_visibility')->default(1)->nullable();
			$table->integer('user_phone')->nullable();
			$table->integer('driver_phone')->nullable();
			$table->integer('user_email_otp')->nullable()->default(0);
			$table->integer('user_phone_otp')->nullable()->default(0);
			$table->integer('driver_email_otp')->nullable()->default(0);
			$table->integer('driver_phone_otp')->nullable()->default(0);
			$table->string('user_login', 191)->nullable();
			$table->integer('user_email_otp_while_phone')->nullable()->default(0)->comment('Send Otp On Email');
			$table->string('driver_login', 191)->nullable();
			$table->integer('driver_email_otp_while_phone')->nullable()->default(0)->comment('Send Otp On Email');
			$table->integer('smoker')->nullable();
			$table->integer('gender')->nullable();
			$table->string('pickup_color', 191)->nullable();
			$table->string('dropoff_color', 191)->nullable();
			$table->integer('time_charges')->nullable();
			$table->integer('favourite_driver_module')->nullable();
			$table->integer('vehicle_rating_enable')->nullable();
			$table->integer('security_question')->nullable();
			$table->integer('enable_super_driver')->nullable()->comment("specific driver category");
			$table->integer('super_driver_limit')->nullable()->comment("Not IN USE");
			$table->integer('tip_status')->nullable();
			$table->integer('sub_charge')->nullable();
			$table->integer('user_document')->nullable();
			$table->string('default_config', 11)->nullable();
			$table->integer('userImage_enable')->nullable();
			$table->integer('sos_user_driver')->default(0)->nullable();
            $table->integer('driver_rating_enable')->nullable()->comment("user rate to driver");
            $table->integer('user_rating_enable')->nullable()->default(1)->comment("driver rate to user");
			$table->integer('vehicle_make_text')->nullable();
			$table->integer('vehicle_model_text')->nullable();
			$table->integer('user_number_track_screen')->nullable();
			$table->string('driver_name')->nullable();
			$table->string('driver_image')->nullable();
			$table->integer('user_cpf_number_enable')->nullable();
			$table->integer('driver_cpf_number_enable')->nullable();
			$table->integer('logo_hide')->nullable();
			$table->tinyInteger('hide_user_info_from_store')->nullable()->default(2)->comment("1:Yes, 2 : No");
			$table->tinyInteger('hide_user_info_from_driver')->nullable()->default(2)->comment("1:Yes, 2 : No");
			$table->string('splash_screen_driver', 191)->nullable();
			$table->string('splash_screen_user', 191)->nullable();
			$table->string('banner_image_user', 191)->nullable();
			$table->integer('otp_from_firebase')->nullable()->comment('1 for enable 2 for disable');
			$table->tinyInteger('restrict_country_wise_searching')->default(2)->nullable()->comment('1 for enable 2 for disable');
			$table->tinyInteger('segment_per_raw')->default(4)->nullable()->comment('4 segments per row');
			$table->tinyInteger('map_on_order_details')->default(2)->nullable()->comment('1 : Yes, 2 : No');
            $table->string('merchant_package_name')->nullable()->comment('to get drivers from node server');
            $table->tinyInteger('auto_fill_otp')->nullable()->comment('1 for enable 2 for disable');
//            $table->tinyInteger('drop_location_visible')->nullable()->comment('Drop location of user before driver accepts the ride');
            $table->tinyInteger('show_recommended_services')->nullable()->default(2)->comment("1:Show 2:Not Show");
            $table->tinyInteger('show_logo_main')->nullable()->default(2)->comment("Show logo on header or not");
            $table->string('logo_main')->nullable()->comment("Show logo on app header");
            $table->tinyInteger('main_screen_add_money_button')->nullable()->default(2)->comment("Show add money button on user app");
            $table->string('add_wallet_money_btntext')->nullable()->comment("Add money button text");
            $table->string('add_wallet_money_btncolor')->nullable()->comment("Add money button color");
            $table->string('add_wallet_money_image')->nullable()->comment("Add money screen image");
            $table->string('zaaou_service_holder_color')->nullable();
            $table->tinyInteger('user_face_recognition')->nullable();
            $table->tinyInteger('driver_face_recognition')->nullable();
            $table->tinyInteger('handyman_clubbing')->default(2)->nullable();
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
		Schema::drop('application_configurations');
	}

}
