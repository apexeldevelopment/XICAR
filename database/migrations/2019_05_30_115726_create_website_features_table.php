<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateWebsiteFeaturesTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('website_features', function(Blueprint $table)
		{
			$table->increments('id');
            $table->unsignedInteger('merchant_id');
//            $table->foreign('merchant_id')->references('id')->on('merchants')->onUpdate('RESTRICT')->onDelete('CASCADE');

            $table->unsignedInteger('web_site_home_page_id');
            $table->foreign('web_site_home_page_id')->references('id')->on('web_site_home_pages')->onUpdate('RESTRICT')->onDelete('CASCADE');

            $table->string('application', 10);
//            web_site_home_pages
//			$table->string('app_title', 191);
//			$table->string('feature_image', 191);
			$table->timestamps();



//            $table->increments('id');
//            $table->integer('merchant_id');
//            $table->string('application', 191);
//            $table->string('feature_image', 191);
//            $table->timestamps();
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('website_features');
	}

}
