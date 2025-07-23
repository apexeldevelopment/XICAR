<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateAppliedJobsTable extends Migration
{

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('applied_jobs', function (Blueprint $table) {
			$table->increments('id');

			$table->integer('job_vacancy_id')->unsigned();
			$table->foreign('job_vacancy_id')->references('id')->on('job_vacancies')->onUpdate('RESTRICT')->onDelete('CASCADE');

			$table->integer('user_id')->unsigned();
			$table->foreign('user_id')->references('id')->on('users')->onUpdate('RESTRICT')->onDelete('CASCADE');

			$table->integer('merchant_id')->unsigned();
			$table->foreign('merchant_id')->references('id')->on('merchants')->onUpdate('RESTRICT')->onDelete('CASCADE');

			$table->string('cv', 191)->nullable();
			$table->text('notes')->nullable();

			$table->integer('status')->default(1)->comment('1:Applied, 2: Accepted 3: Other');
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
		Schema::drop('applied_jobs');
	}
}
