<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateCancelPolicyTranslationsTable extends Migration
{

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('cancel_policy_translations', function (Blueprint $table) {
			$table->increments('id');


			$table->integer('cancel_policy_id')->unsigned();
			$table->foreign('cancel_policy_id')->references('id')->on('cancel_policies')->onUpdate('RESTRICT')->onDelete('CASCADE');

            $table->string('locale', 10);

			$table->integer('merchant_id')->unsigned()->nullable();
			$table->foreign('merchant_id')->references('id')->on('merchants')->onUpdate('RESTRICT')->onDelete('CASCADE');

			$table->string('title', 191);

			$table->string('description', 191);


		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('job_vacancy_translations');
	}
}
