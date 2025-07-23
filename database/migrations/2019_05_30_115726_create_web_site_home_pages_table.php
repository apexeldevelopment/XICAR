<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateWebSiteHomePagesTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('web_site_home_pages', function(Blueprint $table)
		{
            $table->increments('id');
            $table->integer('merchant_id');
            $table->string('logo')->nullable();
            $table->string('footer_logo')->nullable();
            $table->string('user_banner_image', 255)->nullable();
            $table->string('bg_color_primary', 20)->nullable();
            $table->string('bg_color_secondary', 20)->nullable();
            $table->string('text_color_primary', 255)->nullable();
            $table->string('text_color_secondary', 255)->nullable();
            // for driver website if exist
            $table->string('driver_banner_image', 255)->nullable();
            $table->string('driver_footer_image', 255)->nullable();
            //for website dynamic images (2)
			$table->string('home_page_image_1', 255)->nullable();
			$table->string('home_page_image_2', 255)->nullable();
			$table->string('home_page_icon_1', 255)->nullable();
			$table->string('home_page_icon_2', 255)->nullable();
			$table->string('home_page_icon_3', 255)->nullable();
			$table->string('home_page_icon_4', 255)->nullable();
			$table->string('home_page_qr_image_1', 255)->nullable();
			$table->string('home_page_qr_image_2', 255)->nullable();
			
			$table->string('android_user_url_link', 500)->nullable();
			$table->string('android_driver_url_link', 500)->nullable();
			$table->string('ios_user_url_link', 500)->nullable();
			$table->string('ios_driver_url_link', 500)->nullable();

			$table->string('feature_description_image', 500)->nullable();
			$table->string('featured_component_main_image', 191)->nullable();
			$table->string('user_login_bg_image', 191)->nullable();
			$table->string('driver_login_bg_image', 191)->nullable();
			$table->string('user_estimate_image', 191)->nullable();




            $table->timestamps();


//			$table->increments('id');
//			$table->integer('merchant_id');
//            $table->string('logo')->nullable();
//			$table->string('user_banner_image', 255)->nullable();
//			$table->string('footer_bgcolor', 255)->nullable();
//			$table->string('footer_text_color', 255)->nullable();
//            $table->text('user_book_form_config')->nullable();
//            $table->text('user_estimate_container')->nullable();
//            $table->text('android_link')->nullable();
//            $table->text('ios_link')->nullable();
//            // for driver website if exist
//            $table->string('driver_banner_image', 255)->nullable();
//            $table->string('driver_footer_image', 255)->nullable();
//			$table->timestamps();
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('web_site_home_pages');
	}

}
