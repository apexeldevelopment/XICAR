<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateUserWalletTransactionsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('user_wallet_transactions', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('merchant_id');
			$table->integer('user_id');
			$table->unsignedInteger('carpooling_ride_id')->nullable();
            $table->foreign('carpooling_ride_id')->references('id')->on('carpooling_rides')->onUpdate('RESTRICT')->onDelete('CASCADE');
			$table->unsignedInteger('carpooling_ride_user_detail_id')->nullable();
            $table->foreign('carpooling_ride_user_detail_id')->references('id')->on('carpooling_ride_user_details')->onUpdate('RESTRICT')->onDelete('CASCADE');
			$table->string('payment_request', 191)->nullable();
            $table->integer('narration')->nullable();
			$table->integer('platfrom');
			$table->string('amount', 191);
			$table->integer('type');
			$table->integer('payment_method')->default(2);
			$table->string('display_payment_method', 191)->nullable();
			$table->integer('booking_id')->nullable();
			$table->string('receipt_number', 191);
			$table->text('description')->nullable();
			$table->text('transaction_id')->nullable();

            $table->integer('payment_option_id')->unsigned()->nullable();
            $table->foreign('payment_option_id')->references('id')->on('payment_options')->onUpdate('RESTRICT')->onDelete('CASCADE');
            $table->unsignedInteger('wallet_transfer_id')->unsigned()->nullable();
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
		Schema::drop('user_wallet_transactions');
	}

}
