<?php
//
//use Illuminate\Database\Migrations\Migration;
//use Illuminate\Database\Schema\Blueprint;
//
//class CreateBusDriversTable extends Migration
//{
//
//	/**
//	 * Run the migrations.
//	 *
//	 * @return void
//	 */
//	public function up()
//	{
//		Schema::create('bus_drivers', function (Blueprint $table) {
//			$table->increments('id');
//			$table->string('merchant_driver_id', 191)->nullable();
//
//			$table->integer('merchant_id')->unsigned();
//			$table->foreign('merchant_id')->references('id')->on('merchants')->onUpdate('RESTRICT')->onDelete('CASCADE');
//
//			$table->integer('country_area_id')->unsigned();
//			$table->foreign('country_area_id')->references('id')->on('country_areas')->onUpdate('RESTRICT')->onDelete('CASCADE');
//
//			$table->integer('country_id')->unsigned()->nullable();
//			$table->foreign('country_id')->references('id')->on('countries')->onUpdate('RESTRICT')->onDelete('CASCADE');
//
//
//			$table->string('phoneNumber', 191);
//			$table->string('first_name', 191);
//			$table->string('last_name', 191)->nullable();
//			$table->string('email', 25);
//			$table->string('password', 191);
//
//			$table->string('unique_number')->nullable();
//			$table->integer('driver_gender')->nullable()->comment('1 for male and 2 for female');
//			$table->string('profile_image')->default('');
//			$table->string('wallet_money', 191)->nullable();
//			$table->integer('total_trips')->nullable();
//			$table->string('total_earnings', 191)->nullable();
//			$table->string('total_comany_earning', 191)->nullable();
//
//			$table->string('current_latitude', 191)->nullable();
//			$table->string('current_longitude', 191)->nullable();
//			$table->dateTime('last_location_update_time')->nullable();
//			$table->string('bearing', 191)->nullable()->default('');
//			$table->string('accuracy', 191)->nullable()->default('');
//			$table->tinyInteger('device')->nullable()->comment('1:Android, 2:iOS ');
//			$table->string('player_id', 191)->nullable();
//			$table->decimal('rating', 10, 1)->nullable();
//
//			$table->integer('login_logout')->default(2);
//			$table->integer('online_offline')->default(2);
//			$table->integer('free_busy')->default(2);
//			$table->string('bank_name', 191)->nullable();
//			$table->string('account_holder_name', 191)->nullable();
//			$table->string('account_number', 191)->nullable();
//			$table->integer('account_type_id')->nullable()->comment('1:Saving 2:Current 3:Recurring Deposit Account 4:basic checking accounts');
//			$table->integer('driver_verify_status')->default(1);
//			$table->integer('signupFrom')->default(1);
//			$table->integer('signupStep')->default(1);
//			$table->dateTime('driver_verification_date')->nullable();
//			$table->integer('driver_admin_status')->default(1);
//			$table->string('access_token_id', 191)->nullable();
//			$table->integer('driver_delete')->nullable();
//
//
//			$table->string('driver_referralcode')->nullable();
//			$table->integer('driver_block_status')->nullable();
//			$table->integer('term_status')->nullable()->default(0);
//
//
//			$table->string('dob', 25)->nullable();
//			$table->integer('reject_driver')->nullable()->default(1);
//			$table->string('driver_cpf_number', 6555)->nullable();
//			$table->string('online_code')->nullable();
//			$table->string('last_ride_request_timestamp')->nullable();
//
//			// Driver language
//
//			$table->string('language')->nullable()->default("en");
//			$table->timestamps();
//		});
//	}
//
//
//	/**
//	 * Reverse the migrations.
//	 *
//	 * @return void
//	 */
//	public function down()
//	{
//		Schema::drop('bus_drivers');
//	}
//}
