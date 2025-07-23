<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateJobVacancyTranslationsTable extends Migration
{

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('job_vacancy_translations', function (Blueprint $table) {
			$table->increments('id');


			$table->integer('job_vacancy_id')->unsigned();
			$table->foreign('job_vacancy_id')->references('id')->on('job_vacancies')->onUpdate('RESTRICT')->onDelete('CASCADE');

			$table->integer('merchant_id')->unsigned()->nullable();
			$table->foreign('merchant_id')->references('id')->on('merchants')->onUpdate('RESTRICT')->onDelete('CASCADE');

			$table->string('title', 191);
			$table->string('organization', 191)->comment();
			$table->string('description', 191);

			$table->string('locale', 10);
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
