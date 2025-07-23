<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateCountriesTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('countries', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('merchant_id');
			$table->integer('sequance')->nullable();
			$table->string('country_code', 10);
			$table->string('isoCode', 191);
			$table->string('phonecode', 191);
			$table->integer('distance_unit');
			$table->string('default_language', 191);
			$table->integer('maxNumPhone');
			$table->integer('minNumPhone');
			$table->integer('additional_details')->default(0)->comment('1:Enable 0:Disable');
			$table->string('parameter_name')->nullable();
			$table->string('placeholder')->nullable();
            $table->integer('country_status')->default(1);
            $table->string('payment_option_ids')->nullable();
			// Countrywise configurations (carpooling)
            $table->tinyInteger('automatic_cashout')->default(0)->nullable();
            $table->string('wallet_to_bank')->default(0)->nullable();
            $table->string('bank_to_wallet')->default(0)->nullable();
            $table->string('minimum_payin')->default(0)->nullable();
            $table->string('maximin_payin')->default(0)->nullable();
            $table->string('minimum_payout')->default(0)->nullable();
            $table->string('maximum_payout')->default(0)->nullable();
            $table->text('driver_address_fields')->nullable();
            $table->text('sub_area_codes')->nullable();
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
		Schema::drop('countries');
	}

}
