<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateJobVacanciesTable extends Migration
{

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('job_vacancies', function (Blueprint $table) {
			$table->increments('id');

			$table->integer('merchant_id')->unsigned()->nullable();
            $table->foreign('merchant_id')->references('id')->on('merchants')->onUpdate('RESTRICT')->onDelete('CASCADE');

            $table->integer('segment_id')->unsigned()->nullable();
            $table->foreign('segment_id')->references('id')->on('segments')->onUpdate('RESTRICT')->onDelete('CASCADE');

            // $table->integer('country_area_id')->unsigned()->nullable()->change();
            // $table->foreign('country_area_id')->references('id')->on('country_areas')->onUpdate('RESTRICT')->onDelete('CASCADE');

			$table->string('title', 191);
			$table->text('description');

			$table->integer('type')->nullable()->default(1)->comment('1 full time');



			$table->string('start_date', 191)->nullable();
			$table->string('end_date', 191)->nullable();

			$table->timestamp('deleted')->nullable();
			$table->integer('status')->default(1)->comment('1:Active, 2: Inactive 3: Expired');
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
		Schema::drop('promo_codes');
	}
}
