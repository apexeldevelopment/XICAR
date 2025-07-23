<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreatePaymentOptionsConfigurationsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('payment_options_configurations', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('merchant_id');
			$table->integer('payment_option_id');
			$table->string('payment_gateway_provider', 191)->nullable();
            $table->text('description')->nullable();
            $table->string('operator', 191)->nullable();
			$table->longText('api_secret_key')->nullable();
			$table->longText('api_public_key')->nullable();
			$table->longText('auth_token')->nullable();
			$table->string('tokenization_url', 191)->nullable();
			$table->string('payment_redirect_url', 191)->nullable();
			$table->string('callback_url', 191)->nullable();
			$table->integer('gateway_condition')->default(2)->comment('1 for live 2 for testing');
            $table->integer('payment_step')->default(1)->comment('payment will be done in how many steps like authorization and capture in case of payu card');
            $table->longText('additional_data')->nullable()->comment('Store Extra details in json');
            // Debit for wallet money add and ride payment
            // Credit for cashout request and referral payment if required
            // Both for Debit and Credit
            $table->tinyInteger('payment_option_for')->default(1)->nullable()->comment('1:Debit,2:Credit,3:Both');
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
		Schema::drop('payment_options_configurations');
	}

}
